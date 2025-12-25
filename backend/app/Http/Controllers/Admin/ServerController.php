<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PterodactylService;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    protected $pterodactyl;

    public function __construct(PterodactylService $service)
    {
        $this->pterodactyl = $service;
    }

    public function index()
    {
        try {
            // Пытаемся получить список серверов
            $data = $this->pterodactyl->getAllServers();
            
            // Проверяем, не вернул ли Pterodactyl ошибку в JSON
            if (isset($data['errors'])) {
                return response()->json([
                    'error' => 'Pterodactyl API Error',
                    'details' => $data['errors']
                ], 400);
            }

            return response()->json($data);

        } catch (\Exception $e) {
            // Если упало, возвращаем текст ошибки
            return response()->json([
                'error' => 'Server Error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}