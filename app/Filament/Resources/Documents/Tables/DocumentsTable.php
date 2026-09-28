<?php

namespace App\Filament\Resources\Documents\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('stt')
               ->label('STT')
                ->rowIndex(),
                
            TextColumn::make('name')
                ->label('Tên tài liệu')
                ->searchable(),
                
            TextColumn::make('file_type')
                ->label('Định dạng'),
                
            TextColumn::make('status')
                ->label('Trạng thái AI')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'processed' => 'success',
                    'failed' => 'danger',
                    default => 'gray',
                }),
                
            TextColumn::make('created_at')
                ->label('Ngày tải lên')
                ->dateTime()
                ->timezone('Asia/Ho_Chi_Minh'),

        ]) 
        ->paginated([20]);;
            
    }
}