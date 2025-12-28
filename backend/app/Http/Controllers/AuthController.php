<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

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
            throw $e; 
        } catch (\Exception $e) {
            Log::error('Ошибка регистрации: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

    // === ЛОГИН ===
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($validated)) {
            return response()->json(['message' => 'Неверный email или пароль.'], 401);
        }

        $user = $request->user();
        $token = $user->createToken('frontend')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user], 200);
    }

    public function me(Request $request)
    {
        return $request->user();
    }

    // === 🔥 СМЕНА ПАРОЛЯ ===
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Пароль успешно изменен']);
    }

    // === 🔥 2FA ЛОГИКА ===

    public function enableTwoFactor(Request $request)
    {
        $user = $request->user();
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = Str::random(10) . '-' . Str::random(10);
        }

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(['message' => '2FA инициализирована']);
    }

    // 🔥 ИСПРАВЛЕННЫЙ МЕТОД (ГЕНЕРАЦИЯ SVG ВРУЧНУЮ)
    public function getTwoFactorQrCode(Request $request)
    {
        $user = $request->user();

        if (!$user->two_factor_secret) {
            return response()->json(['message' => '2FA не включена'], 400);
        }

        $google2fa = new Google2FA();
        $secret = decrypt($user->two_factor_secret);

        // 1. Получаем ссылку-строку для приложения
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // 2. Генерируем SVG картинку через BaconQrCode
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $svg = $writer->writeString($qrCodeUrl);

        return response()->json(['svg' => $svg]);
    }

    public function getTwoFactorSecretKey(Request $request)
    {
        $user = $request->user();
        if (!$user->two_factor_secret) return response()->json(['message' => '2FA не включена'], 400);

        return response()->json(['secretKey' => decrypt($user->two_factor_secret)]);
    }

    public function confirmTwoFactor(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $user = $request->user();
        if (!$user->two_factor_secret) return response()->json(['message' => '2FA не инициализирована'], 400);

        $google2fa = new Google2FA();
        $secret = decrypt($user->two_factor_secret);

        if ($google2fa->verifyKey($secret, $request->code)) {
            $user->forceFill(['two_factor_confirmed_at' => now()])->save();
            return response()->json(['message' => '2FA успешно активирована']);
        }

        return response()->json(['message' => 'Неверный код'], 422);
    }

    // 🔥 ОБНОВЛЕННЫЙ МЕТОД: Требует код для отключения
    public function disableTwoFactor(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            return response()->json(['message' => '2FA уже выключена'], 400);
        }

        // Проверяем код перед отключением
        $google2fa = new Google2FA();
        $secret = decrypt($user->two_factor_secret);

        $valid = $google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return response()->json(['message' => 'Неверный код подтверждения'], 422);
        }

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(['message' => '2FA успешно отключена']);
    }

    public function getTwoFactorRecoveryCodes(Request $request)
    {
        $user = $request->user();
        if (!$user->two_factor_recovery_codes) return response()->json([]);
        return response()->json(json_decode(decrypt($user->two_factor_recovery_codes), true));
    }

    // === АВАТАРКИ ===
    public function updateAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048']);
        $user = $request->user();
        $disk = 'local'; 
        $folder = 'public/avatars';

        try {
            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    $oldFilename = basename($user->avatar);
                    $oldPath = $folder . '/' . $oldFilename;
                    if (Storage::disk($disk)->exists($oldPath)) Storage::disk($disk)->delete($oldPath);
                }
                $path = $request->file('avatar')->store($folder, $disk);
                $url = url("/api/avatar/" . basename($path));
                $user->avatar = $url; 
                $user->save();
                return response()->json(['message' => 'Обновлено', 'avatar_url' => $url, 'user' => $user]);
            }
        } catch (\Exception $e) {
            Log::error('Avatar error: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка сохранения'], 500);
        }
        return response()->json(['message' => 'Файл не загружен'], 400);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();
        $folder = 'public/avatars';
        if ($user->avatar) {
            $filename = basename($user->avatar);
            if (Storage::disk('local')->exists($folder . '/' . $filename)) {
                Storage::disk('local')->delete($folder . '/' . $filename);
            }
            $user->avatar = null;
            $user->save();
        }
        return response()->json(['message' => 'Удалено', 'user' => $user]);
    }

    public function getAvatar($filename)
    {
        $path = 'public/avatars/' . $filename;
        if (!Storage::disk('local')->exists($path)) abort(404);
        return response()->file(Storage::disk('local')->path($path));
    }
}