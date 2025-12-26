<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PterodactylService
{
    protected $url;
    protected $apiKey;

    public function __construct()
    {
        $this->url = rtrim(config('services.pterodactyl.url'), '/');
        $this->apiKey = config('services.pterodactyl.key');
    }

    /**
     * Базовый метод
     */
    protected function request()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])
        ->withoutVerifying() // Игнорируем проблемы с SSL
        ->baseUrl($this->url . '/api/application');
    }

    // --- ОБЩИЕ МЕТОДЫ С ПРОВЕРКОЙ ОШИБОК ---

    public function findUserByEmail($email)
    {
        $response = $this->request()->get("/users?filter[email]={$email}");
        if ($response->successful() && count($response->json()['data'] ?? []) > 0) {
            return $response->json()['data'][0]['attributes'];
        }
        return null;
    }

    public function createUser($userData)
    {
        $response = $this->request()->post('/users', $userData);
        if ($response->failed()) throw new \Exception('Create User Error: ' . $response->body());
        return $response->json();
    }

    public function createServer($serverData)
    {
        $response = $this->request()->post('/servers', $serverData);
        if ($response->failed()) throw new \Exception('Create Server Error: ' . $response->body());
        return $response->json();
    }

    public function getAllServers()
    {
        return $this->request()->get('/servers')->json();
    }

    public function getServerDetail($serverId)
    {
        $response = $this->request()->get("/servers/{$serverId}");
        if ($response->failed()) throw new \Exception('Get Server Error: ' . $response->body());
        return $response->json();
    }

    // --- УПРАВЛЕНИЕ ЯДРАМИ (Startup) ---

    public function updateServerStartup($serverId, array $data)
    {
        // 🔥 ВАЖНО: Если тут будет ошибка валидации (422), мы её поймаем
        $response = $this->request()->patch("/servers/{$serverId}/startup", $data);
        
        if ($response->failed()) {
            // Возвращаем текст ошибки от панели, чтобы показать юзеру
            $error = $response->json()['errors'][0]['detail'] ?? $response->body();
            throw new \Exception('Ошибка смены параметров запуска: ' . $error);
        }
        
        return $response->json();
    }

    public function reinstallServer($serverId)
    {
        $response = $this->request()->post("/servers/{$serverId}/reinstall");
        
        if ($response->failed()) {
            throw new \Exception('Ошибка запуска переустановки: ' . $response->body());
        }
        
        return $response->json();
    }

    // --- ЯЙЦА И ГНЕЗДА ---

    public function getNestsWithEggs()
    {
        return $this->request()->get('/nests?include=eggs')->json();
    }

    public function getEgg($nestId, $eggId)
    {
        $response = $this->request()->get("/nests/{$nestId}/eggs/{$eggId}?include=variables");
        if ($response->failed()) return null;
        return $response->json()['attributes'] ?? null;
    }

    // --- IMPORTER HELPERS ---

    public function getNests()
    {
        return $this->request()->get('/nests')->json()['data'] ?? [];
    }

    public function findOrCreateNest($name, $description = '')
    {
        $nests = $this->getNests();
        foreach ($nests as $nest) {
            if (strtolower($nest['attributes']['name']) === strtolower($name)) {
                return $nest['attributes']['id'];
            }
        }
        $response = $this->request()->post('/nests', [
            'name' => $name, 'description' => $description, 'identifier' => Str::slug($name) . '_' . rand(100, 999),
        ]);
        return $response->json()['attributes']['id'];
    }

    public function importEgg($nestId, $eggData)
    {
        try {
            $this->request()->post("/nests/{$nestId}/eggs", $eggData);
            return "Created: " . $eggData['name'];
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
}