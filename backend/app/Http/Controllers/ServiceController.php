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

    // 🔥 СМЕНА ЯДРА
    public function changeCore(Request $request, $id)
    {
        $request->validate([
            'nest_id' => 'required|integer',
            'egg_id'  => 'required|integer',
        ]);

        $service = $request->user()->services()->findOrFail($id);
        
        // Проверка: есть ли ID сервера в базе
        if (empty($service->ptero_server_id)) {
            return response()->json(['message' => 'Ошибка: ID сервера не найден в базе данных (ptero_server_id is null). Пересоздайте сервис.'], 500);
        }

        try {
            $eggData = $this->pterodactyl->getEgg($request->nest_id, $request->egg_id);
            if (!$eggData) {
                return response()->json(['message' => 'Ядро не найдено в панели (404)'], 404);
            }

            // Умный сбор переменных
            $environment = [];
            if (isset($eggData['relationships']['variables']['data'])) {
                foreach ($eggData['relationships']['variables']['data'] as $var) {
                    $attr = $var['attributes'];
                    $key = $attr['env_variable'];
                    
                    if (!is_null($attr['default_value']) && $attr['default_value'] !== '') {
                        $val = $attr['default_value'];
                    } else {
                        $rules = $attr['rules'] ?? '';
                        if (str_contains($rules, 'required')) {
                            if (str_contains($rules, 'numeric') || str_contains($rules, 'integer')) {
                                $val = '0';
                            } elseif (str_contains($rules, 'boolean')) {
                                $val = '0'; 
                            } else {
                                $val = 'changeme';
                            }
                        } else {
                            $val = '';
                        }
                    }
                    $environment[$key] = (string) $val;
                }
            }

            // 🔥 ИСПРАВЛЕНО: используем ptero_server_id
            $this->pterodactyl->updateServerStartup($service->ptero_server_id, [
                'egg' => (int) $request->egg_id,
                'image' => $eggData['docker_image'],
                'startup' => $eggData['startup'],
                'environment' => $environment,
                'skip_scripts' => false
            ]);

            // 🔥 ИСПРАВЛЕНО: используем ptero_server_id
            $this->pterodactyl->reinstallServer($service->ptero_server_id);

            $service->update([
                'core' => $eggData['name'],
                'status' => 'installing'
            ]);

            return response()->json(['message' => 'Ядро успешно изменено! Идет переустановка.']);

        } catch (\Exception $e) {
            Log::error('Core change error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
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
            'environment' => 'nullable|array'
        ]);

        $user = $request->user();
        $product = Product::findOrFail($request->product_id);

        $months = $request->period;
        $discount = 0;
        if ($months >= 3) $discount = 0.05;
        if ($months >= 6) $discount = 0.10;
        if ($months >= 12) $discount = 0.20;

        $totalPrice = ($product->price * $months) * (1 - $discount);

        if ($user->balance < $totalPrice) {
             return response()->json(['message' => 'Недостаточно средств.'], 402);
        }

        return DB::transaction(function () use ($user, $product, $request, $totalPrice, $months) {
            
            $user->decrement('balance', $totalPrice);

            // --- USER ---
            try {
                $pteroUser = $this->pterodactyl->findUserByEmail($user->email);
                if ($pteroUser) {
                    $pteroUserId = $pteroUser['id'];
                    $newPassword = null;
                    if ($user->pterodactyl_id !== $pteroUserId) $user->update(['pterodactyl_id' => $pteroUserId]);
                } else {
                    $newPassword = Str::random(12) . '!1a'; 
                    $nameParts = explode(' ', $user->name, 2);
                    $newPteroUser = $this->pterodactyl->createUser([
                        'email'      => $user->email,
                        'username'   => 'client_' . $user->id . '_' . Str::random(3),
                        'first_name' => preg_replace('/[^a-zA-Z0-9._-]/', '', $nameParts[0]) ?: 'Client',
                        'last_name'  => isset($nameParts[1]) ? preg_replace('/[^a-zA-Z0-9._-]/', '', $nameParts[1]) : 'User',
                        'password'   => $newPassword
                    ]);
                    $pteroUserId = $newPteroUser['attributes']['id'];
                    $user->update(['pterodactyl_id' => $pteroUserId, 'ptero_password' => $newPassword]);
                }
            } catch (\Exception $e) {
                throw new \Exception('Ошибка пользователя: ' . $e->getMessage());
            }

            // --- SERVER ---
            $nestId = $request->nest_id ?: $product->ptero_nest_id;
            $eggId  = $request->egg_id ?: $product->ptero_egg_id;
            if (!$nestId || !$eggId) throw new \Exception('Не выбрано ядро.');

            $eggData = $this->pterodactyl->getEgg($nestId, $eggId);
            if (!$eggData) throw new \Exception('Ядро не найдено.');

            $environment = [];
            if (isset($eggData['relationships']['variables']['data'])) {
                foreach ($eggData['relationships']['variables']['data'] as $var) {
                    $attr = $var['attributes'];
                    $val  = $attr['default_value'] ?? '';
                    $sentEnv = $request->input('environment', []);
                    $environment[$attr['env_variable']] = (string)($sentEnv[$attr['env_variable']] ?? $val);
                }
            }

            $locationId = 1; 

            $serverData = [
                'name' => $request->name,
                'user' => (int) $pteroUserId,
                'nest' => (int) $nestId,
                'egg'  => (int) $eggId,
                'docker_image' => $request->docker_image ?: $eggData['docker_image'],
                'startup' => $eggData['startup'],
                'environment' => $environment,
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
                    'locations' => [$locationId], 
                    'dedicated_ip' => false,
                    'port_range' => []
                ],
            ];

            try {
                $pteroServer = $this->pterodactyl->createServer($serverData);
                $attributes = $pteroServer['attributes'];
            } catch (\Exception $e) {
                throw new \Exception('Сбой создания: ' . $e->getMessage());
            }

            // 🔥 ИСПРАВЛЕНО: поле ptero_server_id
            $service = Service::create([
                'user_id'       => $user->id,
                'product_id'    => $product->id,
                'name'          => $attributes['name'],
                'identifier'    => $attributes['identifier'],
                'ptero_server_id' => $attributes['id'], // <--- ВОТ ЗДЕСЬ БЫЛА ОШИБКА
                'ip_address'    => 'Установка...',
                'core'          => $eggData['name'], 
                'status'        => 'installing',
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
        
        // Для удаления сервера в панели раскомментируй:
        // if ($service->ptero_server_id) {
        //     try { $this->pterodactyl->deleteServer($service->ptero_server_id); } catch (\Exception $e) {}
        // }
        
        $service->delete();
        return response()->json(['message' => 'Услуга удалена']);
    }
}