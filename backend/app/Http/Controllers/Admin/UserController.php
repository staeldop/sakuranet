<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
// import { useApiFetch, $api } from '~/composables/useApi' // Раскомментируй в реальном проекте

// --- MOCK DATA (Для демонстрации, удали в реальном проекте) ---
const mockTickets = [
  { id: 1, subject: 'Не работает интеграция с API', priority: 'high', status: 'open', lastUpdate: '2023-10-25T10:30:00Z', preview: 'Пытаюсь отправить запрос на эндпоинт /v2/orders, но получаю 500 ошибку...' },
  { id: 2, subject: 'Вопрос по оплате', priority: 'medium', status: 'answered', lastUpdate: '2023-10-24T14:15:00Z', preview: 'Добрый день, подскажите, как скачать закрывающие документы за прошлый месяц?' },
  { id: 3, subject: 'Предложение по функционалу', priority: 'low', status: 'closed', lastUpdate: '2023-10-20T09:00:00Z', preview: 'Было бы здорово добавить темную тему в личный кабинет.' },
]
const useApiFetch = async (url: string) => ({ data: ref(mockTickets), pending: ref(false), refresh: async () => {} })
const $api = async (url: string, opts: any) => {}
// -----------------------------------------------------------

definePageMeta({
  layout: 'dashboard'
})

const router = useRouter()

// --- ТИПЫ ---
type TicketStatus = 'open' | 'answered' | 'closed'
type TicketPriority = 'low' | 'medium' | 'high'

interface Ticket {
  id: number
  subject: string
  priority: TicketPriority
  status: TicketStatus
  lastUpdate: string
  preview: string
}

// --- 1. ЗАГРУЗКА ДАННЫХ ---
const { data: ticketsData, pending, refresh } = await useApiFetch<Ticket[]>('/api/tickets')
const tickets = computed(() => ticketsData.value || [])

// --- 2. ФИЛЬТРАЦИЯ ---
const currentTab = ref<'all' | 'open' | 'closed'>('all')

const filteredTickets = computed(() => {
  let sorted = tickets.value
  
  // Сортировка: Сначала High, потом Medium, потом Low
  const priorityWeight = { high: 3, medium: 2, low: 1 }
  sorted = [...sorted].sort((a, b) => priorityWeight[b.priority] - priorityWeight[a.priority])

  if (currentTab.value === 'all') return sorted
  if (currentTab.value === 'open') return sorted.filter(t => t.status !== 'closed')
  if (currentTab.value === 'closed') return sorted.filter(t => t.status === 'closed')
  return sorted
})

// --- 3. СОЗДАНИЕ ТИКЕТА ---
const isModalOpen = ref(false)
const isSubmitting = ref(false)
const newTicketForm = ref({ subject: '', priority: 'low' as TicketPriority, message: '' })

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
    newTicketForm.value = { subject: '', priority: 'low', message: '' }
    closeModal()
  } catch (e: any) {
    console.error(e)
    alert('Ошибка создания тикета')
  } finally {
    isSubmitting.value = false
  }
}

const openTicket = (id: number) => {
  router.push(`/dashboard/tickets/${id}`)
}

// --- ХЕЛПЕРЫ ---
const formatDate = (iso: string) => {
  if (!iso) return ''
  try {
    const date = new Date(iso)
    const now = new Date()
    const isToday = date.toDateString() === now.toDateString()
    
    if (isToday) {
      return `Сегодня, ${date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })}`
    }
    return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
  } catch (e) { return iso }
}

// Конфигурация приоритетов (Цвета перенастроены под темную тему)
const priorityConfig: Record<TicketPriority, { label: string, color: string, shadow: string }> = {
  low:    { label: 'Low',     color: '#34d399', shadow: 'rgba(52, 211, 153, 0.4)' }, 
  medium: { label: 'Medium',  color: '#fbbf24', shadow: 'rgba(251, 191, 36, 0.4)' }, 
  high:   { label: 'High',    color: '#f87171', shadow: 'rgba(248, 113, 113, 0.4)' }   
}

const getStatusIcon = (status: TicketStatus) => {
  if (status === 'answered') return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
  if (status === 'open') return 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
  return 'M5 13l4 4L19 7' // Checkmark for closed
}

// --- ЛОГИКА СЕЛЕКТОРА ---
const isPriorityOpen = ref(false)
const togglePriority = () => isPriorityOpen.value = !isPriorityOpen.value
const selectPriority = (val: TicketPriority) => {
  newTicketForm.value.priority = val
  isPriorityOpen.value = false
}

const currentPriorityLabel = computed(() => priorityConfig[newTicketForm.value.priority].label)
const currentPriorityColor = computed(() => priorityConfig[newTicketForm.value.priority].color)
</script>

<template>
  <div class="tickets-page">
    
    <div class="bg-noise"></div>

    <div class="content-wrapper">
      
      <div class="header-section">
        <div class="title-group">
          <h1 class="page-title">Support <span class="text-gradient">Center</span></h1>
          <p class="subtitle">Управляйте запросами и отслеживайте статус</p>
        </div>
        <button class="create-btn" @click="openModal">
          <span class="btn-icon">+</span>
          <span>Новый тикет</span>
        </button>
      </div>

      <div class="tabs-wrapper">
        <div class="tabs-container">
          <button 
            v-for="tab in ['all', 'open', 'closed']" 
            :key="tab"
            @click="currentTab = tab as any"
            class="tab-btn"
            :class="{ active: currentTab === tab }"
          >
            {{ tab === 'all' ? 'Все тикеты' : tab === 'open' ? 'Активные' : 'Архив' }}
            <div v-if="currentTab === tab" class="tab-glow"></div>
          </button>
        </div>
      </div>

      <div class="list-area">
        <div v-if="pending" class="loading-state">
          <div class="skeleton-row" v-for="i in 3" :key="i"></div>
        </div>

        <TransitionGroup name="staggered-fade" tag="div" v-else-if="filteredTickets.length" class="ticket-grid">
          <div 
            v-for="ticket in filteredTickets" 
            :key="ticket.id" 
            class="ticket-card"
            :class="ticket.status"
            @click="openTicket(ticket.id)"
            :style="{ 
              '--p-color': priorityConfig[ticket.priority].color,
              '--p-shadow': priorityConfig[ticket.priority].shadow
            }" 
          >
            <div class="status-line"></div>

            <div class="card-inner">
              <div class="card-header">
                <div class="ticket-id">#{{ ticket.id.toString().padStart(4, '0') }}</div>
                <div class="priority-badge">
                  <span class="dot"></span>
                  {{ priorityConfig[ticket.priority].label }}
                </div>
              </div>

              <h3 class="ticket-subject">{{ ticket.subject }}</h3>
              <p class="ticket-preview">{{ ticket.preview }}</p>

              <div class="card-footer">
                <div class="status-indicator" :class="ticket.status">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getStatusIcon(ticket.status)" />
                  </svg>
                  <span>{{ ticket.status === 'open' ? 'Открыт' : ticket.status === 'answered' ? 'Есть ответ' : 'Закрыт' }}</span>
                </div>
                <span class="date">{{ formatDate(ticket.lastUpdate) }}</span>
              </div>
            </div>
            
            <div class="hover-glass"></div>
          </div>
        </TransitionGroup>

        <div v-else class="empty-state">
          <div class="empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
          </div>
          <h3>Нет обращений</h3>
          <p>В этой категории пока пусто</p>
        </div>
      </div>
    </div>

    <Transition name="modal">
      <div v-if="isModalOpen" class="modal-overlay" @click.self="closeModal">
        <div class="modal-panel">
          <div class="modal-head">
            <h2>Создать запрос</h2>
            <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="createTicket" class="modal-body">
            
            <div class="form-group">
              <label>Тема</label>
              <input 
                v-model="newTicketForm.subject" 
                type="text" 
                placeholder="Краткая суть проблемы..."
                class="modern-input"
                autoFocus
              >
            </div>

            <div class="form-group priority-group">
              <label>Приоритет</label>
              <div class="custom-select" @click="togglePriority" :class="{ active: isPriorityOpen }">
                <div class="selected-value">
                  <span class="p-dot" :style="{ background: currentPriorityColor, boxShadow: `0 0 10px ${currentPriorityColor}` }"></span>
                  {{ currentPriorityLabel }}
                </div>
                <div class="chevron">▾</div>

                <Transition name="dropdown">
                  <div v-if="isPriorityOpen" class="dropdown-options">
                    <div 
                      v-for="(conf, key) in priorityConfig" 
                      :key="key"
                      class="option-item"
                      :class="{ selected: newTicketForm.priority === key }"
                      @click.stop="selectPriority(key)"
                    >
                      <span class="p-dot sm" :style="{ background: conf.color }"></span>
                      {{ conf.label }}
                    </div>
                  </div>
                </Transition>
              </div>
            </div>

            <div class="form-group">
              <label>Сообщение</label>
              <textarea 
                v-model="newTicketForm.message" 
                rows="5"
                placeholder="Опишите детали подробно..."
                class="modern-input area"
              ></textarea>
            </div>

            <div class="modal-actions">
              <button type="button" class="cancel-btn" @click="closeModal">Отмена</button>
              <button type="submit" class="submit-btn" :disabled="isSubmitting">
                <span v-if="isSubmitting" class="spinner"></span>
                <span>{{ isSubmitting ? 'Отправка...' : 'Отправить' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* --- 1. GLOBAL & LAYOUT --- */
.tickets-page {
  width: 100%;
  min-height: 100vh;
  background-color: #050505; /* Deep Black */
  color: #e5e5e5;
  font-family: 'Inter', -apple-system, sans-serif;
  position: relative;
  overflow: hidden;
}

/* Шум на фоне для текстуры */
.bg-noise {
  position: absolute; inset: 0; pointer-events: none;
  background: radial-gradient(circle at 50% 0%, rgba(255,255,255,0.03) 0%, transparent 60%);
  z-index: 0;
}

.content-wrapper {
  position: relative; z-index: 1;
  max-width: 900px; margin: 0 auto; padding: 40px 20px;
}

/* --- 2. HEADER --- */
.header-section {
  display: flex; justify-content: space-between; align-items: flex-end;
  margin-bottom: 40px;
}
.page-title {
  font-size: 36px; font-weight: 800; letter-spacing: -0.03em; margin: 0;
  color: #fff;
}
.text-gradient {
  background: linear-gradient(135deg, #fff 30%, #666);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.subtitle {
  color: #666; font-size: 14px; margin-top: 6px; font-weight: 400;
}

/* Кнопка создания */
.create-btn {
  background: #fff; color: #000;
  border: none; padding: 10px 20px; border-radius: 100px;
  font-weight: 600; font-size: 14px;
  display: flex; align-items: center; gap: 8px;
  cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
}
.create-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 15px rgba(255,255,255,0.3);
}
.btn-icon { font-size: 18px; line-height: 1; }

/* --- 3. TABS (Стиль Apple/Linear) --- */
.tabs-wrapper { margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.06); }
.tabs-container { display: flex; gap: 30px; position: relative; }
.tab-btn {
  background: none; border: none; color: #666;
  font-size: 14px; font-weight: 500; padding: 12px 0;
  cursor: pointer; position: relative; transition: color 0.3s;
}
.tab-btn:hover { color: #aaa; }
.tab-btn.active { color: #fff; }
.tab-glow {
  position: absolute; bottom: -1px; left: 0; width: 100%; height: 2px;
  background: #fff; box-shadow: 0 -2px 10px rgba(255,255,255,0.5);
  border-radius: 2px;
}

/* --- 4. LIST & CARDS --- */
.ticket-grid { display: flex; flex-direction: column; gap: 12px; }

.ticket-card {
  position: relative;
  background: rgba(20, 20, 22, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 16px;
  padding: 0; overflow: hidden;
  cursor: pointer; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

/* Эффект при наведении */
.ticket-card:hover {
  background: rgba(30, 30, 35, 0.6);
  border-color: rgba(255, 255, 255, 0.1);
  transform: scale(1.01);
}

.status-line {
  position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
  background: var(--p-color);
  opacity: 0.6; box-shadow: 2px 0 10px var(--p-shadow);
  transition: opacity 0.3s;
}
.ticket-card:hover .status-line { opacity: 1; }

.card-inner { padding: 20px 24px; position: relative; z-index: 2; }

/* Card Header */
.card-header { display: flex; justify-content: space-between; margin-bottom: 8px; }
.ticket-id { font-family: 'Roboto Mono', monospace; font-size: 12px; color: #555; }
.priority-badge { 
  font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;
  color: var(--p-color); display: flex; align-items: center; gap: 6px;
}
.dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; box-shadow: 0 0 5px var(--p-shadow); }

/* Card Content */
.ticket-subject { color: #eee; font-size: 16px; margin: 0 0 6px 0; font-weight: 600; }
.ticket-preview { color: #888; font-size: 14px; margin: 0 0 16px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Card Footer */
.card-footer { 
  display: flex; justify-content: space-between; align-items: center; 
  padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.03); 
}
.date { font-size: 12px; color: #444; }

.status-indicator { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500; }
.status-indicator svg { width: 14px; height: 14px; }
.status-indicator.open { color: #3b82f6; }
.status-indicator.answered { color: #10b981; }
.status-indicator.closed { color: #6b7280; text-decoration: line-through; }

/* --- 5. MODAL (Floating Panel) --- */
.modal-overlay {
  position: fixed; inset: 0; z-index: 100;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
}

.modal-panel {
  width: 100%; max-width: 500px;
  background: #09090b;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 24px;
  padding: 30px;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.8);
  position: relative; overflow: hidden;
}
/* Свечение внутри модалки */
.modal-panel::before {
  content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%);
  width: 200px; height: 1px; background: linear-gradient(90deg, transparent, #fff, transparent);
  opacity: 0.3;
}

.modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.modal-head h2 { margin: 0; font-size: 20px; font-weight: 600; color: #fff; }
.close-btn { background: none; border: none; color: #666; font-size: 18px; cursor: pointer; transition: color 0.2s; }
.close-btn:hover { color: #fff; }

.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-size: 12px; color: #666; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }

.modern-input {
  width: 100%; background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 12px; padding: 14px;
  color: #fff; font-size: 15px; outline: none; transition: 0.2s;
}
.modern-input:focus {
  background: rgba(255,255,255,0.05);
  border-color: rgba(255,255,255,0.2);
  box-shadow: 0 0 0 4px rgba(255,255,255,0.02);
}
.area { resize: vertical; min-height: 120px; font-family: inherit; }

/* Custom Select */
.custom-select {
  position: relative; cursor: pointer;
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
  border-radius: 12px; padding: 14px; display: flex; justify-content: space-between; align-items: center;
  user-select: none;
}
.selected-value { display: flex; align-items: center; gap: 10px; color: #e5e5e5; font-size: 15px; }
.p-dot { width: 8px; height: 8px; border-radius: 50%; display: block; }
.p-dot.sm { width: 6px; height: 6px; }
.chevron { color: #666; font-size: 12px; transition: transform 0.2s; }
.custom-select.active .chevron { transform: rotate(180deg); }

.dropdown-options {
  position: absolute; top: calc(100% + 8px); left: 0; width: 100%;
  background: #111; border: 1px solid rgba(255,255,255,0.1);
  border-radius: 12px; padding: 6px; z-index: 10;
  box-shadow: 0 10px 40px rgba(0,0,0,0.5);
}
.option-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 8px; color: #999;
  font-size: 14px; transition: 0.2s;
}
.option-item:hover { background: rgba(255,255,255,0.05); color: #fff; }
.option-item.selected { background: rgba(255,255,255,0.1); color: #fff; }

.modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 30px; }
.cancel-btn { 
  background: none; border: none; color: #666; font-weight: 500; cursor: pointer; transition: color 0.2s; 
}
.cancel-btn:hover { color: #fff; }

.submit-btn {
  background: #fff; color: #000; border: none; padding: 12px 24px;
  border-radius: 12px; font-weight: 600; cursor: pointer;
  transition: opacity 0.2s;
}
.submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* --- 6. STATES & ANIMATIONS --- */
.loading-state { display: flex; flex-direction: column; gap: 12px; }
.skeleton-row { height: 100px; background: rgba(255,255,255,0.02); border-radius: 16px; animation: pulse 2s infinite; }
@keyframes pulse { 50% { opacity: 0.5; } }

.empty-state { text-align: center; padding: 60px 0; color: #444; }
.empty-icon svg { width: 48px; height: 48px; margin-bottom: 10px; opacity: 0.5; }

/* Transitions */
.staggered-fade-enter-active, .staggered-fade-leave-active { transition: all 0.4s ease; }
.staggered-fade-enter-from, .staggered-fade-leave-to { opacity: 0; transform: translateY(20px); }

.modal-enter-active, .modal-leave-active { transition: opacity 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .modal-panel { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-enter-from .modal-panel { transform: scale(0.95) translateY(10px); }

.dropdown-enter-active, .dropdown-leave-active { transition: all 0.2s; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-10px); }
</style>