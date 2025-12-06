<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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
     * 1. Найти пользователя по Email (чтобы не дублировать)
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
        // userData должна содержать: email, username, first_name, last_name
        return $this->request()->post('/users', $userData)->json();
    }

    /**
     * 3. Создать сервер
     */
    public function createServer($serverData)
    {
        /*
         serverData сложная структура, Pterodactyl требует:
         - name
         - user (id пользователя в птеродактиле)
         - egg (id яйца, например Minecraft)
         - docker_image
         - startup (команда запуска)
         - environment (переменные)
         - limits (memory, swap, disk, io, cpu)
         - feature_limits (databases, backups)
         - allocation (default port)
        */
        return $this->request()->post('/servers', $serverData)->json();
    }
}