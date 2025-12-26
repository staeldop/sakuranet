<?php

// Закинь этот файл в папку backend/ твоего сайта и запусти: php debug_ptero.php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n🔍 ДИАГНОСТИКА ПОДКЛЮЧЕНИЯ К PTERODACTYL...\n";

// 1. Проверяем конфиг
$url = config('services.pterodactyl.url');
$key = config('services.pterodactyl.key');

echo "URL: " . ($url ? $url : "❌ ОТСУТСТВУЕТ В .env") . "\n";
echo "KEY: " . ($key ? substr($key, 0, 10) . "..." : "❌ ОТСУТСТВУЕТ В .env") . "\n";

if (!$url || !$key) {
    die("\n⚠️  ОШИБКА: Проверь файл .env в папке backend! Там нет PTERODACTYL_URL или PTERODACTYL_API_KEY.\n");
}

// 2. Делаем тестовый запрос
echo "\n📡 Отправка запроса к API...\n";

try {
    // Эмулируем запрос из PterodactylService
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $key,
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
    ])->get(rtrim($url, '/') . '/api/application/nests?include=eggs');

    echo "Статус ответа: " . $response->status() . "\n";

    if ($response->successful()) {
        $data = $response->json();
        $count = count($data['data'] ?? []);
        echo "✅ УСПЕШНО! Получено гнезд: $count\n";
        
        // Проверяем первое яйцо на наличие описания
        if ($count > 0) {
            $egg = $data['data'][0]['attributes']['relationships']['eggs']['data'][0]['attributes'] ?? null;
            if ($egg) {
                echo "Пример описания яйца: " . substr($egg['description'], 0, 50) . "...\n";
            }
        }
    } else {
        echo "❌ ОШИБКА API:\n";
        print_r($response->json());
    }

} catch (\Exception $e) {
    echo "❌ КРИТИЧЕСКАЯ ОШИБКА: " . $e->getMessage() . "\n";
}
echo "\n";