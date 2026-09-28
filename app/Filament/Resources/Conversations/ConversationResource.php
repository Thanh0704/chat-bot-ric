<?php

namespace App\Filament\Resources\Conversations;

use App\Filament\Resources\Conversations\Pages;
use App\Models\Conversation;
use Filament\Forms;
// Không gọi use Filament\Forms\Form nữa, dùng trực tiếp Schemas\Schema ở dưới
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Data Khách hàng';
    protected static ?string $modelLabel = 'Khách hàng';
    protected static ?string $pluralModelLabel = 'Danh sách Data Khách hàng';
    protected static ?int $navigationSort = 3; 

    // ĐÃ FIX: Đổi (Form $form): Form thành (\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('customer_name')->label('Tên khách hàng'),
                Forms\Components\TextInput::make('customer_phone')->label('Số điện thoại'),
                Forms\Components\TextInput::make('title')->label('Câu hỏi đầu tiên')->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'AI_HANDLING' => 'AI đang xử lý',
                        'WAITING_FOR_AGENT' => 'Đang chờ nhân viên',
                        'AGENT_HANDLING' => 'Nhân viên đang chat',
                        'RESOLVED' => 'Đã kết thúc',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Tên khách hàng')
                    ->searchable() 
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Số điện thoại')
                    ->searchable()
                    ->copyable() 
                    ->copyMessage('Đã copy số điện thoại!')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Khách hỏi gì?')
                    ->limit(40) 
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge() 
                    ->color(fn (string $state): string => match ($state) {
                        'AI_HANDLING' => 'gray',
                        'WAITING_FOR_AGENT' => 'warning',
                        'AGENT_HANDLING' => 'info',
                        'RESOLVED' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'AI_HANDLING' => '🤖 AI đang chat',
                        'WAITING_FOR_AGENT' => '⏳ Chờ nhận ca',
                        'AGENT_HANDLING' => '🎧 Đang hỗ trợ',
                        'RESOLVED' => '✅ Đã xong',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày liên hệ')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc') 
            ->filters([
                //
            ])
            ->actions([
                
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversations::route('/'),
            'create' => Pages\CreateConversation::route('/create'),
         
            'edit' => Pages\EditConversation::route('/{record}/edit'),
        ];
    }
}