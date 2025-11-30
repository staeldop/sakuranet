<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiFetch, $api } from '~/composables/useApi'
import { useAuthStore } from '~/stores/auth'

definePageMeta({ layout: 'dashboard' })

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ticketId = route.params.id

// --- ЗАГРУЗКА ДАННЫХ ---
const { data: ticket, pending, refresh, error } = await useApiFetch<any>(`/api/tickets/${ticketId}`)

// --- ОТПРАВКА СООБЩЕНИЯ ---
const messageText = ref('')
const isSending = ref(false)
const messagesContainer = ref<HTMLElement | null>(null)

const sendMessage = async () => {
  if (!messageText.value.trim()) return

  isSending.value = true
  try {
    await $api(`/api/tickets/${ticketId}/reply`, {
      method: 'POST',
      body: { message: messageText.value }
    })

    messageText.value = ''
    await refresh()
    scrollToBottom()
  } catch (e) {
    alert('Ошибка отправки сообщения')
  } finally {
    isSending.value = false
  }
}

// --- СКРОЛЛ ВНИЗ ---
const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

onMounted(() => {
  if (!pending.value) scrollToBottom()
})

// --- СТАТУСЫ ---
const getStatusLabel = (status: string) => {
  if (status === 'open') return 'В обработке'
  if (status === 'answered') return 'Есть ответ'
  return 'Закрыт'
}

const formatDate = (iso: string) => {
  return new Date(iso).toLocaleString('ru-RU', { 
    day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' 
  })
}

// 🔥 НОВАЯ ЛОГИКА ОТОБРАЖЕНИЯ (Client Side) 🔥
// Если is_support == false (или null/0) -> Это сообщение клиента (Вы) -> Справа
// Если is_support == true (или 1) -> Это сообщение поддержки -> Слева
const isMyMessage = (msg: any) => !msg.is_support
</script>

<template>
  <div class="chat-page">
    
    <div v-if="pending" class="loading">Загрузка чата...</div>
    <div v-else-if="error" class="error">Тикет не найден</div>

    <div v-else class="chat-layout">
      
      <div class="chat-header">
        <button @click="router.back()" class="back-btn">← Назад</button>
        <div class="header-info">
          <h1 class="ticket-subject">#{{ ticket.id }} - {{ ticket.subject }}</h1>
          <div class="status-badge" :class="ticket.status">
            {{ getStatusLabel(ticket.status) }}
          </div>
        </div>
      </div>

      <div class="messages-area" ref="messagesContainer">
        <div 
          v-for="msg in ticket.messages" 
          :key="msg.id" 
          class="message-row"
          :class="{ 'my-message': isMyMessage(msg) }"
        >
          <!-- 
             Логика классов:
             isMyMessage (is_support = false) -> Справа (Синее)
             Иначе (is_support = true) -> Слева (Серое, от Поддержки)
          -->
          <div class="message-bubble">
            <div class="msg-header">
              <span class="msg-author">
                <template v-if="isMyMessage(msg)">Вы</template>
                <template v-else>{{ msg.user.name }} (Support)</template>
              </span>
              <span class="msg-time">{{ formatDate(msg.created_at) }}</span>
            </div>
            <div class="msg-text">{{ msg.message }}</div>
          </div>
        </div>
      </div>

      <div class="input-area" v-if="ticket.status !== 'closed'">
        <form @submit.prevent="sendMessage" class="send-form">
          <textarea 
            v-model="messageText" 
            placeholder="Напишите ответ..." 
            rows="1"
            @keydown.enter.exact.prevent="sendMessage"
            class="chat-input"
          ></textarea>
          <button type="submit" class="send-btn" :disabled="isSending || !messageText.trim()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </form>
      </div>

      <div v-else class="closed-notice">
        🔒 Этот тикет закрыт. Вы не можете отправлять сообщения.
      </div>

    </div>
  </div>
</template>

<style scoped>
.chat-page { 
  height: calc(100vh - 180px); 
  display: flex; flex-direction: column; max-width: 900px; 
}

.chat-header {
  display: flex; align-items: center; gap: 20px; 
  padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05);
  margin-bottom: 20px;
}
.back-btn { background: none; border: none; color: #666; cursor: pointer; font-size: 14px; transition: 0.2s; }
.back-btn:hover { color: #fff; }

.header-info { display: flex; align-items: center; gap: 15px; }
.ticket-subject { font-size: 18px; color: #fff; margin: 0; font-weight: 600; }

.status-badge {
  padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase;
}
.status-badge.open { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.status-badge.answered { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
.status-badge.closed { background: rgba(255,255,255,0.1); color: #888; }

.messages-area {
  flex-grow: 1; overflow-y: auto; padding-right: 10px;
  display: flex; flex-direction: column; gap: 15px;
  scroll-behavior: smooth;
}

.messages-area::-webkit-scrollbar { width: 6px; }
.messages-area::-webkit-scrollbar-track { background: transparent; }
.messages-area::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }

.message-row { display: flex; width: 100%; }
.message-row.my-message { justify-content: flex-end; }

.message-bubble {
  max-width: 70%; padding: 12px 16px; border-radius: 16px;
  background: #18181b; border: 1px solid rgba(255,255,255,0.05);
  position: relative;
}

.my-message .message-bubble {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  border: none; color: white;
  border-bottom-right-radius: 4px;
}
.message-row:not(.my-message) .message-bubble {
  border-bottom-left-radius: 4px;
}

.msg-header { 
  display: flex; justify-content: space-between; align-items: center; 
  margin-bottom: 6px; font-size: 11px; opacity: 0.7; gap: 15px; 
}
.msg-author { font-weight: 700; }
.msg-text { font-size: 14px; line-height: 1.5; white-space: pre-wrap; word-break: break-word; }

.input-area { margin-top: 20px; }
.send-form {
  display: flex; gap: 10px; background: rgba(255,255,255,0.03);
  padding: 10px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05);
  align-items: flex-end;
}
.chat-input {
  flex-grow: 1; background: transparent; border: none; color: white;
  resize: none; outline: none; padding: 10px; font-family: inherit; font-size: 14px;
}
.send-btn {
  width: 40px; height: 40px; border-radius: 12px; background: #3b82f6; border: none;
  color: white; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: 0.2s; flex-shrink: 0;
}
.send-btn:hover { background: #2563eb; transform: scale(1.05); }
.send-btn:disabled { background: #333; cursor: not-allowed; transform: none; }
.send-btn svg { width: 20px; height: 20px; margin-left: -2px; margin-top: 2px; }

.closed-notice {
  margin-top: 20px; padding: 15px; text-align: center; 
  background: rgba(255,50,50,0.1); border: 1px solid rgba(255,50,50,0.2);
  color: #fca5a5; border-radius: 12px; font-size: 14px;
}

.loading, .error { padding: 40px; text-align: center; color: #666; }
</style>