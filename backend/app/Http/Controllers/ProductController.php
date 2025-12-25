<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Получить все товары
    public function index()
    {
        return Product::all();
    }

    // Создать товар
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'country' => 'nullable|string',
            'game_type' => 'nullable|string',
            'price' => 'required|numeric',
            'specs' => 'array',
            
            // Настройки Pterodactyl
            'ptero_nest_id' => 'nullable|integer',
            'ptero_egg_id' => 'nullable|integer',
            'ptero_docker_image' => 'nullable|string',
            'ptero_startup' => 'nullable|string',
            'memory_mb' => 'nullable|integer',
            'disk_mb' => 'nullable|integer',
            'cpu_limit' => 'nullable|integer',
            'databases' => 'nullable|integer',
            'backups' => 'nullable|integer',
        ]);

        return Product::create($validated);
    }

    // Обновить товар
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'country' => 'nullable|string',
            'game_type' => 'nullable|string',
            'price' => 'required|numeric',
            'specs' => 'array',
            
            // Настройки Pterodactyl
            'ptero_nest_id' => 'nullable|integer',
            'ptero_egg_id' => 'nullable|integer',
            'ptero_docker_image' => 'nullable|string',
            'ptero_startup' => 'nullable|string',
            'memory_mb' => 'nullable|integer',
            'disk_mb' => 'nullable|integer',
            'cpu_limit' => 'nullable|integer',
            'databases' => 'nullable|integer',
            'backups' => 'nullable|integer',
        ]);

        $product->update($validated);

        return $product;
    }

    // Удалить товар
    public function destroy($id)
    {
        Product::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}