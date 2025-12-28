<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useApiFetch, useApi } from '~/composables/useApi' 

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
    await useApi('/api/tickets', {
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
      
      <div class="header-row">
        <h1 class="page-title">Поддержка</h1>
        <div class="actions">
             <button class="text-btn" @click="openModal">
              + Создать
            </button>
        </div>
      </div>

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
          <div class="empty-icon-wrapper">
            <svg viewBox="0 0 30.074 30.075" fill="none" xmlns="http://www.w3.org/2000/svg" class="empty-svg">
               <path d="M1.606,13.595l1.91-0.948L3.952,1.641L2.291,1.575C1.412,1.54,0.672,2.223,0.638,3.102l-0.352,8.869 c-0.017,0.422,0.135,0.833,0.421,1.143C0.946,13.372,1.264,13.536,1.606,13.595z" fill="currentColor"></path> 
               <path d="M10.387,9.051L8.628,7.197c-0.069-0.073-0.091-0.18-0.056-0.275C8.607,6.827,8.692,6.76,8.793,6.75l2.54-0.268 c0.087-0.009,0.164-0.061,0.205-0.137L12.761,4.1c0.049-0.089,0.144-0.143,0.244-0.139c0.101,0.004,0.19,0.065,0.232,0.157 l1.039,2.335c0.036,0.08,0.107,0.137,0.194,0.153l0.891,0.166l4.827-2.394l0.083-2.091L5.895,1.719l-0.394,9.946l4.95-2.456 C10.444,9.151,10.427,9.095,10.387,9.051z" fill="currentColor"></path> 
               <path d="M23.838,2.975c0.479,0,0.938,0.099,1.357,0.273c-0.262-0.468-0.748-0.796-1.323-0.819l-1.66-0.066l-0.041,1.03 l0.098-0.048C22.753,3.104,23.297,2.975,23.838,2.975z" fill="currentColor"></path> 
               <path d="M0.884,16.123c-0.378,0.188-0.666,0.52-0.801,0.917c-0.135,0.399-0.104,0.837,0.082,1.215l4.646,9.361 C5.087,28.176,5.652,28.5,6.237,28.5c0.238,0,0.479-0.054,0.706-0.167l1.984-0.982L2.868,15.14L0.884,16.123z" fill="currentColor"></path> 
               <path d="M29.909,15.165l-4.646-9.362c-0.275-0.56-0.842-0.884-1.427-0.884c-0.236,0-0.479,0.053-0.706,0.167L21.147,6.07 l6.06,12.211l1.983-0.983c0.378-0.188,0.667-0.519,0.801-0.917C30.126,15.981,30.097,15.543,29.909,15.165z" fill="currentColor"></path> 
               <path d="M4.608,14.276l6.06,12.21l14.799-7.342l-6.06-12.212L4.608,14.276z M19.854,19.577c-0.047,0.09-0.142,0.146-0.24,0.145 l-2.979-0.069c-0.087-0.002-0.169,0.039-0.221,0.109l-1.746,2.412c-0.06,0.081-0.16,0.122-0.26,0.104 c-0.1-0.017-0.182-0.088-0.211-0.187l-0.853-2.854c-0.024-0.083-0.089-0.149-0.172-0.176l-2.834-0.915 c-0.096-0.031-0.167-0.114-0.182-0.214c-0.015-0.102,0.029-0.2,0.112-0.258l2.448-1.692c0.072-0.049,0.115-0.131,0.115-0.219 l-0.007-2.978c0-0.101,0.057-0.194,0.148-0.239c0.09-0.045,0.198-0.035,0.278,0.026l2.368,1.807 c0.069,0.053,0.16,0.069,0.243,0.041l2.832-0.926c0.096-0.031,0.2-0.005,0.271,0.067c0.07,0.072,0.095,0.179,0.062,0.274 l-1.032,2.94l1.837,2.517C19.892,19.378,19.899,19.488,19.854,19.577z" fill="currentColor"></path>
            </svg>
          </div>
          <h3>Список пуст</h3>
          <p>У вас еще нет созданных обращений</p>
        </div>
      </div>
    </div>

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

.icon-box {
  width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,0.05); color: #666;
  border: 1px solid transparent; z-index: 2;
}
.icon-box svg { width: 20px; height: 20px; }

.ticket-card.answered .icon-box { color: #22c55e; background: rgba(34, 197, 94, 0.1); }
.ticket-card.open .icon-box { color: #3b82f6; background: rgba(59, 130, 246, 0.1); }

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

.action-btn {
  width: 30px; height: 30px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: rgba(255, 255, 255, 0.1); transition: all 0.2s ease;
}
.ticket-card:hover .action-btn { color: rgba(255, 255, 255, 0.4); transform: translateX(4px); }

/* --- ПУСТОЕ СОСТОЯНИЕ (EMPTY STATE) --- */
.empty-placeholder {
  text-align: center;
  padding: 80px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.empty-icon-wrapper {
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 84px;
  height: 84px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  color: #444;
}
.empty-svg {
  width: 38px;
  height: 38px;
  opacity: 0.6;
}
.empty-placeholder h3 { color: #fff; font-size: 18px; margin: 0 0 8px 0; font-weight: 600; }
.empty-placeholder p { font-size: 14px; color: #555; }

/* МОДАЛКА */
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

.priority-select { position: relative; }
.select-trigger { display: flex; align-items: center; justify-content: space-between; cursor: pointer; }
.selected-val { display: flex; align-items: center; gap: 10px; }
.status-dot { width: 8px; height: 8px; border-radius: 50%; }

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

.submit-btn {
  width: 100%; background: #fff; color: #000; font-weight: 700;
  padding: 14px; border: none; border-radius: 12px; cursor: pointer; margin-top: 5px; transition: 0.2s;
}
.submit-btn:hover:not(:disabled) { background: #e0e0e0; }

.list-move, .list-enter-active, .list-leave-active { transition: all 0.4s ease; }
.list-leave-to { opacity: 0; transform: translateX(30px); }
.list-enter-from { opacity: 0; transform: translateX(-30px); }
.skeleton-card { height: 86px; background: rgba(255,255,255,0.03); border-radius: 18px; animation: pulse 1.5s infinite; margin-bottom: 10px; }
@keyframes pulse { 50% { opacity: 0.5; } }
</style>