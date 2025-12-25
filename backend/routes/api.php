<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Admin\ServerController;
use App\Http\Controllers\EggController; // 🔥 Убедись, что этот файл создан
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

// === ПУБЛИЧНЫЕ РОУТЫ ===
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/avatar/{filename}', [AuthController::class, 'getAvatar']);
Route::get('/products', [ProductController::class, 'index']);

// 🔥 РОУТ ДЛЯ ПОЛУЧЕНИЯ ДЕРЕВА ЯДЕР
Route::get('/eggs/tree', [EggController::class, 'index']);


// === ЗАЩИЩЕННЫЕ РОУТЫ ===
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);
    Route::delete('/user/avatar', [AuthController::class, 'deleteAvatar']);

    // БИЛЛИНГ
    Route::post('/payment/topup', [PaymentController::class, 'topup']);     
    Route::get('/payment/history', [PaymentController::class, 'history']);  

    // УСЛУГИ
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

    // ТИКЕТЫ
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

    Route::get('/me', [AuthController::class, 'me']);

    // === АДМИНКА ===
    Route::prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        Route::post('/products', [ProductController::class, 'store']);      
        Route::put('/products/{id}', [ProductController::class, 'update']); 
        Route::delete('/products/{id}', [ProductController::class, 'destroy']); 

        Route::get('/tickets', [TicketController::class, 'adminIndex']); 
        Route::get('/tickets/{id}', [TicketController::class, 'adminShow']); 
        Route::put('/tickets/{id}/status', [TicketController::class, 'updateStatus']); 
        Route::post('/tickets/{id}/reply', [TicketController::class, 'adminReply']); 

        Route::post('/notifications/send', [NotificationController::class, 'send']);

        // Если есть Admin\ServerController
        Route::get('/servers', [ServerController::class, 'index']);
    });
});