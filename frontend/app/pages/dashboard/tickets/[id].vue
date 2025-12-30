<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiFetch, useApi } from '~/composables/useApi'
import { useAuthStore } from '~/stores/auth'

definePageMeta({ layout: 'dashboard' })

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ticketId = route.params.id

// Загрузка тикета
const { data: ticket, pending, refresh, error } = await useApiFetch<any>(`/api/tickets/${ticketId}`)

const messageText = ref('')
const isSending = ref(false)
const messagesContainer = ref<HTMLElement | null>(null)
const textareaRef = ref<HTMLTextAreaElement | null>(null)

// --- СКРОЛЛ И РЕСАЙЗ ---
const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const autoResize = () => {
  const el = textareaRef.value
  if (!el) return
  el.style.height = 'auto'
  el.style.height = Math.min(el.scrollHeight, 150) + 'px'
}

watch(() => ticket.value?.messages?.length, () => scrollToBottom())

onMounted(() => {
  if (ticket.value && !pending.value) scrollToBottom()
})

// --- ОТПРАВКА ---
const sendMessage = async () => {
  const text = messageText.value.trim()
  if (!text) return

  isSending.value = true
  
  // Очистка поля
  messageText.value = ''
  if (textareaRef.value) textareaRef.value.style.height = '46px'

  // Временное сообщение (оптимистичный UI)
  const tempId = Date.now()
  const tempMsg = {
    id: tempId,
    user_id: auth.user?.id,
    message: text,
    created_at: new Date().toISOString(),
    is_support: false,
    user: { name: 'Вы' }
  }

  if (ticket.value) {
    if (!ticket.value.messages) ticket.value.messages = []
    ticket.value.messages.push(tempMsg)
    scrollToBottom()
  }

  try {
    // 🔥 ОТПРАВКА
    await useApi(`/api/tickets/${ticketId}/reply`, {
      method: 'POST',
      body: { message: text }
    })
    // Успех (можно вызвать refresh(), если нужны данные с сервера)
  } catch (e: any) {
    console.error('Ошибка отправки:', e)
    // Показываем реальную ошибку
    const errorMsg = e.data?.message || e.message || 'Неизвестная ошибка'
    alert(`Не удалось отправить сообщение:\n${errorMsg}`)
    
    // Возвращаем текст в поле и удаляем временное сообщение
    messageText.value = text
    if (ticket.value && ticket.value.messages) {
       ticket.value.messages = ticket.value.messages.filter((m: any) => m.id !== tempId)
    }
  } finally {
    isSending.value = false
  }
}

// --- ХЕЛПЕРЫ ---
const getStatusLabel = (status: string) => {
  if (status === 'open') return 'В обработке'
  if (status === 'answered') return 'Есть ответ'
  return 'Закрыт'
}

const formatDate = (iso: string) => {
  try {
    return new Date(iso).toLocaleString('ru-RU', { 
      day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' 
    })
  } catch (e) { return iso }
}

const isMyMessage = (msg: any) => !msg.is_support
</script>

<template>
  <div class="chat-page">
    <div v-if="pending" class="center-msg">Загрузка чата...</div>
    <div v-else-if="error" class="center-msg error">Тикет не найден</div>

    <div v-else-if="ticket" class="chat-layout">
      
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
          <div class="message-bubble">
            <div class="msg-header">
              <span class="msg-author">
                <template v-if="isMyMessage(msg)">Вы</template>
                <template v-else>{{ msg.user?.name || 'Support' }} (Support)</template>
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
            ref="textareaRef"
            v-model="messageText" 
            placeholder="Напишите ответ..." 
            rows="1"
            @input="autoResize"
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
  height: 100%; 
  display: flex; flex-direction: column; width: 100%;
}
.center-msg { flex-grow: 1; display: flex; align-items: center; justify-content: center; color: #888; }
.center-msg.error { color: #ef4444; }

.chat-layout { display: flex; flex-direction: column; flex-grow: 1; height: 100%; overflow: hidden; }

.chat-header {
  display: flex; align-items: center; gap: 15px; 
  padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05);
  flex-shrink: 0;
}
.back-btn { background: none; border: none; color: #666; cursor: pointer; font-size: 14px; transition: 0.2s; }
.back-btn:hover { color: #fff; }

.header-info { display: flex; align-items: center; gap: 10px; }
.ticket-subject { font-size: 18px; color: #fff; margin: 0; font-weight: 600; }

.status-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.status-badge.open { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.status-badge.answered { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
.status-badge.closed { background: rgba(255,255,255,0.1); color: #888; }

.messages-area {
  flex-grow: 1; overflow-y: auto; 
  padding-right: 10px; padding-top: 15px;
  display: flex; flex-direction: column; gap: 15px;
  min-height: 0;
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
  border: none; color: white; border-bottom-right-radius: 4px;
}
.message-row:not(.my-message) .message-bubble { border-bottom-left-radius: 4px; }

.msg-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: 11px; opacity: 0.7; gap: 15px; }
.msg-author { font-weight: 700; }
.msg-text { font-size: 14px; line-height: 1.5; white-space: pre-wrap; word-break: break-word; }

.input-area { margin-top: auto; padding-top: 15px; flex-shrink: 0; }
.send-form { display: flex; gap: 10px; background: rgba(255,255,255,0.03); padding: 8px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); align-items: flex-end; }
.chat-input { flex-grow: 1; background: transparent; border: none; color: white; resize: none; outline: none; padding: 10px; font-family: inherit; font-size: 14px; max-height: 150px; overflow-y: auto; }
.send-btn { width: 40px; height: 40px; border-radius: 12px; background: #3b82f6; border: none; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; flex-shrink: 0; margin-bottom: 2px; }
.send-btn:hover:not(:disabled) { background: #2563eb; transform: scale(1.05); }
.send-btn:disabled { background: #333; cursor: not-allowed; }
.send-btn svg { width: 20px; height: 20px; margin-left: -2px; margin-top: 2px; }

.closed-notice { margin-top: 20px; padding: 15px; text-align: center; background: rgba(255,50,50,0.1); border: 1px solid rgba(255,50,50,0.2); color: #fca5a5; border-radius: 12px; font-size: 14px; }
</style>
 