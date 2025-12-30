<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useApiFetch, useApi } from '~/composables/useApi'

// ИМПОРТ ИКОНОК
import IconTicket from '~/assets/icons/ticket.svg?component'
import IconArrow from '~/assets/icons/arrow-right.svg?component'
import IconBox from '~/assets/icons/box.svg?component'

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

// --- КОНФИГУРАЦИЯ ---
const priorityConfig: Record<TicketPriority, { label: string, color: string, bg: string }> = {
  low:    { label: 'Низкий',    color: '#10b981', bg: 'rgba(16, 185, 129, 0.15)' },
  medium: { label: 'Средний',   color: '#f59e0b', bg: 'rgba(245, 158, 11, 0.15)' },
  high:   { label: 'Высокий',   color: '#ef4444', bg: 'rgba(239, 68, 68, 0.15)' }
}

const statusConfig: Record<TicketStatus, { label: string, color: string }> = {
  open:     { label: 'Открыт', color: '#3b82f6' },
  answered: { label: 'Ответ',  color: '#10b981' },
  closed:   { label: 'Закрыт', color: '#666' }
}

// --- ДАННЫЕ ---
const { data: ticketsData, pending, refresh } = await useApiFetch<Ticket[]>('/api/tickets')
const tickets = computed(() => ticketsData.value || [])

// --- ФИЛЬТРАЦИЯ ---
const currentTab = ref<'all' | 'open' | 'closed'>('all')

const filteredTickets = computed(() => {
  let sorted = [...tickets.value]
  const priorityWeight = { high: 3, medium: 2, low: 1 }
  sorted.sort((a, b) => priorityWeight[b.priority] - priorityWeight[a.priority])

  if (currentTab.value === 'all') return sorted
  if (currentTab.value === 'open') return sorted.filter(t => t.status !== 'closed')
  if (currentTab.value === 'closed') return sorted.filter(t => t.status === 'closed')
  return sorted
})

// --- МОДАЛЬНОЕ ОКНО ---
const isModalOpen = ref(false)
const isSubmitting = ref(false)
const newTicketForm = ref({ subject: '', priority: 'low' as TicketPriority, message: '' })

const openModal = () => isModalOpen.value = true
const closeModal = () => {
  isModalOpen.value = false
  setTimeout(() => { newTicketForm.value = { subject: '', priority: 'low', message: '' } }, 300)
}

const setPriority = (val: TicketPriority) => {
  newTicketForm.value.priority = val
}

const createTicket = async () => {
  if (!newTicketForm.value.subject.trim() || !newTicketForm.value.message.trim()) {
    alert('Заполните все поля')
    return
  }
  isSubmitting.value = true
  try {
    await useApi('/api/tickets', { method: 'POST', body: newTicketForm.value })
    await refresh()
    closeModal()
  } catch (e) {
    console.error(e)
    alert('Ошибка создания тикета')
  } finally {
    isSubmitting.value = false
  }
}

const openTicket = (id: number) => router.push(`/dashboard/tickets/${id}`)
const formatDate = (iso: string) => {
  if (!iso) return '—'
  try { return new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' }) } catch { return iso }
}
</script>

<template>
  <div class="tickets-page">
    <div class="content-wrapper">
      
      <div class="page-header flex justify-between items-end">
        <div>
          <h1>Поддержка</h1>
          <p>Центр помощи и ваших обращений</p>
        </div>
        <button class="black-btn compact" @click="openModal">
          <span>+ Новый тикет</span>
        </button>
      </div>

      <div class="tabs-row">
        <button 
          v-for="tab in ['all', 'open', 'closed']" 
          :key="tab"
          class="tab-btn"
          :class="{ active: currentTab === tab }"
          @click="currentTab = tab as any"
        >
          {{ tab === 'all' ? 'Все' : tab === 'open' ? 'В работе' : 'Архив' }}
        </button>
      </div>

      <div class="tickets-list-section">
        
        <div v-if="pending" class="tickets-grid">
           <div class="ticket-item skeleton" v-for="i in 3" :key="i"></div>
        </div>

        <div v-else-if="filteredTickets.length === 0" class="empty-state">
           <div class="empty-icon-wrapper">
              <IconTicket class="empty-svg" />
           </div>
           <h3>Обращений не найдено</h3>
           <p>В этой категории пока пусто</p>
        </div>

        <div v-else class="tickets-grid">
          <div 
            v-for="ticket in filteredTickets" 
            :key="ticket.id" 
            class="ticket-item"
            @click="openTicket(ticket.id)"
          >
            <div class="tx-icon" :style="{ color: statusConfig[ticket.status].color, background: statusConfig[ticket.status].color + '1a' }">
              <IconBox v-if="ticket.status === 'open'" class="icon-svg" />
              <IconArrow v-else-if="ticket.status === 'answered'" class="icon-svg rotate-45" />
              <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </div>

            <div class="tx-info">
              <div class="tx-title">{{ ticket.subject }}</div>
              <div class="tx-desc">{{ ticket.preview || 'Нет описания...' }}</div>
            </div>

            <div class="meta-col">
               <div class="badge" :style="{ color: priorityConfig[ticket.priority].color, background: priorityConfig[ticket.priority].bg }">
                  {{ priorityConfig[ticket.priority].label }}
               </div>
               <span class="tx-date">{{ formatDate(ticket.lastUpdate) }}</span>
            </div>

             <div class="arrow-hint">
                <IconArrow class="icon-svg" />
             </div>
          </div>
        </div>

      </div>

    </div>

    <Transition name="modal-fade">
      <div v-if="isModalOpen" class="modal-backdrop" @click.self="closeModal">
        <div class="dark-modal">
          
          <div class="modal-header">
             <h2>Новое обращение</h2>
             <button class="close-btn" @click="closeModal">✕</button>
          </div>

          <form @submit.prevent="createTicket" class="modal-form">
             <div class="form-group">
                <label>Тема запроса</label>
                <input 
                  v-model="newTicketForm.subject" 
                  type="text" 
                  placeholder="Пример: Проблема с сервером..." 
                  class="dark-input"
                >
             </div>

             <div class="form-group">
                <label>Приоритет</label>
                <div class="priority-grid">
                   <div 
                      v-for="(conf, key) in priorityConfig" 
                      :key="key"
                      class="priority-card"
                      :class="{ active: newTicketForm.priority === key }"
                      @click="setPriority(key)"
                   >
                      <div class="p-dot" :style="{ background: conf.color }"></div>
                      <span>{{ conf.label }}</span>
                   </div>
                </div>
             </div>

             <div class="form-group">
                <label>Детальное описание</label>
                <textarea 
                   v-model="newTicketForm.message" 
                   rows="5" 
                   placeholder="Опишите проблему максимально подробно..." 
                   class="dark-input area"
                ></textarea>
             </div>

             <div class="modal-actions">
                <button type="button" class="black-btn secondary" @click="closeModal">Отмена</button>
                <button type="submit" class="black-btn" :disabled="isSubmitting">
                   <span v-if="!isSubmitting">Создать запрос</span>
                   <span v-else>Отправка...</span>
                </button>
             </div>
          </form>

        </div>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
/* --- ОСНОВА --- */
.tickets-page { position: relative; width: 100%; max-width: 1100px; margin: 0; padding-bottom: 80px; }
.content-wrapper { position: relative; z-index: 10; }

.page-header { margin-bottom: 30px; }
.page-header h1 { font-size: 32px; font-weight: 700; color: white; margin: 0; }
.page-header p { color: #888; font-size: 13px; margin-top: 4px; }

/* --- ТАБЫ --- */
.tabs-row { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; }
.tab-btn { background: transparent; border: 1px solid transparent; color: #666; padding: 6px 16px; border-radius: 20px; font-size: 13px; cursor: pointer; transition: 0.2s; }
.tab-btn:hover { color: #fff; background: rgba(255,255,255,0.05); }
.tab-btn.active { color: #fff; background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.1); }

/* --- СПИСОК --- */
.tickets-grid { display: flex; flex-direction: column; gap: 12px; }
.ticket-item { 
  display: flex; align-items: center; gap: 15px; 
  background: rgba(20, 20, 20, 0.6); border: 1px solid rgba(255,255,255,0.05); 
  padding: 16px 20px; border-radius: 16px; transition: 0.2s; cursor: pointer; 
}
.ticket-item:hover { background: rgba(30, 30, 30, 0.8); border-color: rgba(255,255,255,0.1); transform: translateX(4px); }

.tx-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.tx-info { flex-grow: 1; min-width: 0; }
.tx-title { color: white; font-size: 15px; font-weight: 600; margin-bottom: 2px; }
.tx-desc { color: #777; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.meta-col { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.badge { font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 2px 8px; border-radius: 6px; }
.tx-date { color: #555; font-size: 12px; }

.arrow-hint { color: #333; transition: 0.2s; }
.ticket-item:hover .arrow-hint { color: #fff; }

/* --- DARK MODAL (Новый стиль) --- */
.modal-backdrop { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 20px; }

.dark-modal { 
  width: 100%; max-width: 500px; 
  background: #050505; /* Глубокий черный */
  border: 1px solid #1a1a1a; 
  border-radius: 20px; 
  padding: 32px; /* Увеличенный отступ */
  position: relative; overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.9);
  /* Гарантирует правильный расчет ширины модалки */
  box-sizing: border-box; 
}

.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.modal-header h2 { font-size: 22px; color: white; margin: 0; font-weight: 700; letter-spacing: -0.5px; }
.close-btn { background: transparent; border: none; color: #666; font-size: 24px; cursor: pointer; transition: 0.2s; padding: 0; line-height: 1; }
.close-btn:hover { color: white; }

.modal-form { display: flex; flex-direction: column; gap: 20px; }
.form-group label { display: block; color: #888; font-size: 12px; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

/* Dark Inputs - ИСПРАВЛЕНО ЗДЕСЬ */
.dark-input { 
  width: 100%; 
  background: #0a0a0a; 
  border: 1px solid #222; 
  padding: 14px 16px; 
  border-radius: 12px; 
  color: white; 
  font-size: 14px; 
  transition: 0.2s; 
  outline: none; 
  
  /* Исправляет вылезание за границы (padding включается в width) */
  box-sizing: border-box; 
}

.dark-input:focus { border-color: #444; background: #0f0f0f; }
.area { resize: vertical; min-height: 120px; font-family: inherit; line-height: 1.5; }

/* Priority Grid */
.priority-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
.priority-card { 
  background: #0a0a0a; border: 1px solid #222; 
  border-radius: 12px; padding: 12px; cursor: pointer; transition: 0.2s; 
  display: flex; flex-direction: column; align-items: center; gap: 8px; justify-content: center;
  
  /* Для надежности тоже добавляем */
  box-sizing: border-box;
}
.priority-card:hover { border-color: #444; background: #0f0f0f; }
.priority-card.active { border-color: #fff; background: #111; }
.p-dot { width: 8px; height: 8px; border-radius: 50%; }
.priority-card span { font-size: 12px; font-weight: 600; color: #aaa; }
.priority-card.active span { color: #fff; }

/* Actions */
.modal-actions { display: flex; gap: 12px; margin-top: 10px; }

/* Black Buttons */
.black-btn { 
  flex: 1; display: inline-flex; align-items: center; justify-content: center; 
  background: #fff; color: #000; border: none; 
  border-radius: 12px; font-size: 14px; font-weight: 700; 
  padding: 14px 20px; cursor: pointer; transition: all 0.2s ease; 
}
.black-btn:hover:not(:disabled) { background: #e5e5e5; transform: translateY(-1px); }
.black-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.black-btn.secondary { background: transparent; border: 1px solid #333; color: #fff; }
.black-btn.secondary:hover { border-color: #666; background: rgba(255,255,255,0.02); }

.black-btn.compact { flex: initial; padding: 10px 20px; font-size: 13px; }

/* Empty & Skeleton */
.empty-state { text-align: center; padding: 40px; background: rgba(255,255,255,0.01); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.1); }
.empty-icon-wrapper { color: #444; margin-bottom: 10px; } 
.empty-svg { width: 40px; height: 40px; opacity: 0.5; }
.empty-state h3 { font-size: 16px; color: white; margin: 0 0 5px 0; }
.empty-state p { font-size: 13px; color: #666; margin: 0; }

.skeleton { height: 74px; background: rgba(255,255,255,0.03); animation: pulse 1.5s infinite; }
@keyframes pulse { 50% { opacity: 0.5; } }
.icon-svg { width: 20px; height: 20px; }
.rotate-45 { transform: rotate(-45deg); }

/* Animation */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-fade-enter-active .dark-modal { animation: scaleIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes scaleIn { from { transform: scale(0.95) translateY(10px); } to { transform: scale(1) translateY(0); } }
</style>