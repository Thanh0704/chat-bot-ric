<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // ID của nhân viên CSKH bấm nút "Tiếp nhận"
            $table->unsignedBigInteger('assigned_to')->nullable()->after('status');
            // Thời gian tiếp nhận
            $table->timestamp('assigned_at')->nullable()->after('assigned_to');

            // Khóa ngoại
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropColumn(['assigned_to', 'assigned_at']);
        });
    }
};