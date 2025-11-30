<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiFetch, $api } from '~/composables/useApi'
import { useAuthStore } from '~/stores/auth'

// 🔥 ВАЖНО: Админский лейаут
definePageMeta({ layout: 'admin' })

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ticketId = route.params.id

// Загружаем данные через админский роут
const { data: ticket, pending, refresh } = await useApiFetch<any>(`/api/admin/tickets/${ticketId}`)

const messageText = ref('')
const isSending = ref(false)
const messagesContainer = ref<HTMLElement | null>(null)

const sendMessage = async () => {
  if (!messageText.value.trim()) return
  isSending.value = true
  try {
    await $api(`/api/admin/tickets/${ticketId}/reply`, {
      method: 'POST',
      body: { message: messageText.value }
    })
    messageText.value = ''
    await refresh()
    scrollToBottom()
  } catch (e) { alert('Ошибка отправки') } 
  finally { isSending.value = false }
}

const changeStatus = async (newStatus: string) => {
  if (!confirm(`Изменить статус на "${newStatus}"?`)) return
  try {
    await $api(`/api/admin/tickets/${ticketId}/status`, {
      method: 'PUT',
      body: { status: newStatus }
    })
    await refresh()
  } catch (e) { alert('Ошибка обновления статуса') }
}

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
}

onMounted(() => { if (!pending.value) scrollToBottom() })

const formatDate = (iso: string) => new Date(iso).toLocaleString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })

// 🔥 НОВАЯ ЛОГИКА (АДМИНКА) 🔥
// Мы полностью полагаемся на флаг из базы данных
// is_support === true -> Это сообщение от поддержки (Справа)
// is_support === false -> Это сообщение от клиента (Слева)
const isSupportMsg = (msg: any) => msg.is_support
</script>

<template>
  <div class="admin-chat-layout" v-if="ticket">
    
    <!-- Сайдбар чата (Инфо) -->
    <div class="info-panel glass-card">
      <div class="panel-header">
        <button @click="router.back()" class="back-btn">← Назад</button>
      </div>
      
      <div class="user-block">
        <div class="big-avatar">{{ ticket.user.name[0].toUpperCase() }}</div>
        <div class="u-info">
          <div class="u-name">{{ ticket.user.name }}</div>
          <div class="u-mail">{{ ticket.user.email }}</div>
        </div>
      </div>

      <div class="divider"></div>

      <div class="controls">
        <label>Статус</label>
        <div class="status-grid">
          <button @click="changeStatus('open')" :class="{ active: ticket.status === 'open' }" class="st-btn blue">Open</button>
          <button @click="changeStatus('answered')" :class="{ active: ticket.status === 'answered' }" class="st-btn green">Answered</button>
          <button @click="changeStatus('closed')" :class="{ active: ticket.status === 'closed' }" class="st-btn gray">Closed</button>
        </div>
      </div>
    </div>

    <!-- Основной чат -->
    <div class="chat-main glass-card">
      <div class="chat-head">
        <h2>{{ ticket.subject }}</h2>
        <span class="tag">{{ ticket.department }}</span>
      </div>

      <div class="messages-list" ref="messagesContainer">
        <div 
          v-for="msg in ticket.messages" 
          :key="msg.id" 
          class="msg-row"
          :class="{ 'support-msg': isSupportMsg(msg) }" 
        >
          <!-- 
             Логика классов:
             isSupportMsg (true) -> Справа (Фиолетовое, Админ)
             Иначе (false) -> Слева (Обычное, Клиент)
          -->
          <div class="msg-bubble">
            <div class="msg-top">
              <span class="author">
                <!-- Логика подписей -->
                <template v-if="isSupportMsg(msg)">
                   <!-- Если это саппорт и ID совпадает с моим - пишу "Вы" -->
                   {{ msg.user_id === auth.user?.id ? 'Вы (Admin)' : `${msg.user.name} (Support)` }}
                </template>
                <template v-else>
                   {{ msg.user.name }} (Клиент)
                </template>
              </span>
              <span class="time">{{ formatDate(msg.created_at) }}</span>
            </div>
            <div class="text">{{ msg.message }}</div>
          </div>
        </div>
      </div>

      <form @submit.prevent="sendMessage" class="reply-box">
        <textarea v-model="messageText" placeholder="Напишите ответ..." rows="1" @keydown.enter.exact.prevent="sendMessage"></textarea>
        <button type="submit" :disabled="isSending" class="send-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"></path></svg>
        </button>
      </form>
    </div>

  </div>
</template>

<style scoped>
.admin-chat-layout {
  display: grid; grid-template-columns: 280px 1fr; gap: 20px;
  height: calc(100vh - 80px); 
  max-width: 1400px; margin: 0 auto;
}

.glass-card { background: rgba(20, 20, 20, 0.6); border: 1px solid rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; }

/* INFO PANEL */
.info-panel { padding: 20px; }
.back-btn { background: none; border: none; color: #666; cursor: pointer; margin-bottom: 20px; font-size: 13px; }
.back-btn:hover { color: white; }

.user-block { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
.big-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px; color: white; }
.u-name { font-weight: 700; color: white; font-size: 15px; }
.u-mail { font-size: 12px; color: #777; }

.divider { height: 1px; background: rgba(255,255,255,0.05); margin: 0 -20px 20px; }

.controls label { font-size: 11px; text-transform: uppercase; color: #666; font-weight: 700; display: block; margin-bottom: 10px; }
.status-grid { display: flex; flex-direction: column; gap: 8px; }
.st-btn { padding: 10px; border-radius: 10px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); color: #888; cursor: pointer; text-align: left; font-size: 13px; transition: 0.2s; }
.st-btn:hover { background: rgba(255,255,255,0.05); }
.st-btn.active { font-weight: 600; }
.st-btn.blue.active { background: rgba(59, 130, 246, 0.1); color: #60a5fa; border-color: rgba(59, 130, 246, 0.3); }
.st-btn.green.active { background: rgba(34, 197, 94, 0.1); color: #4ade80; border-color: rgba(34, 197, 94, 0.3); }
.st-btn.gray.active { background: rgba(255,255,255,0.1); color: #ccc; border-color: rgba(255,255,255,0.2); }

/* CHAT MAIN */
.chat-head { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
.chat-head h2 { margin: 0; font-size: 18px; color: white; }
.tag { font-size: 11px; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 6px; color: #888; }

.messages-list { flex-grow: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px; }
.msg-row { display: flex; }

/* Сообщения поддержки - СПРАВА и Фиолетовые */
.msg-row.support-msg { justify-content: flex-end; }

.msg-bubble { max-width: 70%; background: #18181b; border: 1px solid rgba(255,255,255,0.05); padding: 12px 16px; border-radius: 12px; }

/* Стиль для поддержки (Админа) */
.support-msg .msg-bubble { 
  background: linear-gradient(135deg, #7c3aed, #6d28d9); 
  border: none; color: white; 
  border-bottom-right-radius: 4px;
}

/* Стиль для клиента (обычный темный) */
.msg-row:not(.support-msg) .msg-bubble {
  border-bottom-left-radius: 4px;
}

.msg-top { display: flex; justify-content: space-between; gap: 15px; margin-bottom: 5px; font-size: 11px; opacity: 0.7; }
.author { font-weight: 700; }
.text { font-size: 14px; line-height: 1.5; white-space: pre-wrap; }

.reply-box { padding: 20px; background: rgba(255,255,255,0.02); border-top: 1px solid rgba(255,255,255,0.05); display: flex; gap: 12px; }
.reply-box textarea { flex-grow: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 12px; color: white; resize: none; outline: none; height: 46px; box-sizing: border-box; }
.reply-box textarea:focus { border-color: #7c3aed; }
.send-btn { width: 46px; height: 46px; background: #7c3aed; color: white; border: none; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.send-btn:hover { background: #6d28d9; transform: scale(1.05); }
.send-btn:disabled { background: #333; cursor: not-allowed; }
</style>