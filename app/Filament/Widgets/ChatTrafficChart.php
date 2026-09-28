<?php

namespace App\Filament\Widgets;

use App\Models\Message; // Gọi DB Tin nhắn
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;      // Gọi thư viện thời gian

class ChatTrafficChart extends ChartWidget
{
    protected ?string $heading = 'Lưu lượng tin nhắn 7 ngày qua';
    protected static ?int $sort = 2; 
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Quét lùi 6 ngày về trước + ngày hôm nay (Tổng 7 ngày)
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m'); // Hiển thị nhãn dạng Ngày/Tháng (VD: 21/09)

            // Đếm tổng số tin nhắn sinh ra trong ngày đó
            $count = Message::whereDate('created_at', $date->toDateString())->count();
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Số lượng tin nhắn',
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}