<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use ZipArchive;
use Exception;

class ProcessDocumentAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $document;
    public $timeout = 300; // Cấp cho AI 5 phút để xử lý các file lớn

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function handle(): void
    {
        try {
            $this->document->update(['status' => 'processing']);

            // 1. TÌM FILE VÀ BÓC TÁCH CHỮ
            $filePath = Storage::disk('public')->path($this->document->file_path);

            if (!file_exists($filePath)) {
                throw new Exception("Không tìm thấy file: " . $filePath);
            }

            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $text = '';

            if ($extension === 'pdf') {
                $text = (new Parser())->parseFile($filePath)->getText();
            } elseif ($extension === 'txt') {
                $text = file_get_contents($filePath);
            } elseif ($extension === 'docx') {
                $text = $this->extractTextFromDocx($filePath);
            }

            $text = trim(preg_replace('/\s+/', ' ', $text));

            if (empty($text)) {
                throw new Exception("Tài liệu trống hoặc không bóc được chữ.");
            }

            Log::info("✅ Đã bóc thành công " . strlen($text) . " ký tự. Đang gửi lên Google Gemini...");

            // 2. GỌI API GEMINI VÀ LƯU LÊN PINECONE
            $this->embedAndStoreToPinecone($text);

            // 3. KẾT THÚC THÀNH CÔNG
            $this->document->update(['status' => 'processed', 'file_type' => $extension]);
            Log::info("🚀 HOÀN THÀNH: Đã lưu trí nhớ lên Pinecone thành công!");

        } catch (Exception $e) {
            Log::error("❌ LỖI AI: " . $e->getMessage());
            $this->document->update(['status' => 'failed']);
        }
    }

    // Hàm bóc tách chữ từ file Word (.docx)
    private function extractTextFromDocx($filePath): string
    {
        $zip = new ZipArchive;
        $text = '';
        if ($zip->open($filePath) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $text = strip_tags(str_replace(['<w:p', '</w:p>'], ["\n<w:p", "\n</w:p>"], $zip->getFromIndex($index)));
            }
            $zip->close();
        }
        return trim($text);
    }

    // Hàm xử lý Trí Tuệ Nhân Tạo
    private function embedAndStoreToPinecone(string $text): void
    {
        $geminiKey = env('GEMINI_API_KEY');
        $pineconeKey = env('PINECONE_API_KEY');
        $pineconeHost = env('PINECONE_HOST');
        
        // Tự động dọn dẹp link Host cho an toàn
        $pineconeHost = str_replace(['https://', 'http://'], '', $pineconeHost);

        $chunks = explode("\n", wordwrap($text, 1000, "\n"));
        $vectors = [];

        foreach ($chunks as $index => $chunk) {
            $chunk = trim($chunk);
            if (empty($chunk)) continue;

            // 🚀 GỌI GOOGLE GEMINI (Ép Model trả về đúng 768 chiều)
            $response = Http::timeout(60)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-2:embedContent?key={$geminiKey}", [
                'model' => 'models/gemini-embedding-2',
                'content' => ['parts' => [['text' => $chunk]]],
                'outputDimensionality' => 768 // <-- BÍ KÍP 1: Báo Google giảm size
            ]);

            if ($response->successful()) {
                $values = $response->json('embedding.values');
                
                // <-- BÍ KÍP 2: Chặt chẽ 100%, dùng PHP cắt đúng 768 phần tử đầu tiên
                $values = array_slice($values, 0, 768);

                $vectors[] = [
                    'id' => "doc_" . $this->document->id . "_chunk_" . $index,
                    'values' => $values,
                    'metadata' => ['document_id' => $this->document->id, 'text' => $chunk]
                ];
            } else {
                throw new Exception("Lỗi gọi Google Gemini API: " . $response->body());
            }
        }

        // 🚀 ĐẨY VECTOR LÊN KHO TRÍ NHỚ PINECONE
        if (!empty($vectors)) {
            $pineconeResponse = Http::timeout(60)->withHeaders([
                'Api-Key' => $pineconeKey,
                'Content-Type' => 'application/json'
            ])->post("https://{$pineconeHost}/vectors/upsert", [
                'vectors' => $vectors,
                'namespace' => 'chatbot_data'
            ]);

            if (!$pineconeResponse->successful()) {
                throw new Exception("Lỗi lưu lên Pinecone: " . $pineconeResponse->body());
            }
        }
    }
}