<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useApiFetch, $api } from '~/composables/useApi' 

definePageMeta({
  layout: 'dashboard'
})

const router = useRouter()

// --- ТИПЫ ---
type TicketStatus = 'open' | 'answered' | 'closed'

interface Ticket {
  id: number
  subject: string
  department: string
  status: TicketStatus
  lastUpdate: string
  preview: string
}

// --- 1. ЗАГРУЗКА ДАННЫХ ---
const { data: ticketsData, pending, refresh, error } = await useApiFetch<Ticket[]>('/api/tickets')

const tickets = computed(() => ticketsData.value || [])

// --- 2. ФИЛЬТРАЦИЯ ---
const currentTab = ref<'all' | 'open' | 'closed'>('all')

const filteredTickets = computed(() => {
  if (currentTab.value === 'all') return tickets.value
  if (currentTab.value === 'open') return tickets.value.filter(t => t.status !== 'closed')
  if (currentTab.value === 'closed') return tickets.value.filter(t => t.status === 'closed')
  return tickets.value
})

// --- 3. СОЗДАНИЕ ТИКЕТА ---
const isModalOpen = ref(false)
const isSubmitting = ref(false)
const newTicketForm = ref({ subject: '', department: 'tech', message: '' })

const openModal = () => isModalOpen.value = true
const closeModal = () => isModalOpen.value = false

const createTicket = async () => {
  if (!newTicketForm.value.subject || !newTicketForm.value.message) {
    alert('Заполните тему и сообщение')
    return
  }
  
  isSubmitting.value = true
  
  try {
    await $api('/api/tickets', {
      method: 'POST',
      body: newTicketForm.value
    })
    
    await refresh()
    newTicketForm.value = { subject: '', department: 'tech', message: '' }
    closeModal()
    
  } catch (e: any) {
    console.error('Ошибка:', e)
    // Обработка ошибок валидации Laravel (422)
    if (e.response && e.response.status === 422) {
      const errors = e.response._data?.errors
      if (errors) {
        const firstField = Object.keys(errors)[0]
        alert(`Ошибка: ${errors[firstField][0]}`)
      } else {
        alert('Ошибка проверки данных')
      }
    } else {
      alert('Не удалось создать тикет. Попробуйте позже.')
    }
  } finally {
    isSubmitting.value = false
  }
}

// --- 4. ПЕРЕХОД В ЧАТ ---
const openTicket = (id: number) => {
  router.push(`/dashboard/tickets/${id}`)
}

// --- ХЕЛПЕРЫ ---
const formatDate = (iso: string) => {
  if (!iso) return ''
  try {
    return new Date(iso).toLocaleDateString('ru-RU', { 
      day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' 
    })
  } catch (e) { return iso }
}

const getStatusLabel = (status: TicketStatus) => {
  if (status === 'open') return 'В обработке'
  if (status === 'answered') return 'Ответ получен'
  return 'Закрыт'
}

const getStatusClass = (status: TicketStatus) => status || 'closed'
</script>

<template>
  <div class="tickets-page">
    <div class="content-wrapper">
      
      <div class="header-row">
        <div>
          <h1 class="page-title">Поддержка</h1>
          <p class="subtitle">Мы всегда готовы помочь вам</p>
        </div>
        <button class="create-btn" @click="openModal">
          <span class="plus-icon">+</span> Новый тикет
        </button>
      </div>

      <div class="tabs">
        <button 
          v-for="tab in ['all', 'open', 'closed']" 
          :key="tab"
          @click="currentTab = tab as any"
          class="tab-btn"
          :class="{ active: currentTab === tab }"
        >
          {{ tab === 'all' ? 'Все тикеты' : tab === 'open' ? 'Активные' : 'Архив' }}
        </button>
      </div>

      <div v-if="pending" class="loading-state">
        <div class="skeleton-card" v-for="i in 3" :key="i"></div>
      </div>

      <div v-else-if="error" class="error-state">
        <p>Ошибка загрузки тикетов. Попробуйте обновить страницу.</p>
      </div>

      <div v-else class="list-container">
        <div class="tickets-wrapper">
          <div 
            v-for="ticket in filteredTickets" 
            :key="ticket.id" 
            class="ticket-card"
            :class="ticket.status"
            @click="openTicket(ticket.id)"
          >
            <div class="ticket-main">
              <div class="ticket-header">
                <span class="ticket-id">#{{ ticket.id }}</span>
                <span class="dot">•</span>
                <span class="ticket-dept">{{ ticket.department }}</span>
              </div>
              <h3 class="ticket-subject">{{ ticket.subject }}</h3>
              <p class="ticket-preview">{{ ticket.preview }}</p>
            </div>

            <div class="ticket-meta">
              <div class="status-badge" :class="getStatusClass(ticket.status)">
                <div class="status-dot"></div>
                {{ getStatusLabel(ticket.status) }}
              </div>
              <span class="ticket-date">{{ formatDate(ticket.lastUpdate) }}</span>
            </div>
          </div>
        </div>

        <div v-if="filteredTickets.length === 0" class="empty-state">
          <span class="empty-icon">📂</span>
          <p>Тикетов в этой категории нет</p>
        </div>
      </div>
    </div>

    <Transition name="fade">
      <div v-if="isModalOpen" class="modal-backdrop" @click.self="closeModal">
        <div class="modal-window">
          <div class="modal-header">
            <h2>Создание запроса</h2>
            <button class="close-modal-btn" @click="closeModal">✕</button>
          </div>
          
          <form @submit.prevent="createTicket" class="ticket-form">
            <div class="form-group">
              <label>Тема обращения</label>
              <input 
                v-model="newTicketForm.subject" 
                type="text" 
                placeholder="Например: Не работает FTP" 
                class="glass-input" 
                autoFocus 
              >
            </div>

            <div class="form-group">
              <label>Отдел</label>
              <select v-model="newTicketForm.department" class="glass-input">
                <option value="tech">Технический отдел</option>
                <option value="billing">Бухгалтерия</option>
                <option value="other">Общие вопросы</option>
              </select>
            </div>

            <div class="form-group">
              <label>Сообщение</label>
              <textarea 
                v-model="newTicketForm.message" 
                rows="5" 
                placeholder="Опишите проблему подробно..." 
                class="glass-input area"
              ></textarea>
            </div>

            <button type="submit" class="submit-btn" :disabled="isSubmitting">
              {{ isSubmitting ? 'Отправка...' : 'Отправить запрос' }}
            </button>
          </form>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
.tickets-page { width: 100%; max-width: 900px; padding-bottom: 80px; }
.content-wrapper { position: relative; z-index: 10; }

.header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.page-title { color: #fff; font-size: 26px; font-weight: 700; margin: 0; }
.subtitle { color: #666; font-size: 14px; margin: 5px 0 0; }

.create-btn {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  border: none; padding: 10px 20px; border-radius: 12px;
  color: white; font-weight: 600; font-size: 14px; cursor: pointer;
  display: flex; align-items: center; gap: 8px;
  box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
  transition: 0.2s;
}
.create-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6); }

.tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; }
.tab-btn {
  background: transparent; border: none; color: #666; padding: 8px 16px; 
  border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: 0.2s;
}
.tab-btn:hover { color: #fff; background: rgba(255,255,255,0.03); }
.tab-btn.active { color: #fff; background: rgba(255,255,255,0.1); }

.tickets-wrapper { display: flex; flex-direction: column; gap: 12px; }

.ticket-card {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 16px; padding: 20px;
  display: flex; justify-content: space-between; align-items: flex-start;
  transition: 0.2s; cursor: pointer;
}
.ticket-card:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.1);
  transform: translateY(-2px);
}

.ticket-header { font-size: 12px; color: #555; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
.ticket-id { font-family: monospace; color: #777; }
.ticket-subject { color: #fff; font-size: 16px; font-weight: 600; margin: 0 0 6px; }
.ticket-preview { color: #888; font-size: 13px; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 400px; }

.ticket-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
.ticket-date { color: #444; font-size: 12px; }

.status-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
}
.status-dot { width: 6px; height: 6px; border-radius: 50%; }

.ticket-card.open .status-badge { background: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.2); }
.ticket-card.open .status-dot { background: #60a5fa; box-shadow: 0 0 5px #60a5fa; }

.ticket-card.answered .status-badge { background: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }
.ticket-card.answered .status-dot { background: #4ade80; box-shadow: 0 0 8px #4ade80; animation: pulse 2s infinite; }

.ticket-card.closed .status-badge { background: rgba(255, 255, 255, 0.05); color: #888; border: 1px solid rgba(255, 255, 255, 0.05); }
.ticket-card.closed .status-dot { background: #666; }
.ticket-card.closed { opacity: 0.7; }

.modal-backdrop {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.7); backdrop-filter: blur(5px);
  z-index: 100; display: flex; align-items: center; justify-content: center;
}
.modal-window {
  background: #09090b; border: 1px solid rgba(255,255,255,0.1);
  width: 100%; max-width: 500px; padding: 30px; border-radius: 24px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.5);
}
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.modal-header h2 { color: #fff; margin: 0; font-size: 20px; }
.close-modal-btn { background: none; border: none; color: #666; font-size: 20px; cursor: pointer; transition: 0.2s; }
.close-modal-btn:hover { color: #fff; }

.form-group { margin-bottom: 15px; }
.form-group label { display: block; color: #888; font-size: 12px; margin-bottom: 8px; font-weight: 500; }
.glass-input {
  width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
  padding: 12px 15px; border-radius: 12px; color: #fff; font-size: 14px; outline: none; transition: 0.2s; box-sizing: border-box;
}
.glass-input:focus { border-color: #3b82f6; background: rgba(59, 130, 246, 0.05); }
.area { resize: vertical; min-height: 100px; font-family: inherit; }

.submit-btn {
  width: 100%; background: #fff; color: #000; font-weight: 700;
  padding: 14px; border: none; border-radius: 12px; cursor: pointer; margin-top: 10px; transition: 0.2s;
}
.submit-btn:disabled { background: #555; cursor: not-allowed; }
.submit-btn:hover:not(:disabled) { background: #e5e5e5; }

.empty-state { text-align: center; padding: 40px; color: #444; font-size: 14px; }
.empty-icon { display: block; font-size: 24px; margin-bottom: 10px; }
.error-state { text-align: center; color: #ef4444; padding: 20px; }

.loading-state { display: flex; flex-direction: column; gap: 12px; }
.skeleton-card { height: 90px; background: rgba(255,255,255,0.03); border-radius: 16px; animation: pulse-bg 1.5s infinite; }
@keyframes pulse-bg { 0% { opacity: 0.3; } 50% { opacity: 0.7; } 100% { opacity: 0.3; } }
@keyframes pulse { 0% { opacity: 0.5; } 50% { opacity: 1; } 100% { opacity: 0.5; } }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>