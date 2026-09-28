<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// Nhớ import model User của sếp vào đây nếu cần
use App\Models\User; 

class Conversation extends Model
{
    protected $guarded = [];

    // Tạo liên kết để lấy thông tin nhân viên đang hỗ trợ
    public function agent()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}