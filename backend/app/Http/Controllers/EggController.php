<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PterodactylService;
use Illuminate\Support\Facades\Cache;

class EggController extends Controller
{
    /**
     * Возвращает дерево Nests -> Eggs
     * КЕШ ВКЛЮЧЕН (60 минут)
     */
    public function index(PterodactylService $ptero)
    {
        // Кешируем результат на 1 час (3600 секунд).
        // Если данные в панели изменятся, кеш сбросится сам через час, 
        // либо вручную командой php artisan cache:clear
        $data = Cache::remember('ptero_nests_eggs_tree', 3600, function () use ($ptero) {
            return $ptero->getNestsWithEggs();
        });

        return response()->json($data);
    }
}