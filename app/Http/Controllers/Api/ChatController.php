<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\Conversation;
use App\Models\Message;

class ChatController extends Controller
{
    public function ask(Request $request)
    {
        try {
            $question = $request->input('question');
            $history = $request->input('history', []); 
            $conversationId = $request->input('conversation_id'); 
            
            if (empty($question)) {
                return response()->json(['status' => 'error', 'message' => 'Vui lòng nhập câu hỏi.'], 400);
            }

            // ==========================================
            // TẠO HOẶC TÌM PHÒNG CHAT
            // ==========================================
           if (!$conversationId) {
                $conversation = Conversation::create([
                    'title' => mb_substr($question, 0, 40) . '...', 
                    'status' => 'AI_HANDLING',
                    'customer_name' => $request->input('customer_name'), // Hứng Tên từ Next.js
                    'customer_phone' => $request->input('customer_phone') // Hứng SĐT từ Next.js
                ]);
                $conversationId = $conversation->id;
            } else {
                $conversation = Conversation::findOrFail($conversationId);
                
                // 🔥 ĐOẠN MỚI THÊM: HỒI SINH PHÒNG CHAT ĐÃ ĐÓNG
                // Nếu khách hàng nhắn tin lại vào một ca đã bị đánh dấu CLOSED
                // Hệ thống tự động mở lại và giao cho AI tiếp đón
                if ($conversation->status === 'CLOSED') {
                    $conversation->update(['status' => 'AI_HANDLING']);
                }
            }

            // Lưu tin nhắn của khách hàng
            Message::create([
                'conversation_id' => $conversationId,
                'sender_type' => 'user',
                'content' => $question,
            ]);

            // ==========================================
            // CHẶN AI NẾU ĐANG LÀ NHÂN VIÊN XỬ LÝ
            // ==========================================
            if ($conversation->status === 'WAITING_FOR_AGENT' || $conversation->status === 'AGENT_HANDLING') {
                return response()->json([
                    'status' => 'success',
                    'answer' => 'Dạ, anh/chị đợi một chút nhé, chuyên viên của Ricvina đang vào hỗ trợ mình ạ!',
                    'is_handover' => true, // Thêm Cờ này báo hiệu không cần AI trả lời
                    'conversation_id' => $conversationId, 
                ]);
            }

            // ==========================================
            // CÁC BƯỚC XỬ LÝ AI
            // ==========================================
            $geminiKey = env('GEMINI_API_KEY');
            $pineconeKey = env('PINECONE_API_KEY');
            $pineconeHost = str_replace(['https://', 'http://'], '', env('PINECONE_HOST'));

            // Bước 1: Vector hóa (Giữ nguyên model gemini-embedding-2 của bác)
            $embedResponse = Http::timeout(60)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-2:embedContent?key={$geminiKey}", [
                'model' => 'models/gemini-embedding-2',
                'content' => ['parts' => [['text' => $question]]],
                'outputDimensionality' => 768
            ]);
            
            if (!$embedResponse->successful()) throw new Exception("Lỗi Vector: " . $embedResponse->body());
            
            $questionVector = array_slice($embedResponse->json('embedding.values'), 0, 768);

            // Bước 2: Tìm Pinecone
            $pineconeResponse = Http::timeout(60)->withHeaders([
                'Api-Key' => $pineconeKey,
                'Content-Type' => 'application/json'
            ])->post("https://{$pineconeHost}/query", [
                'vector' => $questionVector,
                'topK' => 5, 
                'includeMetadata' => true, 
                'namespace' => 'chatbot_data'
            ]);
            
            if (!$pineconeResponse->successful()) throw new Exception("Lỗi Pinecone: " . $pineconeResponse->body());
            
            $matches = $pineconeResponse->json('matches');
            $contextText = "";
            foreach ($matches as $match) {
                if (isset($match['score']) && $match['score'] > 0.6) {
                    $contextText .= $match['metadata']['text'] . "\n\n";
                }
            }

          // Bước 3: Prompt AI (Đã nâng cấp thành AI Mở & Thông minh)
            $prompt = "Bạn là Ricvina - Trợ lý AI Chăm sóc khách hàng cao cấp của công ty Ricvina.\n";
            $prompt .= "YÊU CẦU VỀ GIỌNG ĐIỆU: Xưng 'em', gọi khách là 'anh/chị', luôn lịch sự, dùng các từ 'Dạ', 'Vâng ạ'.\n\n";
            
            $prompt .= "QUY TẮC XỬ LÝ CÂU HỎI:\n";
            $prompt .= "1. KIẾN THỨC CHUNG & GIAO TIẾP: Nếu khách hỏi các vấn đề chung chung, đời sống, hoặc giao tiếp bình thường, hãy dùng sự thông minh và hiểu biết mở của bạn để trò chuyện một cách linh hoạt, tự nhiên.\n";
            $prompt .= "2. THÔNG TIN CÔNG TY/SẢN PHẨM: Nếu khách hỏi chuyên sâu về sản phẩm, dịch vụ, bảng giá, hoặc thông tin của Ricvina, bạn PHẢI ưu tiên sử dụng dữ liệu trong phần 'TÀI LIỆU CUNG CẤP' bên dưới để trả lời.\n";
            $prompt .= "3. XỬ LÝ KHI THIẾU THÔNG TIN: Nếu khách hỏi về công ty/sản phẩm nhưng trong 'TÀI LIỆU CUNG CẤP' không có dữ liệu, TUYỆT ĐỐI KHÔNG TỰ BỊA RA. Hãy trả lời khéo léo rằng: 'Dạ, hiện tại em chưa có sẵn thông tin chi tiết về vấn đề này. Anh/chị vui lòng nhấn nút Gặp nhân viên để em kết nối chuyên viên tư vấn trực tiếp cho mình nhé!'.\n\n";
            
            $prompt .= "--- TÀI LIỆU CUNG CẤP ---\n";
            $prompt .= empty(trim($contextText)) ? "Không có tài liệu cụ thể cho câu hỏi này.\n" : $contextText . "\n";

            if (!empty($history) && is_array($history)) {
                $prompt .= "--- LỊCH SỬ TRÒ CHUYỆN ---\n";
                foreach ($history as $msg) {
                    $role = $msg['role'] === 'user' ? 'Khách hàng' : 'Ricvina';
                    $prompt .= "{$role}: {$msg['content']}\n";
                }
            }
            $prompt .= "\n--- CÂU HỎI HIỆN TẠI ---\n" . $question;

            // Bước 4: Gọi Gemini (Giữ nguyên model 3.5 của bác + TẮT BỘ LỌC AN TOÀN)
            $chatResponse = Http::timeout(60)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key={$geminiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.45],
                'safetySettings' => [
                    ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                    ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                    ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                    ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
                ]
            ]);

            if (!$chatResponse->successful()) throw new Exception("Lỗi API Gemini: " . $chatResponse->body());
            
            $answer = $chatResponse->json('candidates.0.content.parts.0.text');

            // Xử lý dự phòng nếu Data trả về bị rỗng
            if (empty($answer)) {
                $answer = "Dạ hiện tại em chưa có sẵn thông tin này, anh/chị vui lòng nhấn 'Gặp nhân viên' để em kết nối chuyên viên hỗ trợ trực tiếp nhé!";
            }

            $aiMessage = Message::create([
                'conversation_id' => $conversationId,
                'sender_type' => 'ai',
                'content' => $answer,
            ]);

            return response()->json([
                'status' => 'success',
                'answer' => $answer,
                'conversation_id' => $conversationId, 
                'message_id' => $aiMessage->id        
            ]);

        } catch (Exception $e) {
            Log::error("❌ LỖI CHAT API: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function feedback(Request $request, $id)
    {
        try {
            $message = Message::findOrFail($id);
            $message->update(['is_helpful' => $request->input('is_helpful')]);
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // TÍNH NĂNG MỚI: CHUYỂN YÊU CẦU CHO NHÂN VIÊN
    // ==========================================
    public function handover($id)
    {
        try {
            $conversation = Conversation::findOrFail($id);
            
            // Đổi trạng thái sang chờ nhân viên
            $conversation->update(['status' => 'WAITING_FOR_AGENT']);

            return response()->json([
                'status' => 'success', 
                'message' => 'Đã chuyển trạng thái thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // TÍNH NĂNG ĐỒNG BỘ TRẠNG THÁI CHO NEXT.JS
    // ==========================================
    public function loadMessages($id)
    {
        try {
            $conversation = \App\Models\Conversation::findOrFail($id);

            $messages = \App\Models\Message::where('conversation_id', $id)->orderBy('id', 'asc')->get()->map(function($msg) {
                return [
                    'id' => $msg->sender_type === 'ai' ? $msg->id : null,
                    'role' => $msg->sender_type === 'user' ? 'user' : 'bot', 
                    'content' => $msg->content,
                    'feedback' => $msg->is_helpful
                ];
            });
            
            return response()->json([
                'status' => 'success', 
                'messages' => $messages,
                
                // 🔥 CỰC KỲ QUAN TRỌNG: Bắn trạng thái hiện tại về cho Frontend
                // Để Next.js biết lúc nào cần bật/tắt nút "Gặp nhân viên"
                'chat_status' => $conversation->status 
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    }

   // ==========================================
    // TÍNH NĂNG PHÂN LUỒNG ADMIN: GÁN NHÂN VIÊN
    // ==========================================
    public function assignConversation(Request $request, $id)
    {
        try {
            $conversation = Conversation::findOrFail($id);

            // 1. Kiểm tra xem chat này có người khác nhanh tay nhận mất chưa
            if ($conversation->status === 'AGENT_HANDLING' && $conversation->assigned_to !== $request->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Khách hàng này đã có nhân viên khác tiếp nhận!'
                ], 403);
            }

            // 2. Cập nhật trạng thái và "đánh dấu chủ quyền" cho nhân viên đang đăng nhập
            $conversation->update([
                'status' => 'AGENT_HANDLING',
                'assigned_to' => $request->user()->id, 
                'assigned_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Tiếp nhận thành công!',
                'conversation' => $conversation
            ]);
        } catch (\Exception $e) {
            Log::error("❌ LỖI GÁN NHÂN VIÊN: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Không thể tiếp nhận phòng chat.'], 500);
        }
    }
}