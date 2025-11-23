<?php

namespace App\Http\Controllers;

use App\Models\Product; // <--- ОБЯЗАТЕЛЬНО
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
            'specs' => 'array' // 🔥 ПЕРЕИМЕНОВАЛИ
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
            'specs' => 'array' // 🔥 ПЕРЕИМЕНОВАЛИ
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