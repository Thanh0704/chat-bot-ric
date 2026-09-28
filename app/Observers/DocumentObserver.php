<?php

namespace App\Observers;

use App\Models\Document;
use App\Jobs\ProcessDocumentAI;
use App\Jobs\DeleteDocumentAI; // <-- THÊM DÒNG NÀY: Giới thiệu anh Công nhân dọn rác

class DocumentObserver
{
    public function created(Document $document): void
    {
        ProcessDocumentAI::dispatch($document);
    }

    public function updated(Document $document): void
    {
        //
    }

    // KHI XÓA TÀI LIỆU TRÊN GIAO DIỆN WEB
    public function deleted(Document $document): void
    {
        // <-- THÊM DÒNG NÀY: Đẩy ID của tài liệu bị xóa vào Hàng đợi để dọn dẹp Pinecone
        DeleteDocumentAI::dispatch($document->id); 
    }

    public function restored(Document $document): void
    {
        //
    }

    public function forceDeleted(Document $document): void
    {
        // Nếu bạn dùng xóa vĩnh viễn (force delete), cũng nên gọi hàm xóa
        DeleteDocumentAI::dispatch($document->id);
    }
}