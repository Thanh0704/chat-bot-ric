<?php

use Illuminate\Support\Facades\Route;

// Lệnh này bắt buộc trình duyệt tự động nhảy từ cổng 8000 sang trang đăng nhập của Filament
Route::redirect('/', '/admin/login');