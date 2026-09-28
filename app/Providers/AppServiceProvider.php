<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Document;
use App\Observers\DocumentObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Đăng ký giám sát bảng Documents
        Document::observe(DocumentObserver::class);
    }
}