<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('documents', function (Blueprint $table) {
        $table->id();
        $table->string('name')->comment('Tên hiển thị của tài liệu');
        $table->string('file_path')->comment('Đường dẫn lưu file');
        $table->string('file_type')->nullable()->comment('Loại file: pdf, docx, txt');
        $table->string('status')->default('pending')->comment('Trạng thái AI xử lý: pending, processed, failed');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
