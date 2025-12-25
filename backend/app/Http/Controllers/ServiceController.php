<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Product;
use App\Services\PterodactylService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    protected $pterodactyl;

    public function __construct(PterodactylService $pterodactyl)
    {
        $this->pterodactyl = $pterodactyl;
    }

    public function index(Request $request)
    {
        return $request->user()->services()->with('product')->latest()->get();
    }

    public function show(Request $request, $id)
    {
        return $request->user()->services()->with('product')->findOrFail($id);
    }

    // 🔥 ПОКУПКА УСЛУГИ
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'name'       => 'required|string|min:3|max:50',
            'period'     => 'required|integer|in:1,3,6,12',
            'nest_id'    => 'nullable|integer',
            'egg_id'     => 'nullable|integer',
            'docker_image' => 'nullable|string',
            'environment' => 'nullable|array' // Разрешаем кастомные переменные (если нужно)
        ]);

        $user = $request->user();
        $product = Product::findOrFail($request->product_id);

        // 1. Расчет цены
        $months = $request->period;
        $discount = 0;
        if ($months >= 3) $discount = 0.05;
        if ($months >= 6) $discount = 0.10;
        if ($months >= 12) $discount = 0.20;

        $totalPrice = ($product->price * $months) * (1 - $discount);

        if ($user->balance < $totalPrice) {
             return response()->json(['message' => 'Недостаточно средств. Пополните баланс.'], 402);
        }

        return DB::transaction(function () use ($user, $product, $request, $totalPrice, $months) {
            
            // Списываем баланс
            $user->decrement('balance', $totalPrice);

            // --- 2. PTERODACTYL USER ---
            $pteroUser = $this->pterodactyl->findUserByEmail($user->email);
            $pteroUserId = null;
            $newPassword = null; 

            if ($pteroUser) {
                $pteroUserId = $pteroUser['id'];
                // Синхронизируем ID если он отличается
                if ($user->pterodactyl_id !== $pteroUserId) {
                    $user->update(['pterodactyl_id' => $pteroUserId]);
                }
            } else {
                $newPassword = Str::random(12) . '!1a'; 
                // Очистка имени от спецсимволов для Pterodactyl
                $nameParts = explode(' ', $user->name, 2);
                $firstName = preg_replace('/[^a-zA-Z0-9а-яА-Я._-]/u', '', $nameParts[0]) ?: 'Client';
                $lastName = isset($nameParts[1]) ? preg_replace('/[^a-zA-Z0-9а-яА-Я._-]/u', '', $nameParts[1]) : 'User';

                $newPteroUser = $this->pterodactyl->createUser([
                    'email'      => $user->email,
                    'username'   => 'client_' . $user->id . '_' . Str::random(3),
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'password'   => $newPassword
                ]);

                if (!isset($newPteroUser['attributes']['id'])) {
                    $msg = 'Ошибка создания юзера.';
                    if (isset($newPteroUser['errors'][0]['detail'])) $msg .= ' ' . $newPteroUser['errors'][0]['detail'];
                    throw new \Exception($msg);
                }
                $pteroUserId = $newPteroUser['attributes']['id'];
                
                // Сохраняем пароль во временное поле (если оно есть) или просто ID
                // Лучше не хранить пароль в открытом виде долго, но для выдачи клиенту нужно
                $user->update([
                    'pterodactyl_id' => $pteroUserId, 
                    'ptero_password' => $newPassword
                ]);
            }

            // --- 3. ПОДГОТОВКА ДАННЫХ СЕРВЕРА ---
            // Если в запросе пришли nest/egg, используем их, иначе дефолтные из товара
            $nestId = $request->nest_id ? (int)$request->nest_id : (int)$product->ptero_nest_id;
            $eggId  = $request->egg_id ? (int)$request->egg_id : (int)$product->ptero_egg_id;
            
            if (!$nestId || !$eggId) {
                // Если нигде не указано, берем Minecraft Paper (пример) или кидаем ошибку
                throw new \Exception('Не выбрано ядро (Egg ID). Обратитесь к администратору.');
            }

            // 🔥 ЗАПРАШИВАЕМ ДЕТАЛИ ЯЙЦА (ВМЕСТЕ С ПЕРЕМЕННЫМИ)
            $eggData = $this->pterodactyl->getEgg($nestId, $eggId);
            if (!$eggData) throw new \Exception('Ядро не найдено в панели.');

            // Определяем Image и Startup
            $image = $request->docker_image ?: $eggData['docker_image'];
            $startup = $product->ptero_startup ?: $eggData['startup'];

            // 🔥 СОБИРАЕМ ПЕРЕМЕННЫЕ (Environment)
            // Берем их из самого яйца (отношения 'variables')
            $environment = [];
            
            // Если в API прилетел список переменных relationships
            if (isset($eggData['relationships']['variables']['data'])) {
                foreach ($eggData['relationships']['variables']['data'] as $var) {
                    $envCode = $var['attributes']['env_variable'];
                    $defaultVal = $var['attributes']['default_value'];
                    
                    // Если пользователь/фронт прислал свое значение для этой переменной - берем его
                    if ($request->has('environment') && isset($request->environment[$envCode])) {
                        $environment[$envCode] = $request->environment[$envCode];
                    } else {
                        $environment[$envCode] = $defaultVal;
                    }
                }
            }

            $serverData = [
                'name' => $request->name,
                'user' => (int) $pteroUserId,
                'nest' => $nestId,
                'egg'  => $eggId,
                'docker_image' => $image,
                'startup' => $startup,
                'environment' => $environment, // 🔥 Теперь тут полные переменные
                'limits' => [
                    'memory' => (int) ($product->memory ?: 1024),
                    'swap'   => 0,
                    'disk'   => (int) ($product->disk ?: 5000),
                    'io'     => 500,
                    'cpu'    => (int) ($product->cpu ?: 100),
                ],
                'feature_limits' => [
                    'databases'   => (int) $product->databases,
                    'backups'     => (int) $product->backups,
                    'allocations' => (int) $product->allocations
                ],
                'deploy' => [
                    'locations' => [1], // ID локации, желательно вынести в конфиг или товар
                    'dedicated_ip' => false,
                    'port_range' => []
                ],
            ];

            try {
                $pteroServer = $this->pterodactyl->createServer($serverData);
            } catch (\Exception $e) {
                Log::error('Ptero API Error: ' . $e->getMessage());
                throw new \Exception('Сбой API панели при создании сервера: ' . $e->getMessage());
            }

            if (isset($pteroServer['errors'])) {
                Log::error('Ptero Validation Error', $pteroServer);
                $errDetail = $pteroServer['errors'][0]['detail'] ?? 'Неизвестная ошибка валидации Pterodactyl';
                throw new \Exception($errDetail);
            }

            $attributes = $pteroServer['attributes'];

            // --- 4. СОХРАНЕНИЕ ---
            $service = Service::create([
                'user_id'       => $user->id,
                'product_id'    => $product->id,
                'name'          => $attributes['name'],
                'identifier'    => $attributes['identifier'],
                'ptero_id'      => $attributes['id'], // Используем поле ptero_id (проверь миграцию!)
                'ip_address'    => 'Установка...',
                'core'          => $eggData['name'] ?? ('Egg #' . $eggId), 
                'status'        => 'active',
                'price_monthly' => $product->price,
                'expires_at'    => now()->addMonths($months),
            ]);

            return response()->json([
                'message' => 'Сервер успешно создан!',
                'service' => $service,
                'new_user_password' => $newPassword 
            ]);
        });
    }

    public function destroy(Request $request, $id)
    {
        $service = $request->user()->services()->findOrFail($id);
        
        // Тут можно добавить $this->pterodactyl->deleteServer($service->ptero_id);
        
        $service->delete();
        return response()->json(['message' => 'Услуга удалена']);
    }
}