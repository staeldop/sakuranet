<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Admin\ServerController;
use App\Http\Controllers\EggController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/ping', function () {
    return response()->json([
        'status'  => 'ok',
        'service' => 'sakuranet-billing',
        'time'    => now()->toDateTimeString()
    ]);
});

// === ПУБЛИЧНЫЕ РОУТЫ (Доступны всем) ===
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/avatar/{filename}', [AuthController::class, 'getAvatar']);
Route::get('/products', [ProductController::class, 'index']);

// Сброс пароля (Гостевой доступ)
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Получение ядер (для магазина)
Route::get('/eggs/tree', [EggController::class, 'index']);


// === ЗАЩИЩЕННЫЕ РОУТЫ (Только для авторизованных) ===
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/me', [AuthController::class, 'me']);

    // ПРОФИЛЬ (Аватарка)
    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);
    Route::delete('/user/avatar', [AuthController::class, 'deleteAvatar']);

    // 🔥 БЕЗОПАСНОСТЬ (Смена пароля и 2FA)
    Route::put('/user/password', [AuthController::class, 'updatePassword']); 
    Route::post('/user/send-password-code', [AuthController::class, 'sendPasswordCode']);
    
    // 2FA (Google Authenticator)
    Route::post('/user/two-factor-authentication', [AuthController::class, 'enableTwoFactor']);
    Route::delete('/user/two-factor-authentication', [AuthController::class, 'disableTwoFactor']);
    Route::post('/user/confirmed-two-factor-authentication', [AuthController::class, 'confirmTwoFactor']);
    Route::get('/user/two-factor-qr-code', [AuthController::class, 'getTwoFactorQrCode']);
    Route::get('/user/two-factor-secret-key', [AuthController::class, 'getTwoFactorSecretKey']);
    Route::get('/user/two-factor-recovery-codes', [AuthController::class, 'getTwoFactorRecoveryCodes']);

    // БИЛЛИНГ
    Route::post('/payment/topup', [PaymentController::class, 'topup']);     
    Route::get('/payment/history', [PaymentController::class, 'history']);  

    // УСЛУГИ (Сервера)
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
    Route::post('/services/{id}/change-core', [ServiceController::class, 'changeCore']);

    // ТИКЕТЫ (Клиент)
    Route::get('/tickets', [TicketController::class, 'index']);         
    Route::post('/tickets', [TicketController::class, 'store']);        
    Route::get('/tickets/{id}', [TicketController::class, 'show']);      
    Route::post('/tickets/{id}/reply', [TicketController::class, 'reply']); 

    // УВЕДОМЛЕНИЯ
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications', [NotificationController::class, 'destroyAll']);

    // === АДМИНКА (ТОЛЬКО ДЛЯ АДМИНОВ) ===
    // Добавляем middleware 'admin', который мы создали выше
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        
        // Пользователи
        Route::get('/users', [UserController::class, 'index']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // Продукты (Товары)
        Route::post('/products', [ProductController::class, 'store']);      
        Route::put('/products/{id}', [ProductController::class, 'update']); 
        Route::delete('/products/{id}', [ProductController::class, 'destroy']); 

        // Тикеты (Админ)
        Route::get('/tickets', [TicketController::class, 'adminIndex']); 
        Route::get('/tickets/{id}', [TicketController::class, 'adminShow']); 
        Route::put('/tickets/{id}/status', [TicketController::class, 'updateStatus']); 
        Route::post('/tickets/{id}/reply', [TicketController::class, 'adminReply']); 

        // Уведомления (Рассылка)
        Route::post('/notifications/send', [NotificationController::class, 'send']);

        // Сервера (Управление Pterodactyl)
        Route::get('/servers', [ServerController::class, 'index']);
    });
});