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
type TicketPriority = 'low' | 'medium' | 'high'

interface Ticket {
  id: number
  subject: string
  priority: TicketPriority // Используем priority
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
    return new Date(iso).toLocaleDateString('ru-RU', { 
      day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' 
    })
  } catch (e) { return iso }
}

// Конфигурация приоритетов
const priorityConfig: Record<TicketPriority, { label: string, color: string, bg: string }> = {
  low:    { label: 'Низкий',    color: '#10b981', bg: 'rgba(16, 185, 129, 0.15)' }, 
  medium: { label: 'Средний',   color: '#f59e0b', bg: 'rgba(245, 158, 11, 0.15)' }, 
  high:   { label: 'Высокий',   color: '#ef4444', bg: 'rgba(239, 68, 68, 0.15)' }   
}

const getStatusIcon = (status: TicketStatus) => {
  if (status === 'answered') return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
  if (status === 'open') return 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
  return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
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
    <div class="content-wrapper">
      
      <!-- ЗАГОЛОВОК -->
      <div class="header-row">
        <h1 class="page-title">Поддержка</h1>
        <div class="actions">
             <button class="text-btn" @click="openModal">
              + Создать
            </button>
        </div>
      </div>

      <!-- ВКЛАДКИ -->
      <div class="inline-tabs">
        <button 
          v-for="tab in ['all', 'open', 'closed']" 
          :key="tab"
          @click="currentTab = tab as any"
          class="tab-link"
          :class="{ active: currentTab === tab }"
        >
          {{ tab === 'all' ? 'Все' : tab === 'open' ? 'В работе' : 'Архив' }}
        </button>
      </div>

      <!-- СПИСОК -->
      <div class="list-container">
        <div v-if="pending" class="loading-state">
          <div class="skeleton-card" v-for="i in 3" :key="i"></div>
        </div>

        <TransitionGroup name="list" tag="div" v-else-if="filteredTickets.length">
          <div 
            v-for="ticket in filteredTickets" 
            :key="ticket.id" 
            class="ticket-card"
            :class="ticket.status"
            @click="openTicket(ticket.id)"
            :style="{ '--p-color': priorityConfig[ticket.priority].color }" 
          >
            <!-- Свечение и бордер -->
            <div class="priority-border"></div>
            <div class="ambient-glow"></div>

            <div class="icon-box">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getStatusIcon(ticket.status)" />
              </svg>
            </div>

            <div class="card-content">
              <div class="top-row">
                <span class="card-title">{{ ticket.subject }}</span>
                <span 
                  class="priority-badge"
                  :style="{ color: priorityConfig[ticket.priority].color, background: priorityConfig[ticket.priority].bg }"
                >
                  {{ priorityConfig[ticket.priority].label }}
                </span>
              </div>
              
              <p class="card-message">{{ ticket.preview }}</p>

              <div class="meta-row">
                  <span class="card-date">{{ formatDate(ticket.lastUpdate) }}</span>
              </div>
            </div>

            <div class="action-btn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M9 18l6-6-6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>

          </div>
        </TransitionGroup>

        <div v-else class="empty-placeholder">
          <div class="empty-circle">📂</div>
          <h3>Пусто</h3>
          <p>Тикетов нет</p>
        </div>
      </div>
    </div>

    <!-- МОДАЛЬНОЕ ОКНО -->
    <Transition name="fade">
      <div v-if="isModalOpen" class="modal-backdrop" @click.self="closeModal">
        <div class="modal-window">
          <div class="modal-glow"></div> 
          <div class="modal-header">
            <h2>Новый запрос</h2>
            <button class="close-modal-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="createTicket" class="ticket-form">
            <input
              v-model="newTicketForm.subject"
              type="text"
              placeholder="Тема обращения..."
              class="glass-input mb-3"
              autoFocus
            >

            <div class="priority-select mb-3">
              <div
                class="glass-input select-trigger"
                tabindex="0"
                @click="togglePriority"
                @keydown.enter.prevent="togglePriority"
              >
                <div class="selected-val">
                  <span class="status-dot" :style="{ background: currentPriorityColor }"></span>
                  <span>Приоритет: <span :style="{ color: currentPriorityColor }">{{ currentPriorityLabel }}</span></span>
                </div>
                <span class="caret" :class="{ open: isPriorityOpen }">▾</span>
              </div>

              <transition name="slide-down">
                <ul v-if="isPriorityOpen" class="select-options">
                  <li
                    v-for="(conf, key, idx) in priorityConfig"
                    :key="key"
                    :class="{ active: newTicketForm.priority === key }"
                    :style="{ '--i': idx }"
                    @click.stop="selectPriority(key)"
                  >
                    <span class="status-dot" :style="{ background: conf.color }"></span>
                    <span>{{ conf.label }}</span>
                    <span v-if="newTicketForm.priority === key" class="check-mark">✓</span>
                  </li>
                </ul>
              </transition>
            </div>

            <textarea
              v-model="newTicketForm.message"
              rows="4"
              placeholder="Опишите проблему..."
              class="glass-input area mb-3"
            ></textarea>

            <button type="submit" class="submit-btn" :disabled="isSubmitting">
              Создать тикет
            </button>
          </form>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* --- BASE LAYOUT (Как в уведомлениях) --- */
.tickets-page { width: 100%; max-width: 850px; padding-bottom: 80px; }
.list-container { position: relative; margin-top: 20px; }

.header-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px; }
.page-title { color: #fff; font-size: 32px; font-weight: 700; margin: 0; }

.inline-tabs { display: flex; gap: 18px; margin-bottom: 20px; }
.tab-link { background: none; border: none; color: #666; font-size: 15px; font-weight: 500; cursor: pointer; padding: 0; transition: 0.2s; }
.tab-link:hover { color: #aaa; }
.tab-link.active { color: #fff; text-decoration: underline; text-underline-offset: 5px; }

.text-btn { background: none; border: none; color: #666; font-size: 14px; cursor: pointer; padding: 5px 10px; transition: 0.2s; }
.text-btn:hover { color: #fff; }

/* --- TICKET CARD (Стиль уведомлений) --- */
.ticket-card {
  position: relative;
  display: flex; align-items: center; gap: 16px;
  background: rgba(255, 255, 255, 0.02); 
  border: 1px solid rgba(255, 255, 255, 0.05);
  padding: 16px 20px;
  margin-bottom: 10px;
  border-radius: 16px;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.2s ease;
  width: 100%; box-sizing: border-box;
}

.ticket-card:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.1);
  transform: translateY(-1px);
}

/* Приоритет слева */
.priority-border {
  position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
  background: var(--p-color); opacity: 0.7; box-shadow: 2px 0 15px var(--p-color);
}

.ambient-glow {
  position: absolute; left: 0; top: 0; bottom: 0; width: 120px;
  background: radial-gradient(circle at left center, var(--glow-color, #666), transparent 70%);
  opacity: 0.15; pointer-events: none; transition: opacity 0.3s;
}
.ticket-card:hover .ambient-glow { opacity: 0.25; }

/* Иконки */
.icon-box {
  width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,0.05); color: #666;
  border: 1px solid transparent; z-index: 2;
}
.icon-box svg { width: 20px; height: 20px; }

.ticket-card.answered .icon-box { color: #22c55e; background: rgba(34, 197, 94, 0.1); }
.ticket-card.open .icon-box { color: #3b82f6; background: rgba(59, 130, 246, 0.1); }

/* Контент */
.card-content { flex-grow: 1; z-index: 2; display: flex; flex-direction: column; justify-content: center; overflow: hidden; }
.top-row { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
.card-title { color: #fff; font-size: 14px; font-weight: 500; }

.priority-badge {
  font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
  padding: 2px 6px; border-radius: 5px;
}

.card-message { color: #888; font-size: 13px; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 95%; }
.meta-row { margin-top: 4px; }
.card-date { color: #555; font-size: 12px; white-space: nowrap; }

/* Стрелка действия справа */
.action-btn {
  width: 30px; height: 30px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: rgba(255, 255, 255, 0.1); transition: all 0.2s ease;
}
.ticket-card:hover .action-btn { color: rgba(255, 255, 255, 0.4); transform: translateX(4px); }


/* --- MODAL & FORM --- */
.modal-backdrop {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.7); backdrop-filter: blur(5px);
  z-index: 100; display: flex; align-items: center; justify-content: center;
}
.modal-window {
  position: relative; background: #09090b; border: 1px solid rgba(255,255,255,0.1);
  width: 100%; max-width: 480px; padding: 30px; border-radius: 20px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.5);
}
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.modal-header h2 { color: #fff; margin: 0; font-size: 20px; }
.close-modal-btn { background: none; border: none; color: #666; font-size: 20px; cursor: pointer; }
.close-modal-btn:hover { color: #fff; }

.mb-3 { margin-bottom: 14px; }
.glass-input {
  width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
  padding: 12px 16px; border-radius: 12px; color: #fff; font-size: 14px; outline: none;
}
.glass-input:focus { border-color: #3b82f6; background: rgba(59, 130, 246, 0.05); }
.area { resize: vertical; min-height: 100px; font-family: inherit; }

/* SELECTOR */
.priority-select { position: relative; }
.select-trigger { display: flex; align-items: center; justify-content: space-between; cursor: pointer; }
.selected-val { display: flex; align-items: center; gap: 10px; }
.status-dot { width: 8px; height: 8px; border-radius: 50%; box-shadow: 0 0 8px inherit; }

.select-options {
  position: absolute; top: calc(100% + 6px); left: 0; right: 0;
  background: #121215; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px;
  padding: 6px; list-style: none; margin: 0; z-index: 20; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}
.select-options li {
  display: flex; align-items: center; gap: 10px; padding: 10px 12px;
  border-radius: 8px; color: #ccc; cursor: pointer; font-size: 14px; transition: 0.2s;
}
.select-options li:hover { background: rgba(255,255,255,0.05); color: #fff; }
.select-options li.active { background: rgba(255,255,255,0.08); color: #fff; }
.check-mark { margin-left: auto; color: #3b82f6; }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.2s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-10px); }

.submit-btn {
  width: 100%; background: #fff; color: #000; font-weight: 700;
  padding: 14px; border: none; border-radius: 12px; cursor: pointer; margin-top: 5px; transition: 0.2s;
}
.submit-btn:hover:not(:disabled) { background: #e0e0e0; }
.submit-btn:disabled { background: #444; color: #888; cursor: not-allowed; }

/* ANIMATIONS */
.list-move, .list-enter-active, .list-leave-active { transition: all 0.4s ease; }
.list-leave-to { opacity: 0; transform: translateX(30px); }
.list-enter-from { opacity: 0; transform: translateX(-30px); }
.loading-state { display: flex; flex-direction: column; gap: 12px; }
.skeleton-card { height: 86px; background: rgba(255,255,255,0.03); border-radius: 18px; animation: pulse 1.5s infinite; }
@keyframes pulse { 50% { opacity: 0.5; } }
.empty-placeholder { text-align: center; padding: 50px 0; color: #555; }
.empty-circle { font-size: 28px; margin-bottom: 8px; opacity: 0.4; filter: grayscale(1); }
</style>
