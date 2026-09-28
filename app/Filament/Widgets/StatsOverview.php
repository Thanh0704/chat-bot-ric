<?php

namespace App\Filament\Widgets;

use App\Models\Conversation; // Chèn Model Conversation
use App\Models\Message;      // Chèn Model Message
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected ?string $pollingInterval = '5s';

   protected function getStats(): array
    {
        // 1. Đếm tổng số lượng cuộc hội thoại
        $totalConversations = Conversation::count();
        
        // 2. Đếm số lượng tin nhắn do AI gửi
        $aiMessages = Message::where('sender_type', 'ai')->count(); 
        
        // 3. Đếm số ca đang chờ nhân viên
        $waitingForAgent = Conversation::where('status', 'WAITING_FOR_AGENT')->count();

        // 4. LẤY DỮ LIỆU ĐÁNH GIÁ (FEEDBACK)
        $likes = Message::where('is_helpful', true)->count();   // Đếm số lượt 👍
        $dislikes = Message::where('is_helpful', false)->count(); // Đếm số lượt 👎

        return [
            Stat::make('Tổng số hội thoại', $totalConversations)
                ->description('Tất cả từ trước đến nay')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('success'),

            Stat::make('Tin nhắn AI đã trả lời', $aiMessages)
                ->description('Hiệu suất hoạt động của AI')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('primary'),

            // THẺ MỚI: HIỂN THỊ CHỈ SỐ HÀI LÒNG
            Stat::make('Đánh giá Hữu ích (👍)', $likes)
                ->description($dislikes > 0 ? "Cảnh báo: Có {$dislikes} lượt chê (👎)" : 'Chưa có đánh giá tiêu cực')
                ->descriptionIcon($dislikes > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-hand-thumb-up')
                ->color($dislikes > 0 ? 'warning' : 'success'),

            Stat::make('Cần nhân viên hỗ trợ', $waitingForAgent)
                ->description('Các ca AI không xử lý được')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($waitingForAgent > 0 ? 'danger' : 'success'),
        ];
    }
}