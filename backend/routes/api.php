<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\UserController; // Импорт контроллера админки
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Тестовый роут (проверка, что API жив)
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

// 🔥 РОУТ ДЛЯ КАРТИНОК (ПУБЛИЧНЫЙ)
// Нужен, чтобы отдавать файлы через контроллер, минуя проблемы с папками Windows и Symlinks
Route::get('/avatar/{filename}', [AuthController::class, 'getAvatar']);


// === ЗАЩИЩЕННЫЕ РОУТЫ (Требуют токен) ===
Route::middleware('auth:sanctum')->group(function () {

    // 1. Получить данные текущего пользователя
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // 2. Управление аватаром
    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);
    Route::delete('/user/avatar', [AuthController::class, 'deleteAvatar']);

    // 3. Старый роут (для совместимости, если где-то еще используется)
    Route::get('/me', [AuthController::class, 'me']);

    // === АДМИНКА (Только для роли admin) ===
    // Контроллер сам проверит роль, но здесь мы группируем маршруты
    Route::prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);       // Получить всех
        Route::put('/users/{id}', [UserController::class, 'update']); // Обновить юзера
        Route::delete('/users/{id}', [UserController::class, 'destroy']); // Удалить юзера
    });

});