<script setup lang="ts">
import { computed } from 'vue'
import { useApiFetch, useApi } from '~/composables/useApi'

definePageMeta({
  layout: 'dashboard'
})

interface Notification {
  id: number
  title: string
  message: string
  type: 'info' | 'success' | 'warning' | 'error'
  created_at: string
  is_read: boolean
}

// --- ЗАГРУЗКА ДАННЫХ С БЭКЕНДА ---
const { data: notificationsData, refresh } = await useApiFetch<Notification[]>('/api/notifications')
const notifications = computed(() => notificationsData.value || [])

// --- МЕТОДЫ ---

const formatDate = (isoDate: string) => {
  if (!isoDate) return ''
  const date = new Date(isoDate)
  return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' })
}

const markAsRead = async (id: number) => {
  const note = notifications.value.find(n => n.id === id)
  if (note && !note.is_read) {
    note.is_read = true
    try {
      await useApi(`/api/notifications/${id}/read`, { method: 'POST' })
    } catch (e) {
      console.error('Ошибка обновления статуса', e)
    }
  }
}

const markAllRead = async () => {
  notifications.value.forEach(n => n.is_read = true)
  try {
    await useApi('/api/notifications/read-all', { method: 'POST' })
  } catch (e) {
    console.error(e)
  }
}

const deleteNotification = async (id: number) => {
  try {
    await useApi(`/api/notifications/${id}`, { method: 'DELETE' })
    await refresh()
  } catch (e) {
    console.error(e)
  }
}

const deleteAll = async () => {
  if (!confirm('Удалить все уведомления?')) return
  try {
    await useApi('/api/notifications', { method: 'DELETE' })
    await refresh()
  } catch (e) {
    console.error(e)
  }
}

const getIcon = (type: string) => {
  if (type === 'success') return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
  if (type === 'error') return 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
  if (type === 'warning') return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
  return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
}
</script>

<template>
  <div class="notifications-page">
    <div class="content-wrapper">
      <div class="header-row">
        <h1 class="page-title">Центр уведомлений</h1>
        <div class="actions" v-if="notifications.length">
          <button @click="markAllRead" class="text-btn">Прочитать все</button>
          <button @click="deleteAll" class="text-btn delete">Очистить</button>
        </div>
      </div>

      <div class="list-container" v-if="notifications.length">
        <TransitionGroup name="list">
          <div 
            v-for="note in notifications" 
            :key="note.id" 
            class="notify-card"
            :class="[note.type, { 'is-read': note.is_read }]"
            @click="markAsRead(note.id)"
          >
            <div class="ambient-glow"></div>
            
            <div class="icon-box">
              <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIcon(note.type)" />
              </svg>
            </div>

            <div class="card-content">
              <div class="top-row">
                <span class="card-title">{{ note.title }}</span>
                <span class="card-date">{{ formatDate(note.created_at) }}</span>
              </div>
              <p class="card-message">{{ note.message }}</p>
            </div>

            <button class="close-btn" @click.stop="deleteNotification(note.id)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M6 18L18 6M6 6l12 12" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
        </TransitionGroup>
      </div>

      <div v-else class="empty-placeholder">
        <div class="empty-icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="empty-svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M9.29664 4.72727V5.25342C6.60683 6.35644 4.7276 9.97935 4.79579 13.1192L4.79577 14.8631C3.4188 16.6333 3.49982 19.2727 6.93518 19.2727H9.29664C9.29664 19.996 9.57852 20.6897 10.0803 21.2012C10.582 21.7127 11.2625 22 11.9721 22C12.6817 22 13.3622 21.7127 13.8639 21.2012C14.3656 20.6897 14.6475 19.996 14.6475 19.2727H17.0155C20.4443 19.2727 20.494 16.6278 19.1172 14.8576L19.1555 13.1216C19.2248 9.97811 17.3419 6.35194 14.6475 5.25049V4.72727C14.6475 4.00395 14.3656 3.31026 13.8639 2.7988C13.3622 2.28734 12.6817 2 11.9721 2C11.2625 2 10.582 2.28734 10.0803 2.7988C9.57852 3.31026 9.29664 4.00395 9.29664 4.72727ZM12.8639 4.72727C12.8639 4.72727 12.8633 4.76414 12.8622 4.78246C12.5718 4.74603 12.2759 4.72727 11.9757 4.72727C11.673 4.72727 11.3747 4.74634 11.082 4.78335C11.0808 4.76474 11.0803 4.74603 11.0803 4.72727C11.0803 4.48617 11.1742 4.25494 11.3415 4.08445C11.5087 3.91396 11.7356 3.81818 11.9721 3.81818C12.2086 3.81818 12.4354 3.91396 12.6027 4.08445C12.7699 4.25494 12.8639 4.48617 12.8639 4.72727ZM11.0803 19.2727C11.0803 19.5138 11.1742 19.7451 11.3415 19.9156C11.5087 20.086 11.7356 20.1818 11.9721 20.1818C12.2086 20.1818 12.4354 20.086 12.6027 19.9156C12.7699 19.7451 12.8639 19.5138 12.8639 19.2727H11.0803ZM17.0155 17.4545C17.7774 17.4545 18.1884 16.5435 17.6926 15.9538C17.4516 15.6673 17.3233 15.3028 17.3316 14.9286L17.3723 13.0808C17.4404 9.99416 15.0044 6.54545 11.9757 6.54545C8.94765 6.54545 6.51196 9.99301 6.57898 13.0789L6.61916 14.9289C6.62729 15.303 6.49893 15.6674 6.25806 15.9538C5.76221 16.5435 6.17325 17.4545 6.93518 17.4545H17.0155ZM16.9799 3.20202C17.2945 2.74813 17.9176 2.63524 18.3715 2.94988C19.5192 3.74546 20.8956 5.65348 21.6471 7.9126C21.8214 8.43664 21.5379 9.00279 21.0139 9.17712C20.4898 9.35145 19.9237 9.06795 19.7493 8.5439C19.0892 6.55949 17.9221 5.07189 17.2321 4.59358C16.7782 4.27894 16.6653 3.65592 16.9799 3.20202ZM5.4303 2.94988C5.8842 2.63524 6.50722 2.74813 6.82185 3.20202C7.13649 3.65592 7.0236 4.27894 6.56971 4.59358C5.87969 5.07189 4.71256 6.55949 4.05242 8.5439C3.87809 9.06795 3.31194 9.35145 2.78789 9.17712C2.26384 9.00279 1.98034 8.43664 2.15467 7.9126C2.90619 5.65348 4.2826 3.74546 5.4303 2.94988Z" fill="currentColor" />
          </svg>
        </div>
        <h3>Все чисто</h3>
        <p>Новых уведомлений нет</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.notifications-page {
  width: 100%; max-width: 850px; padding-bottom: 80px;
}
.list-container { position: relative; }

.header-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 25px; }
.page-title { color: #fff; font-size: 32px; font-weight: 700; margin: 0; }
.text-btn { background: none; border: none; color: #666; font-size: 13px; cursor: pointer; padding: 5px 10px; transition: 0.2s; }
.text-btn:hover { color: #fff; }
.text-btn.delete:hover { color: #ef4444; }

/* --- NOTIFY CARD --- */
.notify-card {
  position: relative;
  display: flex; align-items: center; gap: 16px;
  background: rgba(255, 255, 255, 0.02); 
  border: 1px solid rgba(255, 255, 255, 0.05);
  padding: 16px 20px;
  padding-right: 50px;
  margin-bottom: 10px;
  border-radius: 16px;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.2s ease;
  width: 100%; box-sizing: border-box;
}

.notify-card:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.1);
  transform: translateY(-1px);
}

.ambient-glow {
  position: absolute; left: 0; top: 0; bottom: 0; width: 120px;
  background: radial-gradient(circle at left center, var(--glow-color), transparent 70%);
  opacity: 0.15; pointer-events: none; transition: opacity 0.3s;
}
.notify-card:hover .ambient-glow { opacity: 0.25; }
.is-read .ambient-glow { opacity: 0; }

.icon-box {
  width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,0.05);
  color: #666;
  border: 1px solid transparent;
  transition: 0.3s;
  z-index: 2;
}
.icon-box svg { width: 20px; height: 20px; }

.notify-card.success .icon-box { color: #22c55e; background: rgba(34, 197, 94, 0.1); }
.notify-card.info     .icon-box { color: #3b82f6; background: rgba(59, 130, 246, 0.1); }
.notify-card.warning .icon-box { color: #eab308; background: rgba(234, 179, 8, 0.1); }
.notify-card.error   .icon-box { color: #ef4444; background: rgba(239, 68, 68, 0.1); }

.card-content { flex-grow: 1; z-index: 2; display: flex; flex-direction: column; justify-content: center; overflow: hidden; }
.top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.card-title { color: #fff; font-size: 14px; font-weight: 500; }
.card-date { color: #555; font-size: 12px; margin-left: 10px; white-space: nowrap; }
.card-message { color: #888; font-size: 13px; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 95%; }

.is-read .card-title { color: #777; }
.is-read .card-message { color: #555; }
.is-read .icon-box { filter: grayscale(1); opacity: 0.5; }

.close-btn {
  position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
  z-index: 10; width: 30px; height: 30px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  border: none; cursor: pointer; transition: all 0.2s ease;
  background: transparent; color: rgba(255, 255, 255, 0.2); 
}
.close-btn svg { width: 18px; height: 18px; }
.close-btn:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; transform: translateY(-50%) rotate(90deg); }

.notify-card.success { --glow-color: #22c55e; }
.notify-card.info    { --glow-color: #3b82f6; }
.notify-card.warning { --glow-color: #eab308; }
.notify-card.error   { --glow-color: #ef4444; }

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

.list-move,
.list-enter-active,
.list-leave-active { transition: all 0.4s ease; }
.list-leave-to { opacity: 0; transform: translateX(50px); }
.list-enter-from { opacity: 0; transform: translateX(-50px); }
.list-leave-active { position: absolute; width: 100%; z-index: -1; }
</style>