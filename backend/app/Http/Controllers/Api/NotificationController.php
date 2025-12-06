<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    // ==========================================
    // КЛИЕНТСКАЯ ЧАСТЬ
    // ==========================================

    // Получить все уведомления текущего пользователя
    public function index(Request $request)
    {
        return Notification::where('user_id', $request->user()->id)
            ->latest()
            ->get();
    }

    // Пометить как прочитанное
    public function markAsRead(Request $request, $id)
    {
        $note = Notification::where('user_id', $request->user()->id)->findOrFail($id);
        $note->update(['is_read' => true]);
        return response()->json(['message' => 'Read']);
    }

    // Пометить все как прочитанные
    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->update(['is_read' => true]);
        return response()->json(['message' => 'All read']);
    }

    // Удалить одно
    public function destroy(Request $request, $id)
    {
        $note = Notification::where('user_id', $request->user()->id)->findOrFail($id);
        $note->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // Удалить все
    public function destroyAll(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->delete();
        return response()->json(['message' => 'All deleted']);
    }

    // ==========================================
    // АДМИНСКАЯ ЧАСТЬ
    // ==========================================

    // Отправить уведомление (Админ)
    public function send(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id', // Если null - всем
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
            'type'    => 'required|in:info,success,warning,error',
        ]);

        if ($request->user_id) {
            // Отправить одному пользователю
            Notification::create([
                'user_id' => $request->user_id,
                'title'   => $request->title,
                'message' => $request->message,
                'type'    => $request->type,
            ]);
        } else {
            // Отправить ВСЕМ пользователям
            // В реальном проекте это лучше делать через Queue/Jobs
            $users = \App\Models\User::all();
            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'title'   => $request->title,
                    'message' => $request->message,
                    'type'    => $request->type,
                ]);
            }
        }

        return response()->json(['message' => 'Уведомление отправлено']);
    }
}
