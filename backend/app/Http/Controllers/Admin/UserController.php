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
        // Проверка на права админа (дополнительная защита)
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return User::orderBy('id', 'desc')->get();
    }

    // Обновление пользователя
    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') return response()->json(['message' => 'Forbidden'], 403);

        $user = User::findOrFail($id);
        
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,'.$id,
            'balance' => 'numeric',
            'role' => 'required|in:user,admin'
        ]);

        $user->update($data);
        return $user;
    }

    // Удаление пользователя
    public function destroy($id)
    {
        if (auth()->user()->role !== 'admin') return response()->json(['message' => 'Forbidden'], 403);
        
        User::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}