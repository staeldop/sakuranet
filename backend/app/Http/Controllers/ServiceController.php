<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    // Показать все мои услуги
    public function index(Request $request)
    {
        return $request->user()->services()->with('product')->latest()->get();
    }

    // Показать одну конкретную услугу (для страницы управления)
    public function show(Request $request, $id)
    {
        return $request->user()->services()->with('product')->findOrFail($id);
    }

    // Покупка услуги
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|min:3|max:50',
            'core' => 'required|string',
            'period' => 'required|integer|in:1,3,6,12'
        ]);

        $user = $request->user();
        $product = Product::findOrFail($request->product_id);

        // 1. Расчет цены
        $basePrice = $product->price;
        $months = $request->period;
        $discount = 0;
        
        if ($months >= 3) $discount = 0.05;
        if ($months >= 6) $discount = 0.10;
        if ($months >= 12) $discount = 0.20;

        $totalPrice = ($basePrice * $months) * (1 - $discount);

        // 2. Списание баланса (раскомментируй, когда будет готова система баланса)
        /*
        if ($user->balance < $totalPrice) {
            return response()->json(['message' => 'Недостаточно средств на балансе'], 402);
        }
        $user->decrement('balance', $totalPrice);
        */

        // 3. Создание услуги
        $service = Service::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'name' => $request->name,
            'identifier' => 'srv-' . Str::random(8), // Генерируем ID (srv-a1b2c3d4)
            'ip_address' => '192.168.' . rand(10, 99) . '.' . rand(10, 255), // Заглушка IP
            'core' => $request->core,
            'status' => 'active',
            'price_monthly' => $product->price,
            'expires_at' => now()->addMonths($months),
        ]);

        return response()->json([
            'message' => 'Услуга успешно активирована!',
            'service' => $service
        ]);
    }

    // Отмена/Удаление услуги
    public function destroy(Request $request, $id)
    {
        $service = $request->user()->services()->findOrFail($id);
        
        // Тут можно добавить логику отправки запроса на Pterodactyl для удаления сервера
        
        $service->delete();

        return response()->json(['message' => 'Услуга успешно удалена']);
    }
}