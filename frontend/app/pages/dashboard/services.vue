<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router' // Добавляем роутер
import { useApiFetch } from '~/composables/useApi'

import IconServer from '~/assets/icons/server.svg?component'

definePageMeta({
  layout: 'dashboard'
})

const router = useRouter() // Инициализируем роутер
const services = ref<any[]>([])
const isLoading = ref(true)

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })
}

const fetchServices = async () => {
  try {
    // server: false отключает SSR, чтобы избежать ошибки 500
    const { data } = await useApiFetch<any[]>('/api/services', { server: false })
    if (data.value) {
      services.value = data.value
      console.log('Services loaded:', services.value) // Лог для проверки
    }
  } catch (e) {
    console.error('Ошибка загрузки:', e)
  } finally {
    isLoading.value = false
  }
}

// 🔥 Функция для перехода на страницу услуги
const openService = (id: number) => {
  console.log('Opening service ID:', id) // Лог клика
  router.push(`/dashboard/services/${id}`)
}

onMounted(fetchServices)
</script>

<template>
  <div class="container-custom">
    
    <div class="header-section">
      <h1 class="page-title">Мои услуги</h1>
      <p class="page-subtitle">Управление вашими активными серверами.</p>
    </div>

    <!-- ЗАГРУЗКА -->
    <div v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <!-- ПУСТОЕ СОСТОЯНИЕ -->
    <div v-else-if="services.length === 0" class="empty-state">
      <div class="empty-icon">📦</div>
      <h3>У вас пока нет активных услуг</h3>
      <NuxtLink to="/dashboard/order" class="action-btn">Заказать сервер</NuxtLink>
    </div>

    <!-- СПИСОК СЕРВЕРОВ -->
    <div v-else class="services-grid">
      <!-- 
         🔥 ЗАМЕНИЛ NuxtLink НА div C @click 
         Это гарантирует, что клик будет обработан JS-функцией
      -->
      <div 
        v-for="srv in services" 
        :key="srv.id" 
        @click="openService(srv.id)"
        class="service-card cursor-pointer"
      >
        <div class="card-top">
          <div class="service-icon">
            <IconServer />
          </div>
          <div class="service-info">
            <div class="srv-name">{{ srv.name }}</div>
            <div class="srv-id">ID: {{ srv.identifier }}</div>
          </div>
          <div class="status-badge" :class="srv.status">
            <span class="status-dot"></span>
            {{ srv.status === 'active' ? 'Активен' : 'Остановлен' }}
          </div>
        </div>

        <div class="card-details">
          <div class="detail-row">
            <span>IP адрес</span>
            <span class="text-white font-mono">{{ srv.ip_address || 'Выдается...' }}</span>
          </div>
          <div class="detail-row">
            <span>Истекает</span>
            <span class="text-white">{{ formatDate(srv.expires_at) }}</span>
          </div>
        </div>

        <div class="hover-indicator">Перейти к управлению →</div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.container-custom { width: 100%; max-width: 1350px; margin: 0; padding-bottom: 100px; color: #f5f5f5; }

.header-section { margin-bottom: 40px; }
.page-title { font-size: 32px; font-weight: 800; color: white; margin: 0 0 6px 0; }
.page-subtitle { color: #888; font-size: 15px; }

/* СЕТКА */
.services-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; }

/* КАРТОЧКА */
.service-card {
  background: rgba(23, 23, 23, 0.5);
  border: 1px solid #262626;
  border-radius: 16px;
  padding: 24px;
  transition: all 0.3s ease;
  display: flex; flex-direction: column; gap: 24px;
  position: relative;
  overflow: hidden;
}
.service-card:hover { border-color: #404040; transform: translateY(-4px); background: rgba(23, 23, 23, 0.8); }

/* Стили для курсора */
.cursor-pointer { cursor: pointer; }

/* Исправлено свойство CSS */
.card-top { display: flex; align-items: center; gap: 16px; }

.service-icon { width: 48px; height: 48px; background: #1a1a1a; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #d4d4d4; }
.service-icon svg { width: 24px; height: 24px; }

.service-info { flex-grow: 1; }
.srv-name { font-size: 16px; font-weight: 700; color: white; }
.srv-id { font-size: 12px; color: #737373; margin-top: 2px; font-family: monospace; }

/* СТАТУС */
.status-badge { 
  display: flex; align-items: center; gap: 6px; 
  padding: 6px 12px; border-radius: 100px; 
  font-size: 12px; font-weight: 600;
}
.status-badge.active { background: rgba(34, 197, 94, 0.1); color: #4ade80; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

/* ДЕТАЛИ */
.card-details { display: flex; flex-direction: column; gap: 12px; border-top: 1px solid #262626; padding-top: 20px; }
.detail-row { display: flex; justify-content: space-between; font-size: 13px; color: #737373; }

/* HOVER ЭФФЕКТ */
.hover-indicator {
  margin-top: auto; text-align: center; font-size: 13px; font-weight: 600; color: #a3a3a3;
  opacity: 0; transform: translateY(10px); transition: all 0.3s ease;
}
.service-card:hover .hover-indicator { opacity: 1; transform: translateY(0); color: white; }

/* EMPTY STATE */
.empty-state { text-align: center; padding: 80px 0; border: 1px dashed #262626; border-radius: 20px; }
.empty-icon { font-size: 48px; margin-bottom: 16px; }
.empty-state h3 { color: #737373; font-size: 18px; font-weight: 500; margin-bottom: 24px; }
.action-btn { display: inline-block; background: white; color: black; padding: 12px 24px; border-radius: 8px; font-weight: 700; text-decoration: none; transition: 0.2s; }
.action-btn:hover { transform: scale(1.05); }

.loading-state { height: 200px; display: flex; align-items: center; justify-content: center; }
.spinner { width: 40px; height: 40px; border: 3px solid #333; border-top-color: white; border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>