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

const getStatusIcon = (status: TicketStatus) => {
  if (status === 'answered') return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' // Success
  if (status === 'open') return 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' // Info
  return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' // Closed
}

const getDepartmentLabel = (dept: string) => {
   const map: Record<string, string> = { tech: 'Техн.', billing: 'Бух.', other: 'Общее' }
   return map[dept] || dept
}
</script>

<template>
  <div class="tickets-page">
    <div class="content-wrapper">
      
      <div class="header-row">
        <div>
           <h1 class="page-title">Поддержка</h1>
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
        </div>
        
        <button class="create-link-btn" @click="openModal">
           + Создать
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
          >
            <div class="ambient-glow"></div>

            <div class="icon-box">
               <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getStatusIcon(ticket.status)" />
               </svg>
            </div>

            <div class="card-content">
               <div class="top-row">
                 <span class="card-title">{{ ticket.subject }}</span>
                 <span class="card-date">{{ formatDate(ticket.lastUpdate) }}</span>
               </div>
               <div class="meta-row">
                 <span class="dept-text">[{{ getDepartmentLabel(ticket.department) }}]</span>
                 <p class="card-message">{{ ticket.preview }}</p>
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

    <Transition name="fade">
      <div v-if="isModalOpen" class="modal-backdrop" @click.self="closeModal">
        <div class="modal-window">
          <div class="modal-glow"></div> 
          <div class="modal-header">
            <h2>Новый запрос</h2>
            <button class="close-modal-btn" @click="closeModal">✕</button>
          </div>
          <form @submit.prevent="createTicket" class="ticket-form">
            <input v-model="newTicketForm.subject" type="text" placeholder="Тема..." class="glass-input mb-3" autoFocus>
            <select v-model="newTicketForm.department" class="glass-input mb-3">
               <option value="tech">Технический отдел</option>
               <option value="billing">Бухгалтерия</option>
               <option value="other">Другое</option>
            </select>
            <textarea v-model="newTicketForm.message" rows="4" placeholder="Сообщение..." class="glass-input area mb-3"></textarea>
            <button type="submit" class="submit-btn" :disabled="isSubmitting">Создать</button>
          </form>
        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
/* --- BASE LAYOUT --- */
.tickets-page { width: 100%; max-width: 900px; padding-bottom: 80px; } /* Ширина чуть меньше гигантской, но больше оригинала */
.content-wrapper { position: relative; z-index: 10; }

.header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
.page-title { color: #fff; font-size: 32px; font-weight: 700; margin: 0 0 8px 0; }

/* Inline Tabs */
.inline-tabs { display: flex; gap: 18px; }
.tab-link {
    background: none; border: none; color: #666; 
    font-size: 15px; /* Средний размер */
    font-weight: 500;
    cursor: pointer; padding: 0; transition: 0.2s;
}
.tab-link:hover { color: #aaa; }
.tab-link.active { color: #fff; text-decoration: underline; text-underline-offset: 5px; }

.create-link-btn {
    background: none; border: 1px solid rgba(255,255,255,0.1); 
    color: #fff; 
    font-size: 14px; 
    font-weight: 500;
    padding: 8px 20px; 
    border-radius: 24px;
    cursor: pointer; transition: 0.2s;
}
.create-link-btn:hover { background: rgba(255,255,255,0.05); border-color: #fff; }

/* --- TICKET CARD (Сбалансированный размер) --- */
.ticket-card {
  position: relative;
  display: flex; align-items: center; gap: 20px;
  background: rgba(255, 255, 255, 0.02); 
  border: 1px solid rgba(255, 255, 255, 0.05);
  
  /* ЗОЛОТАЯ СЕРЕДИНА */
  padding: 20px 24px; 
  margin-bottom: 12px;
  border-radius: 18px;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.2s ease;
}

.ticket-card:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.1);
  transform: translateY(-2px);
}

/* 1. AMBIENT GLOW */
.ambient-glow {
  position: absolute; left: 0; top: 0; bottom: 0; width: 130px;
  background: radial-gradient(circle at left center, var(--glow-color), transparent 70%);
  opacity: 0.15; pointer-events: none; transition: opacity 0.3s;
}
.ticket-card:hover .ambient-glow { opacity: 0.25; }
.ticket-card.closed .ambient-glow { opacity: 0; }

/* 2. ICON BOX (Размер 48px - оптимально) */
.icon-box {
  width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,0.05);
  color: #666;
  border: 1px solid transparent;
  transition: 0.3s; z-index: 2;
}
.icon-box svg { width: 24px; height: 24px; }

/* ЦВЕТА СТАТУСОВ */
.ticket-card.answered { --glow-color: #22c55e; } 
.ticket-card.answered .icon-box { color: #22c55e; background: rgba(34, 197, 94, 0.1); }

.ticket-card.open { --glow-color: #3b82f6; } 
.ticket-card.open .icon-box { color: #3b82f6; background: rgba(59, 130, 246, 0.1); }

.ticket-card.closed { --glow-color: #666; opacity: 0.8; } 
.ticket-card.closed .icon-box { color: #666; background: rgba(255, 255, 255, 0.05); }

/* 3. CONTENT */
.card-content { flex-grow: 1; z-index: 2; display: flex; flex-direction: column; overflow: hidden; }
.top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }

.card-title { 
    color: #fff; 
    font-size: 16px; /* Не мелко, не крупно */
    font-weight: 600; 
}
.card-date { 
    color: #555; 
    font-size: 13px; 
    margin-left: 12px; white-space: nowrap; 
}

.meta-row { display: flex; align-items: center; gap: 8px; }
.dept-text { 
    font-size: 13px; 
    color: #777; font-weight: 600; white-space: nowrap;
}
.card-message { 
    color: #999; 
    font-size: 14px; /* Хорошо читаемый текст */
    line-height: 1.4;
    margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 90%; 
}

/* 4. ACTION BUTTON */
.action-btn {
  width: 34px; height: 34px; 
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: rgba(255, 255, 255, 0.1);
  transition: all 0.2s ease; z-index: 10;
}
.action-btn svg { width: 20px; height: 20px; }
.ticket-card:hover .action-btn { color: rgba(255, 255, 255, 0.4); transform: translateX(4px); }

/* --- MODAL (Тоже чуть компактнее) --- */
.modal-backdrop {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.7); backdrop-filter: blur(5px);
  z-index: 100; display: flex; align-items: center; justify-content: center;
}
.modal-window {
  position: relative; background: #09090b; border: 1px solid rgba(255,255,255,0.1);
  width: 100%; max-width: 500px; padding: 30px; border-radius: 20px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.5); overflow: hidden;
}
.modal-glow {
    position: absolute; top: 0; left: 50%; transform: translateX(-50%);
    width: 60%; height: 5px; background: #3b82f6; box-shadow: 0 0 40px 10px #3b82f6; opacity: 0.3; pointer-events: none;
}
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; position: relative; z-index: 2; }
.modal-header h2 { color: #fff; margin: 0; font-size: 20px; }
.close-modal-btn { background: none; border: none; color: #666; font-size: 20px; cursor: pointer; }
.close-modal-btn:hover { color: #fff; }

.mb-3 { margin-bottom: 14px; }
.glass-input {
  width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
  padding: 12px 16px; border-radius: 12px; color: #fff; font-size: 14px; outline: none; box-sizing: border-box;
}
.glass-input:focus { border-color: #3b82f6; background: rgba(59, 130, 246, 0.05); }
.area { resize: vertical; min-height: 100px; font-family: inherit; }
.submit-btn {
  width: 100%; background: #fff; color: #000; font-weight: 600; font-size: 14px;
  padding: 14px; border: none; border-radius: 12px; cursor: pointer; transition: 0.2s; margin-top: 5px;
}
.submit-btn:hover:not(:disabled) { background: #e5e5e5; }
.submit-btn:disabled { background: #555; cursor: not-allowed; }

/* --- ANIMATION --- */
.loading-state { display: flex; flex-direction: column; gap: 12px; }
.skeleton-card { height: 86px; background: rgba(255,255,255,0.03); border-radius: 18px; animation: pulse 1.5s infinite; }
@keyframes pulse { 0% { opacity: 0.5; } 50% { opacity: 1; } 100% { opacity: 0.5; } }

.list-enter-active, .list-leave-active { transition: all 0.4s ease; }
.list-leave-to { opacity: 0; transform: translateX(30px); }
.list-enter-from { opacity: 0; transform: translateX(-30px); }
.empty-placeholder { text-align: center; padding: 50px 0; color: #555; }
.empty-circle { font-size: 28px; margin-bottom: 8px; opacity: 0.4; filter: grayscale(1); }
</style>