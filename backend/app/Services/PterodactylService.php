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
        // Убираем слеш в конце, если он есть
        $this->url = rtrim(config('services.pterodactyl.url'), '/');
        $this->apiKey = config('services.pterodactyl.key');
    }

    /**
     * Базовый метод для запросов
     */
    protected function request()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ])->baseUrl($this->url . '/api/application');
    }

    /**
     * 1. Найти пользователя по Email
     */
    public function findUserByEmail($email)
    {
        $response = $this->request()->get("/users?filter[email]={$email}");
        
        if ($response->successful() && count($response->json()['data']) > 0) {
            return $response->json()['data'][0]['attributes'];
        }

        return null;
    }

    /**
     * 2. Создать пользователя Pterodactyl
     */
    public function createUser($userData)
    {
        return $this->request()->post('/users', $userData)->json();
    }

    /**
     * 3. Создать сервер
     */
    public function createServer($serverData)
    {
        return $this->request()->post('/servers', $serverData)->json();
    }

    /**
     * 4. Получить список всех серверов (для админки)
     */
    public function getAllServers()
    {
        return $this->request()->get('/servers')->json();
    }

    /**
     * 5. Получить детали сервера по ID
     */
    public function getServerDetail($serverId)
    {
        return $this->request()->get("/servers/{$serverId}")->json();
    }

    /**
     * 🔥 ПОЛУЧИТЬ ВСЕ ГНЕЗДА И ЯЙЦА (ДЛЯ ДЕРЕВА ВЫБОРА)
     */
    public function getNestsWithEggs()
    {
        // include=eggs позволяет сразу получить вложенные яйца
        return $this->request()->get('/nests?include=eggs')->json();
    }

    /**
     * 🔥 НОВЫЙ МЕТОД: Получить детали конкретного яйца
     * Используется при покупке, чтобы узнать docker_image, startup и переменные
     */
    public function getEgg($nestId, $eggId)
    {
        // ВАЖНО: добавляем ?include=variables, иначе переменные окружения не придут!
        $response = $this->request()->get("/nests/{$nestId}/eggs/{$eggId}?include=variables");
        return $response->json()['attributes'] ?? null;
    }

    // ==========================================
    // МЕТОДЫ ДЛЯ СКРИПТА ИМПОРТА
    // ==========================================

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
            'name' => $name,
            'description' => $description,
            'identifier' => Str::slug($name) . '_' . rand(100, 999),
        ]);

        return $response->json()['attributes']['id'];
    }

    public function importEgg($nestId, $eggData)
    {
        $existingEggs = $this->request()->get("/nests/{$nestId}/eggs")->json()['data'] ?? [];
        
        $eggId = null;
        foreach ($existingEggs as $egg) {
            if ($egg['attributes']['name'] === $eggData['name']) {
                $eggId = $egg['attributes']['id'];
                break;
            }
        }

        if ($eggId) {
            return "Skipped (Already exists, ID: $eggId)";
        } else {
            try {
                $this->request()->post("/nests/{$nestId}/eggs", $eggData);
                return "Created new egg: " . $eggData['name'];
            } catch (\Exception $e) {
                return "Error creating: " . $e->getMessage();
            }
        }
    }
}