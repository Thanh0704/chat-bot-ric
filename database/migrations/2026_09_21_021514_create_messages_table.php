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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete(); // Nối với bảng conversations
            $table->string('sender_type'); // 'user', 'ai', hoặc 'agent'
            $table->text('content');       // Nội dung chat
            $table->boolean('is_helpful')->nullable(); // Dùng cho chức năng Feedback 👍/👎 sau này
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
