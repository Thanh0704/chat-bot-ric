<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class DeleteDocumentAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $documentId;

    public function __construct($documentId)
    {
        // Khi xóa file, chúng ta chỉ cần truyền ID của tài liệu để đi tìm và xóa trên Pinecone
        $this->documentId = $documentId;
    }

    public function handle(): void
    {
        try {
            $pineconeKey = env('PINECONE_API_KEY');
            $pineconeHost = env('PINECONE_HOST');
            $pineconeHost = str_replace(['https://', 'http://'], '', $pineconeHost);

            // Gửi lệnh xóa cho Pinecone: Tìm tất cả vector có metadata là document_id này và xóa sạch
            $response = Http::timeout(60)->withHeaders([
                'Api-Key' => $pineconeKey,
                'Content-Type' => 'application/json'
            ])->post("https://{$pineconeHost}/vectors/delete", [
                'filter' => [
                    'document_id' => $this->documentId
                ],
                'namespace' => 'chatbot_data'
            ]);

            if ($response->successful()) {
                Log::info("🗑️ HOÀN THÀNH: Đã XÓA SẠCH trí nhớ của tài liệu ID [{$this->documentId}] trên Pinecone!");
            } else {
                throw new Exception("Lỗi xóa trên Pinecone: " . $response->body());
            }

        } catch (Exception $e) {
            Log::error("❌ LỖI XÓA AI: " . $e->getMessage());
        }
    }
}