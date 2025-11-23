<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // <-- Добавил

class AuthController extends Controller
{
    // === РЕГИСТРАЦИЯ ===
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $token = $user->createToken('frontend')->plainTextToken;

            return response()->json([
                'message' => 'Регистрация успешна',
                'user' => $user,
                'token' => $token
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Если валидация не пройдена, Laravel автоматически вернет 422
            throw $e; 
        } catch (\Exception $e) {
            Log::error('Ошибка регистрации: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ошибка сервера: ' . $e->getMessage(),
            ], 500);
        }
    }

    // === ЛОГИН (ИСПРАВЛЕН) ===
    public function login(Request $request)
    {
        // 1. Валидация
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 2. Используем стандартный Auth::attempt
        if (!Auth::attempt($validated)) {
            // Если аутентификация не прошла
            return response()->json([
                'message' => 'Неверный email или пароль. (Код 401)',
            ], 401); // Возвращаем 401 - Unauthorized
        }

        // 3. Успешный вход
        $user = $request->user();
        $token = $user->createToken('frontend')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 200);
    }
    // ... остальной код контроллера ...
    // ...
    public function me(Request $request)
    {
        return $request->user();
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();
        $disk = 'local'; 
        $folder = 'public/avatars';

        try {
            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    $oldFilename = basename($user->avatar);
                    $oldPath = $folder . '/' . $oldFilename;
                    if (Storage::disk($disk)->exists($oldPath)) {
                        Storage::disk($disk)->delete($oldPath);
                    }
                }

                $path = $request->file('avatar')->store($folder, $disk);
                
                $filename = basename($path);
                $url = url("/api/avatar/{$filename}");

                $user->avatar = $url; 
                $user->save();

                return response()->json([
                    'message' => 'Аватар успешно обновлен',
                    'avatar_url' => $url,
                    'user' => $user
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Ошибка загрузки аватара: ' . $e->getMessage());
            return response()->json(['message' => 'Не удалось сохранить файл'], 500);
        }

        return response()->json(['message' => 'Файл не был загружен'], 400);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();
        $disk = 'local';
        $folder = 'public/avatars';

        if ($user->avatar) {
            $filename = basename($user->avatar);
            $path = $folder . '/' . $filename;

            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }

            $user->avatar = null;
            $user->save();
        }

        return response()->json([
            'message' => 'Аватар удален',
            'user' => $user
        ]);
    }

    public function getAvatar($filename)
    {
        $disk = 'local';
        $path = 'public/avatars/' . $filename;

        if (!Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        $absolutePath = Storage::disk($disk)->path($path);

        return response()->file($absolutePath);
    }
}