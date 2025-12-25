<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PterodactylService;
use Illuminate\Support\Facades\Cache;

class EggController extends Controller
{
    /**
     * Возвращает дерево Nests -> Eggs
     */
    public function index(PterodactylService $ptero)
    {
        // Кешируем результат на 60 минут, так как список ядер меняется редко.
        // Это значительно ускорит загрузку страницы заказа.
        $data = Cache::remember('ptero_nests_eggs_tree', 3600, function () use ($ptero) {
            return $ptero->getNestsWithEggs();
        });

        return response()->json($data);
    }
}