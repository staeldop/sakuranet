<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProductController; // <--- Импорт контроллера товаров
use App\Http\Controllers\PaymentController; // <--- Импорт контроллера платежей
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

// Роут для картинок аватаров
Route::get('/avatar/{filename}', [AuthController::class, 'getAvatar']);

// 🔥 ТОВАРЫ (Публичный список для страницы заказа)
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
    Route::post('/payment/topup', [PaymentController::class, 'topup']);     // Создать платеж
    Route::get('/payment/history', [PaymentController::class, 'history']);  // История операций

    // 4. Старый роут (совместимость)
    Route::get('/me', [AuthController::class, 'me']);

    // === АДМИНКА (Только для роли admin) ===
    Route::prefix('admin')->group(function () {
        
        // Управление пользователями
        Route::get('/users', [UserController::class, 'index']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // Управление товарами (Создание и Удаление)
        Route::post('/products', [ProductController::class, 'store']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    });

});