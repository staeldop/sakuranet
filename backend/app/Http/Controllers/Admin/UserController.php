<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Список всех пользователей
    public function index()
    {
        return User::all();
    }

    // Обновление пользователя (баланс, роль)
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,'.$id,
            'balance' => 'sometimes|numeric',
            'role' => 'sometimes|in:user,admin',
        ]);

        $user->update($validated);

        return response()->json(['message' => 'Пользователь обновлен', 'user' => $user]);
    }

    // Удаление пользователя
    public function destroy($id)
    {
        User::destroy($id);
        return response()->json(['message' => 'Пользователь удален']);
    }
}