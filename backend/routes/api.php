<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceController; // <--- Импорт контроллера услуг
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

// 🔥 ТОВАРЫ
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

    // 4. 🚀 УСЛУГИ И СЕРВЕРЫ
    Route::get('/services', [ServiceController::class, 'index']);       // Все услуги
    Route::post('/services', [ServiceController::class, 'store']);      // Купить
    Route::get('/services/{id}', [ServiceController::class, 'show']);   // Показать одну (для страницы управления)
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']); // Удалить/Отменить

    // 5. Старый роут
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
    });

});