<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApiFetch } from '~/composables/useApi'

definePageMeta({ layout: 'dashboard' })

const router = useRouter()
const services = ref<any[]>([])
const isLoading = ref(true)
const errorMessage = ref('')

const PTERODACTYL_URL = 'https://panel.sakuranet.space' 

const formatDate = (dateStr: string) => {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })
}

const fetchServices = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const { data, error } = await useApiFetch<any[]>('/api/services', { server: false })
    if (error.value) throw error.value
    if (data.value) services.value = data.value
  } catch (e: any) {
    console.error('Ошибка загрузки:', e)
    errorMessage.value = 'Не удалось загрузить список услуг. Попробуйте позже.'
  } finally {
    isLoading.value = false
  }
}

const openSettings = (id: number) => router.push(`/dashboard/services/${id}`)
const openPanel = (srv: any) => window.open(`${PTERODACTYL_URL}/server/${srv.identifier}`, '_blank')

onMounted(fetchServices)
</script>

<template>
  <div class="page-wrapper">
    <div class="content-wrapper">
      
      <div class="page-header">
        <h1>Мои услуги</h1>
        <p>Управление вашими активными серверами</p>
      </div>

      <div v-if="isLoading" class="grid-layout">
        <div class="glass-card skeleton" v-for="i in 3" :key="i"></div>
      </div>

      <div v-else-if="errorMessage" class="error-state">
        <div class="text-red-400 mb-2">⚠️ {{ errorMessage }}</div>
        <button @click="fetchServices" class="cosmic-btn compact">Повторить</button>
      </div>

      <div v-else-if="services.length === 0" class="empty-state">
        <div class="empty-icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="empty-svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.4856 1.12584C12.1836 0.958052 11.8164 0.958052 11.5144 1.12584L2.51436 6.12584L2.5073 6.13784L2.49287 6.13802C2.18749 6.3177 2 6.64568 2 7V16.9999C2 17.3631 2.19689 17.6977 2.51436 17.874L11.5022 22.8673C11.8059 23.0416 12.1791 23.0445 12.4856 22.8742L21.4856 17.8742C21.8031 17.6978 22 17.3632 22 17V7C22 6.64568 21.8125 6.31781 21.5071 6.13813C21.4996 6.13372 21.4921 6.12942 21.4845 6.12522L12.4856 1.12584ZM5.05923 6.99995L12.0001 10.856L14.4855 9.47519L7.74296 5.50898L5.05923 6.99995ZM16.5142 8.34816L18.9409 7L12 3.14396L9.77162 4.38195L16.5142 8.34816ZM4 16.4115V8.69951L11 12.5884V20.3004L4 16.4115ZM13 20.3005V12.5884L20 8.69951V16.4116L13 20.3005Z" fill="currentColor" />
          </svg>
        </div>
        <h3>У вас пока нет услуг</h3>
        <p class="empty-subtext">Закажите свой первый сервер, чтобы начать работу.</p>
        <NuxtLink to="/dashboard/order" class="cosmic-btn compact mt-4">
          Заказать сервер
          <div class="btn-glow"></div>
        </NuxtLink>
      </div>

      <div v-else class="grid-layout">
        <div v-for="srv in services" :key="srv.id" class="glass-card">
          <div class="card-top">
            <div class="icon-wrapper">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-svg">
                <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                <line x1="6" y1="6" x2="6.01" y2="6"></line>
                <line x1="6" y1="18" x2="6.01" y2="18"></line>
              </svg>
            </div>
            
            <div class="srv-info">
              <div class="srv-name">{{ srv.name }}</div>
              <div class="srv-id">ID: {{ srv.identifier }}</div>
            </div>

            <div class="status-indicator">
              <div class="status-dot" :class="{ 'bg-red': srv.status !== 'active' }"></div>
              <span class="status-text">{{ srv.status === 'active' ? 'ONLINE' : 'OFFLINE' }}</span>
            </div>
          </div>

          <div class="details-list">
            <div class="detail-item">
              <span class="label">IP Адрес</span>
              <span class="value mono">{{ srv.ip_address || 'Загрузка...' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Истекает</span>
              <span class="value">{{ formatDate(srv.expires_at) }}</span>
            </div>
          </div>

          <div class="action-grid">
            <button @click="openPanel(srv)" class="cosmic-btn">
              <span>В панель</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-svg sm">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                <polyline points="15 3 21 3 21 9"></polyline>
                <line x1="10" y1="14" x2="21" y2="3"></line>
              </svg>
              <div class="btn-glow"></div>
            </button>
            
            <button @click="openSettings(srv.id)" class="cosmic-btn secondary">
              <span>Настройки</span>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.page-wrapper { position: relative; width: 100%; max-width: 1200px; margin: 0; padding-bottom: 80px; }
.content-wrapper { position: relative; z-index: 10; }
.page-header { margin-bottom: 30px; }
.page-header h1 { font-size: 32px; font-weight: 700; color: white; margin: 0; }
.page-header p { color: #888; font-size: 13px; margin-top: 4px; }
.grid-layout { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
.glass-card { position: relative; background: rgba(12, 12, 12, 0.65); border: 1px solid rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); padding: 24px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); transition: transform 0.3s ease, border-color 0.3s ease; display: flex; flex-direction: column; gap: 20px; }
.glass-card:hover { transform: translateY(-4px); border-color: rgba(255, 255, 255, 0.15); }
.card-top { display: flex; align-items: center; gap: 15px; }
.icon-wrapper { width: 42px; height: 42px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ccc; flex-shrink: 0; }
.srv-info { flex-grow: 1; overflow: hidden; }
.srv-name { font-size: 15px; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.srv-id { font-size: 11px; color: #666; font-family: monospace; margin-top: 2px; }
.status-indicator { display: flex; align-items: center; gap: 8px; background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); }
.status-dot { width: 6px; height: 6px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 8px #22c55e; animation: pulse 2s infinite; }
.status-dot.bg-red { background: #ef4444; box-shadow: 0 0 8px #ef4444; }
.status-text { font-size: 9px; color: #ccc; font-weight: 600; letter-spacing: 0.5px; }
@keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
.details-list { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04); border-radius: 14px; padding: 12px 16px; display: flex; flex-direction: column; gap: 10px; }
.detail-item { display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
.label { color: #666; font-size: 12px; }
.value { color: #eee; font-weight: 500; }
.mono { font-family: monospace; }
.action-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: auto; }
.cosmic-btn { position: relative; display: flex; align-items: center; justify-content: center; gap: 8px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: #fff; font-size: 13px; font-weight: 600; padding: 10px; cursor: pointer; overflow: hidden; transition: all 0.3s ease; }
.cosmic-btn:hover { background: rgba(168, 85, 247, 0.15); border-color: rgba(168, 85, 247, 0.5); box-shadow: 0 0 20px rgba(168, 85, 247, 0.2); }
.cosmic-btn.secondary { background: transparent; border-color: rgba(255,255,255,0.05); color: #888; }
.cosmic-btn.secondary:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.2); color: white; box-shadow: none; }
.btn-glow { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(168, 85, 247, 0.4), transparent); transform: translateX(-100%); transition: transform 0.5s ease; opacity: 0.5; pointer-events: none; }
.cosmic-btn:hover .btn-glow { transform: translateX(100%); }

/* === НОВЫЕ СТИЛИ ПУСТОГО СОСТОЯНИЯ === */
.empty-state {
  min-height: 400px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.02);
  border: 1px dashed rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  text-align: center;
  padding: 40px;
}
.empty-icon-wrapper {
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 84px;
  height: 84px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  color: #444;
}
.empty-svg {
  width: 38px;
  height: 38px;
  opacity: 0.8;
}
.empty-state h3 { color: #fff; font-size: 18px; margin: 0 0 8px 0; font-weight: 600; }
.empty-subtext { font-size: 14px; color: #666; max-width: 300px; margin-bottom: 10px; }
.mt-4 { margin-top: 16px; }

.cosmic-btn.compact { display: inline-flex; width: auto; padding: 12px 32px; text-decoration: none; }
.error-state { text-align: center; padding: 40px; }
.skeleton { height: 220px; background: linear-gradient(90deg, rgba(255,255,255,0.02) 25%, rgba(255,255,255,0.05) 50%, rgba(255,255,255,0.02) 75%); background-size: 200% 100%; animation: loading 1.5s infinite; }
@keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
@media (max-width: 600px) { .grid-layout { grid-template-columns: 1fr; } }
</style>