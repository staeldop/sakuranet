<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем, что юзер авторизован И у него есть права админа
        // (Предполагается, что в таблице users есть поле is_admin, или ты проверяешь ID)
        if (Auth::check() && Auth::user()->is_admin) {
            return $next($request);
        }

        return response()->json(['message' => 'Access Denied (Admin only)'], 403);
    }
}