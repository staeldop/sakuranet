<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Публичный метод: Получить все товары
    public function index()
    {
        return Product::all();
    }

    // Админ метод: Создать товар
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'country' => 'nullable|string',
            'game_type' => 'nullable|string',
            'price' => 'required|numeric',
            'attributes' => 'array' // Ожидаем массив характеристик
        ]);

        return Product::create($validated);
    }

    // Админ метод: Удалить товар
    public function destroy($id)
    {
        Product::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}