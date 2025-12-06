<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\NotificationController; // <--- 🔥 ДОБАВИЛ ИМПОРТ
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Тестовый роут
Route::get('/ping', function () {
    return response()->json([
        'status'  => 'ok',
        'service' => 'sakuranet-billing',
        'time'    => now()->toDateTimeString()
    ]);
});

// === ПУБЛИЧНЫЕ РОУТЫ (Без токена) ===
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/avatar/{filename}', [AuthController::class, 'getAvatar']);
Route::get('/products', [ProductController::class, 'index']);


// === ЗАЩИЩЕННЫЕ РОУТЫ (Требуют токен) ===
Route::middleware('auth:sanctum')->group(function () {

    // 1. Данные пользователя
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // 2. Управление аватаром
    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);
    Route::delete('/user/avatar', [AuthController::class, 'deleteAvatar']);

    // 3. БИЛЛИНГ И ПЛАТЕЖИ
    Route::post('/payment/topup', [PaymentController::class, 'topup']);     
    Route::get('/payment/history', [PaymentController::class, 'history']);  

    // 4. УСЛУГИ И СЕРВЕРЫ
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

    // 5. 🎫 ТИКЕТЫ (ПОДДЕРЖКА) - КЛИЕНТ
    Route::get('/tickets', [TicketController::class, 'index']);          // Список тикетов
    Route::post('/tickets', [TicketController::class, 'store']);         // Создать новый
    Route::get('/tickets/{id}', [TicketController::class, 'show']);      // Посмотреть переписку
    Route::post('/tickets/{id}/reply', [TicketController::class, 'reply']); // Ответить

    // 6. 🔔 УВЕДОМЛЕНИЯ (КЛИЕНТ) - 🔥 НОВЫЕ РОУТЫ
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications', [NotificationController::class, 'destroyAll']);

    // 7. Старый роут
    Route::get('/me', [AuthController::class, 'me']);

    // === АДМИНКА ===
    Route::prefix('admin')->group(function () {
        
        // Пользователи
        Route::get('/users', [UserController::class, 'index']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // Товары
        Route::post('/products', [ProductController::class, 'store']);      
        Route::put('/products/{id}', [ProductController::class, 'update']); 
        Route::delete('/products/{id}', [ProductController::class, 'destroy']); 

        // 🎫 ТИКЕТЫ - АДМИН
        Route::get('/tickets', [TicketController::class, 'adminIndex']); 
        Route::get('/tickets/{id}', [TicketController::class, 'adminShow']); 
        Route::put('/tickets/{id}/status', [TicketController::class, 'updateStatus']); 
        Route::post('/tickets/{id}/reply', [TicketController::class, 'adminReply']); 
    });

});
