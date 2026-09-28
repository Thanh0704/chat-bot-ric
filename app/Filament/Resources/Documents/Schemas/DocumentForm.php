<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Tên tài liệu')
                ->required()
                ->maxLength(255),
                
            FileUpload::make('file_path')
                ->label('Tải lên file (PDF, DOCX, TXT)')
                ->disk('public') // 👈 ĐÂY CHÍNH LÀ CHÌA KHÓA: Ép lưu vào ổ public
                ->directory('documents') // Sẽ thành: storage/app/public/documents
                ->acceptedFileTypes([
                    'application/pdf', 
                    'text/plain', 
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ])
                ->required(),
        ]);
    }
}