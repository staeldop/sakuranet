<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    // ==========================================
    // КЛИЕНТСКАЯ ЧАСТЬ
    // ==========================================

    // Получить список тикетов пользователя
    public function index(Request $request)
    {
        $tickets = Ticket::where('user_id', $request->user()->id)
            ->with('latestMessage') // Подгружаем связь для превью
            ->latest('updated_at') // Сортируем по дате обновления
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'priority' => $ticket->priority, // 🔥 Теперь возвращаем priority
                    'status' => $ticket->status,
                    'lastUpdate' => $ticket->updated_at->toISOString(),
                    'preview' => $ticket->latestMessage ? $ticket->latestMessage->message : 'Нет сообщений',
                ];
            });

        return response()->json($tickets);
    }

    // Создать новый тикет (Клиент)
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|string|in:low,medium,high', // 🔥 Валидация приоритета
            'message' => 'required|string|min:5',
        ]);

        return DB::transaction(function () use ($request) {
            // 1. Создаем сам тикет
            $ticket = Ticket::create([
                'user_id' => $request->user()->id,
                'subject' => $request->subject,
                'priority' => $request->priority, // 🔥 Сохраняем приоритет
                'status' => 'open',
            ]);

            // 2. Создаем первое сообщение
            TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'message' => $request->message,
                'is_support' => false, // <--- Это пишет клиент
            ]);

            return response()->json(['message' => 'Тикет успешно создан', 'id' => $ticket->id], 201);
        });
    }

    // Показать переписку внутри тикета (для страницы чата)
    public function show(Request $request, $id)
    {
        $ticket = Ticket::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with(['messages.user']) // Подгружаем сообщения и авторов
            ->firstOrFail();

        return response()->json($ticket);
    }

    // Отправить сообщение в тикет (Клиент)
    public function reply(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);

        $ticket = Ticket::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'is_support' => false, // <--- Это пишет клиент
        ]);

        // Обновляем дату обновления тикета и меняем статус, если он был закрыт
        $ticket->touch();
        if ($ticket->status === 'closed') {
            $ticket->update(['status' => 'open']);
        }

        return response()->json($message);
    }

    // ==========================================
    // АДМИНСКАЯ ЧАСТЬ
    // ==========================================

    // Все тикеты всех пользователей
    public function adminIndex()
    {
        $tickets = Ticket::with('user') // Подгружаем автора тикета
            ->withCount('messages')
            ->latest()
            ->paginate(20);

        return response()->json($tickets);
    }

    // Админ смотрит переписку конкретного тикета
    public function adminShow($id)
    {
        $ticket = Ticket::with(['messages.user', 'user']) // Подгружаем сообщения и автора тикета
            ->findOrFail($id);

        return response()->json($ticket);
    }

    // Админ меняет статус (например, закрывает тикет)
    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:open,answered,closed']);
        
        $ticket = Ticket::findOrFail($id);
        $ticket->update(['status' => $request->status]);

        return response()->json(['message' => 'Статус обновлен']);
    }

    // Админ отвечает
    public function adminReply(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);

        $ticket = Ticket::findOrFail($id);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id, // ID админа
            'message' => $request->message,
            'is_support' => true, // <--- 🔥 ВАЖНО: Это пишет поддержка
        ]);

        // При ответе админа ставим статус "answered"
        $ticket->update(['status' => 'answered']);
        $ticket->touch();

        return response()->json(['message' => 'Ответ отправлен']);
    }
}
