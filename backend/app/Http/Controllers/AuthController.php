<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\KnownDevice;
use App\Mail\AuthCodeMail; // ✅ Подключаем наш умный класс письма
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
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

            // Сразу запоминаем устройство при регистрации
            KnownDevice::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'last_login_at' => now(),
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

    // === ЛОГИН С ЗАЩИТОЙ (DEVICE VERIFICATION) ===
    public function login(Request $request)
    {
        // 1. Валидация
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'code' => ['nullable', 'string'],
        ]);

        // 2. Ищем юзера
        $user = User::where('email', $validated['email'])->first();

        // 3. Проверяем пароль
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Неверный email или пароль.'], 401);
        }

        // 4. Проверяем устройство
        $ip = $request->ip();
        $userAgent = $request->header('User-Agent');

        $isKnownDevice = KnownDevice::where('user_id', $user->id)
            ->where('ip_address', $ip)
            ->exists();

        // === ЕСЛИ УСТРОЙСТВО НОВОЕ (ИЛИ ТРЕБУЕТСЯ 2FA) ===
        if (!$isKnownDevice) {
            
            // СЦЕНАРИЙ А: У юзера включена Google 2FA
            if ($user->two_factor_secret) {
                if (empty($validated['code'])) {
                    return response()->json([
                        'step' => '2fa_required',
                        'message' => 'Вход с нового устройства. Введите код из Google Authenticator.'
                    ], 403); 
                }

                $google2fa = new Google2FA();
                $valid = $google2fa->verifyKey(decrypt($user->two_factor_secret), $validated['code']);

                if (!$valid) {
                    return response()->json(['message' => 'Неверный код Google Authenticator'], 422);
                }
            } 
            // СЦЕНАРИЙ Б: Google 2FA нет, шлем Email-код
            else {
                $cacheKey = 'login_code_' . $user->id . '_' . $ip;

                // Если код не прислали — генерируем и шлем
                if (empty($validated['code'])) {
                    $code = rand(100000, 999999);
                    Cache::put($cacheKey, $code, now()->addMinutes(10));

                    // ⚡ Данные для письма (Тип: login)
                    $details = [
                        'ip' => $ip,
                        'browser' => $userAgent,
                        'type' => 'login' 
                    ];

                    try {
                        Mail::to($user->email)->send(new AuthCodeMail($code, $details));
                    } catch (\Exception $e) {
                        Log::error('Mail error: ' . $e->getMessage());
                        return response()->json(['message' => 'Ошибка отправки письма. Свяжитесь с поддержкой.'], 500);
                    }

                    return response()->json([
                        'step' => 'email_code_required',
                        'message' => 'Новое устройство. Мы отправили код подтверждения на вашу почту.'
                    ], 403);
                }

                // Если код прислали — проверяем кэш
                $cachedCode = Cache::get($cacheKey);
                if (!$cachedCode || $cachedCode != $validated['code']) {
                    return response()->json(['message' => 'Неверный или устаревший код из письма'], 422);
                }
                
                Cache::forget($cacheKey);
            }

            // Если прошли проверки — запоминаем устройство
            KnownDevice::updateOrCreate(
                ['user_id' => $user->id, 'ip_address' => $ip],
                ['user_agent' => $userAgent, 'last_login_at' => now()]
            );
        } else {
            // Устройство знакомое — просто обновляем время
            KnownDevice::where('user_id', $user->id)
                ->where('ip_address', $ip)
                ->update(['last_login_at' => now()]);
        }

        // 5. Выдаем токен
        $token = $user->createToken('frontend')->plainTextToken;

        return response()->json([
            'token' => $token, 
            'user' => $user,
            'message' => 'Успешный вход'
        ], 200);
    }

    public function me(Request $request)
    {
        return $request->user();
    }

    // === СМЕНА ПАРОЛЯ С EMAIL КОДОМ (В НАСТРОЙКАХ) ===

    public function sendPasswordCode(Request $request)
    {
        $user = $request->user();
        $code = rand(100000, 999999);
        Cache::put('password_code_' . $user->id, $code, now()->addMinutes(15));

        // ⚡ Данные для письма (Тип: update)
        $details = [
            'ip' => $request->ip(),
            'browser' => $request->header('User-Agent'),
            'type' => 'update'
        ];

        Mail::to($user->email)->send(new AuthCodeMail($code, $details));

        return response()->json(['message' => 'Код отправлен на ваш Email']);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'current_password.current_password' => 'Текущий пароль неверен.',
            'code.required' => 'Введите код из письма.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        $user = $request->user();
        $cachedCode = Cache::get('password_code_' . $user->id);

        if (!$cachedCode || $cachedCode != $request->code) {
            return response()->json([
                'message' => 'Неверный код подтверждения.',
                'errors' => ['code' => ['Неверный код']]
            ], 422);
        }

        $user->update(['password' => Hash::make($validated['password'])]);
        Cache::forget('password_code_' . $user->id);

        return response()->json(['message' => 'Пароль успешно изменен']);
    }

    // === ВОССТАНОВЛЕНИЕ ПАРОЛЯ (ДЛЯ ГОСТЕЙ) ===

    // Шаг 1: Запрос кода
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Пользователь с таким Email не найден'], 404);
        }

        $code = rand(100000, 999999);
        Cache::put('reset_code_' . $user->email, $code, now()->addMinutes(15));

        // ⚡ Данные для письма (Тип: reset)
        $details = [
            'ip' => $request->ip(),
            'browser' => $request->header('User-Agent'),
            'type' => 'reset'
        ];

        try {
            Mail::to($user->email)->send(new AuthCodeMail($code, $details));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка отправки письма'], 500);
        }

        return response()->json(['message' => 'Код отправлен на ваш Email']);
    }

    // Шаг 2: Смена пароля
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'code.required' => 'Введите код из письма',
            'password.confirmed' => 'Пароли не совпадают'
        ]);

        $cachedCode = Cache::get('reset_code_' . $validated['email']);

        if (!$cachedCode || $cachedCode != $validated['code']) {
            return response()->json(['message' => 'Неверный или просроченный код'], 422);
        }

        $user = User::where('email', $validated['email'])->first();
        
        if (!$user) {
            return response()->json(['message' => 'Пользователь не найден'], 404);
        }

        $user->update(['password' => Hash::make($validated['password'])]);
        
        Cache::forget('reset_code_' . $validated['email']);
        $user->tokens()->delete(); 

        return response()->json(['message' => 'Пароль успешно изменен. Теперь вы можете войти.']);
    }

    // === 2FA ЛОГИКА ===

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

    public function getTwoFactorQrCode(Request $request)
    {
        $user = $request->user();
        if (!$user->two_factor_secret) return response()->json(['message' => '2FA не включена'], 400);

        $google2fa = new Google2FA();
        $secret = decrypt($user->two_factor_secret);

        $qrCodeUrl = $google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);

        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
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
            return response()->json(['message' => '2FA активирована']);
        }

        return response()->json(['message' => 'Неверный код'], 422);
    }

    public function disableTwoFactor(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $user = $request->user();

        if (!$user->two_factor_secret) return response()->json(['message' => '2FA уже выключена'], 400);

        $google2fa = new Google2FA();
        $secret = decrypt($user->two_factor_secret);

        if (!$google2fa->verifyKey($secret, $request->code)) {
            return response()->json(['message' => 'Неверный код'], 422);
        }

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(['message' => '2FA отключена']);
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