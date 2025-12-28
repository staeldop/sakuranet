<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
// ИСПРАВЛЕНО: Заменили $api на useApi
import { useApiFetch, useApi } from '~/composables/useApi'
import { useAuthStore } from '~/stores/auth'

definePageMeta({ layout: 'admin' })

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const ticketId = route.params.id

// lazy: false чтобы данные были сразу
const { data: ticket, pending, refresh, error } = await useApiFetch<any>(`/api/admin/tickets/${ticketId}`)

const messageText = ref('')
const isSending = ref(false)
const messagesContainer = ref<HTMLElement | null>(null)
const textareaRef = ref<HTMLTextAreaElement | null>(null)

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

watch(() => ticket.value?.messages?.length, () => {
  scrollToBottom()
})

onMounted(() => {
  if (ticket.value && !pending.value) scrollToBottom()
})

const sendMessage = async () => {
  if (!messageText.value.trim()) return
  
  const textToSend = messageText.value
  isSending.value = true
  messageText.value = '' 
  if (textareaRef.value) textareaRef.value.style.height = '46px'

  const tempId = Date.now()
  const tempMsg = {
     id: tempId,
     user_id: auth.user?.id,
     message: textToSend,
     created_at: new Date().toISOString(),
     is_support: true, 
     user: { name: auth.user?.name || 'Admin' }
  }

  if (ticket.value) {
      if (!ticket.value.messages) ticket.value.messages = []
      ticket.value.messages.push(tempMsg)
      scrollToBottom()
  }

  try {
    // ИСПРАВЛЕНО: $api -> useApi
    await useApi(`/api/admin/tickets/${ticketId}/reply`, {
      method: 'POST',
      body: { message: textToSend }
    })
  } catch (e) { 
    alert('Ошибка отправки')
    messageText.value = textToSend 
    if (ticket.value && ticket.value.messages) {
       ticket.value.messages = ticket.value.messages.filter((m: any) => m.id !== tempId)
    }
  } finally { 
    isSending.value = false 
  }
}

const changeStatus = async (newStatus: string) => {
  if (!confirm(`Изменить статус на "${newStatus}"?`)) return
  try {
    // ИСПРАВЛЕНО: $api -> useApi
    await useApi(`/api/admin/tickets/${ticketId}/status`, {
      method: 'PUT',
      body: { status: newStatus }
    })
    await refresh()
  } catch (e) { alert('Ошибка обновления статуса') }
}

const formatDate = (iso: string) => {
  try {
    return new Date(iso).toLocaleString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
  } catch (e) { return iso }
}

const isSupportMsg = (msg: any) => msg.is_support
</script>

<template>
  <div class="admin-chat-layout">
    <div v-if="pending" class="center-msg">Загрузка...</div>
    <div v-else-if="error" class="center-msg error">Ошибка: {{ error.message }}</div>

    <template v-else-if="ticket">
      <div class="info-panel glass-card">
        <div class="panel-header">
          <button @click="router.back()" class="back-btn">← Назад</button>
        </div>
        <div class="user-block" v-if="ticket.user">
          <div class="big-avatar">{{ ticket.user.name ? ticket.user.name[0].toUpperCase() : 'U' }}</div>
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

      <div class="chat-main glass-card">
        <div class="chat-head">
          <h2>{{ ticket.subject }}</h2>
          <span class="tag">{{ ticket.department }}</span>
        </div>

        <div class="messages-list" ref="messagesContainer">
          <div v-for="msg in ticket.messages" :key="msg.id" class="msg-row" :class="{ 'support-msg': isSupportMsg(msg) }">
            <div class="msg-bubble">
              <div class="msg-top">
                <span class="author">
                  <template v-if="isSupportMsg(msg)">{{ (msg.user_id === auth.user?.id) ? 'Вы (Admin)' : `${msg.user?.name || 'Support'} (Support)` }}</template>
                  <template v-else>{{ msg.user?.name || 'Клиент' }} (Клиент)</template>
                </span>
                <span class="time">{{ formatDate(msg.created_at) }}</span>
              </div>
              <div class="text">{{ msg.message }}</div>
            </div>
          </div>
        </div>

        <form @submit.prevent="sendMessage" class="reply-box">
          <textarea ref="textareaRef" v-model="messageText" placeholder="Напишите ответ..." rows="1" class="chat-input" @input="autoResize" @keydown.enter.exact.prevent="sendMessage"></textarea>
          <button type="submit" :disabled="!messageText.trim() || isSending" class="send-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"></path></svg>
          </button>
        </form>
      </div>
    </template>
  </div>
</template>

<style scoped>
.admin-chat-layout { display: grid; grid-template-columns: 280px 1fr; gap: 20px; height: calc(100vh - 40px); max-width: 1400px; margin: 0 auto; }
.center-msg { grid-column: 1 / -1; text-align: center; padding: 50px; color: #888; }
.center-msg.error { color: #ef4444; }
.glass-card { background: rgba(20, 20, 20, 0.6); border: 1px solid rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; }
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
.chat-head { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
.chat-head h2 { margin: 0; font-size: 18px; color: white; }
.tag { font-size: 11px; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 6px; color: #888; }
.messages-list { flex-grow: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent; }
.msg-row { display: flex; }
.msg-row.support-msg { justify-content: flex-end; }
.msg-bubble { max-width: 70%; background: #18181b; border: 1px solid rgba(255,255,255,0.05); padding: 12px 16px; border-radius: 12px; }
.support-msg .msg-bubble { background: linear-gradient(135deg, #7c3aed, #6d28d9); border: none; color: white; border-bottom-right-radius: 4px; }
.msg-row:not(.support-msg) .msg-bubble { border-bottom-left-radius: 4px; }
.msg-top { display: flex; justify-content: space-between; gap: 15px; margin-bottom: 5px; font-size: 11px; opacity: 0.7; }
.author { font-weight: 700; }
.text { font-size: 14px; line-height: 1.5; white-space: pre-wrap; }
.reply-box { padding: 20px; background: rgba(255,255,255,0.02); border-top: 1px solid rgba(255,255,255,0.05); display: flex; align-items: flex-end; gap: 12px; }
.chat-input { flex-grow: 1; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 12px; color: white; resize: none; outline: none; max-height: 150px; overflow-y: auto; font-family: inherit; }
.chat-input:focus { border-color: #7c3aed; }
.send-btn { width: 46px; height: 46px; background: #7c3aed; color: white; border: none; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; flex-shrink: 0; }
.send-btn:hover { background: #6d28d9; transform: scale(1.05); }
.send-btn:disabled { background: #333; cursor: not-allowed; }
</style>