<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_to')->nullable()->after('status');
            $table->timestamp('assigned_at')->nullable()->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['assigned_to', 'assigned_at']);
        });
    }
};