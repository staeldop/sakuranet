<script setup lang="ts">
import { computed } from 'vue'
import { useApiFetch, $api } from '~/composables/useApi'

definePageMeta({
  layout: 'dashboard'
})

interface Notification {
  id: number
  title: string
  message: string
  type: 'info' | 'success' | 'warning' | 'error'
  created_at: string // Laravel возвращает created_at
  is_read: boolean   // Laravel возвращает snake_case
}

// --- ЗАГРУЗКА ДАННЫХ С БЭКЕНДА ---
const { data: notificationsData, refresh } = await useApiFetch<Notification[]>('/api/notifications')
const notifications = computed(() => notificationsData.value || [])

// --- МЕТОДЫ (ТЕПЕРЬ С ЗАПРОСАМИ К API) ---

const formatDate = (isoDate: string) => {
  if (!isoDate) return ''
  const date = new Date(isoDate)
  return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' })
}

const markAsRead = async (id: number) => {
  // Оптимистичное обновление интерфейса
  const note = notifications.value.find(n => n.id === id)
  if (note && !note.is_read) {
    note.is_read = true
    try {
      await $api(`/api/notifications/${id}/read`, { method: 'POST' })
    } catch (e) {
      console.error('Ошибка обновления статуса', e)
    }
  }
}

const markAllRead = async () => {
  // Сразу обновляем UI
  notifications.value.forEach(n => n.is_read = true)
  try {
    await $api('/api/notifications/read-all', { method: 'POST' })
  } catch (e) {
    console.error(e)
  }
}

const deleteNotification = async (id: number) => {
  try {
    await $api(`/api/notifications/${id}`, { method: 'DELETE' })
    await refresh() // Обновляем список после удаления
  } catch (e) {
    console.error(e)
  }
}

const deleteAll = async () => {
  if (!confirm('Удалить все уведомления?')) return
  try {
    await $api('/api/notifications', { method: 'DELETE' })
    await refresh()
  } catch (e) {
    console.error(e)
  }
}

// SVG иконки (Без изменений)
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
                <!-- Используем created_at вместо date -->
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
        <div class="empty-circle">🔔</div>
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
  padding-right: 50px; /* Чуть уменьшил отступ, так как кнопка стала визуально легче */
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

/* 1. AMBIENT GLOW */
.ambient-glow {
  position: absolute; left: 0; top: 0; bottom: 0; width: 120px;
  background: radial-gradient(circle at left center, var(--glow-color), transparent 70%);
  opacity: 0.15; pointer-events: none; transition: opacity 0.3s;
}
.notify-card:hover .ambient-glow { opacity: 0.25; }
.is-read .ambient-glow { opacity: 0; }

/* 2. ICON BOX */
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
.notify-card.info    .icon-box { color: #3b82f6; background: rgba(59, 130, 246, 0.1); }
.notify-card.warning .icon-box { color: #eab308; background: rgba(234, 179, 8, 0.1); }
.notify-card.error   .icon-box { color: #ef4444; background: rgba(239, 68, 68, 0.1); }

/* 3. CONTENT */
.card-content { flex-grow: 1; z-index: 2; display: flex; flex-direction: column; justify-content: center; overflow: hidden; }
.top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.card-title { color: #fff; font-size: 14px; font-weight: 500; }
.card-date { color: #555; font-size: 12px; margin-left: 10px; white-space: nowrap; }
.card-message { color: #888; font-size: 13px; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 95%; }

.is-read .card-title { color: #777; }
.is-read .card-message { color: #555; }
.is-read .icon-box { filter: grayscale(1); opacity: 0.5; }

/* --- 🔥 НОВАЯ КНОПКА (Invisible Style) --- */
.close-btn {
  position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
  z-index: 10;
  width: 30px; height: 30px; 
  border-radius: 50%; /* Делаем круглую зону клика, это приятнее глазу */
  
  display: flex; align-items: center; justify-content: center;
  border: none; cursor: pointer;
  transition: all 0.2s ease;

  /* УБИРАЕМ ФОН ПО УМОЛЧАНИЮ */
  background: transparent; 
  /* Делаем крестик полупрозрачным, чтобы он сливался с фоном */
  color: rgba(255, 255, 255, 0.2); 
}

.close-btn svg { width: 18px; height: 18px; }

/* ПРИ НАВЕДЕНИИ НА САМУ КНОПКУ */
.close-btn:hover { 
  /* Легкая красная подсветка */
  background: rgba(239, 68, 68, 0.1); 
  /* Яркий крестик */
  color: #ef4444; 
  transform: translateY(-50%) rotate(90deg); /* Небольшая анимация поворота для фана */
}

/* CSS ПЕРЕМЕННЫЕ */
.notify-card.success { --glow-color: #22c55e; }
.notify-card.info    { --glow-color: #3b82f6; }
.notify-card.warning { --glow-color: #eab308; }
.notify-card.error   { --glow-color: #ef4444; }

/* ЗАГЛУШКА */
.empty-placeholder { text-align: center; padding: 60px 0; color: #555; }
.empty-circle { font-size: 32px; margin-bottom: 10px; opacity: 0.4; filter: grayscale(1); }

/* АНИМАЦИЯ СПИСКА */
.list-move,
.list-enter-active,
.list-leave-active { transition: all 0.4s ease; }
.list-leave-to { opacity: 0; transform: translateX(50px); }
.list-enter-from { opacity: 0; transform: translateX(-50px); }
.list-leave-active { position: absolute; width: 100%; z-index: -1; }
</style>
