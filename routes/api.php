<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatController;

// Thêm middleware throttle:10,1 (Giới hạn mỗi IP chỉ được gọi 10 lần / 1 phút)
Route::post('/chat', [ChatController::class, 'ask'])->middleware('throttle:10,1');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/messages/{id}/feedback', [App\Http\Controllers\Api\ChatController::class, 'feedback']);
Route::post('/conversations/{id}/handover', [App\Http\Controllers\Api\ChatController::class, 'handover']); // Dòng mới
Route::get('/conversations/{id}/messages', [App\Http\Controllers\Api\ChatController::class, 'loadMessages']);
Route::post('/conversations/{id}/assign', [ChatController::class, 'assignConversation'])->middleware('auth:sanctum');