<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiFetch } from '~/composables/useApi'

// Иконки
import IconServer from '~/assets/icons/server.svg?component'
import IconArrow from '~/assets/icons/arrow-right.svg?component'
import IconTrash from '~/assets/icons/trash.svg?component'
import IconCopy from '~/assets/icons/box.svg?component' // Используем box как иконку копирования, если нет специальной

definePageMeta({
  layout: 'dashboard'
})

const route = useRoute()
const router = useRouter()
const service = ref<any>(null)
const isLoading = ref(true)
const showPassword = ref(false)

// Загрузка данных
const fetchService = async () => {
  // Если ID нет, уходим назад
  if (!route.params.id) return router.push('/dashboard/services')

  try {
    // server: false — предотвращает ошибку 500 при SSR
    const { data, error } = await useApiFetch<any>(`/api/services/${route.params.id}`, {
      server: false 
    })
    
    if (error.value) throw error.value
    service.value = data.value
  } catch (e) {
    console.error(e)
    router.push('/dashboard/services')
  } finally {
    isLoading.value = false
  }
}

const goToPanel = () => {
  window.open('https://panel.sakuranet.space', '_blank')
}

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text)
  alert('Скопировано!')
}

const deleteService = async () => {
  if(!confirm('Удалить сервер? Это действие нельзя отменить.')) return
  try {
    await useApiFetch(`/api/services/${service.value.id}`, { method: 'DELETE' })
    router.push('/dashboard/services')
  } catch (e) {
    alert('Ошибка удаления')
  }
}

// Форматирование даты
const formatDate = (dateStr: string) => {
  if (!dateStr) return '...'
  return new Date(dateStr).toLocaleDateString('ru-RU')
}

onMounted(fetchService)
</script>

<template>
  <div class="container-custom">
    
    <div v-if="isLoading" class="loading-screen">
      <div class="spinner"></div>
    </div>

    <!-- КОНТЕНТ -->
    <div v-else-if="service" class="content-wrapper">
      
      <!-- ХЕДЕР -->
      <div class="top-bar">
        <button @click="router.push('/dashboard/services')" class="back-link">
          <IconArrow class="icon-back" /> К списку услуг
        </button>
        
        <div class="header-flex">
          <div>
            <div class="flex items-center gap-3 mb-1">
              <h1 class="page-title">{{ service.name }}</h1>
              <span class="status-tag" :class="service.status">
                {{ service.status === 'active' ? 'Активен' : 'Неактивен' }}
              </span>
            </div>
            <p class="page-subtitle">ID: {{ service.identifier }} • {{ service.core }}</p>
          </div>
          
          <button class="panel-btn-hero" @click="goToPanel">
            Перейти в панель
            <IconArrow class="icon-next" />
          </button>
        </div>
      </div>

      <div class="grid-layout">
        
        <!-- ЛЕВАЯ КОЛОНКА -->
        <div class="left-col">
          
          <!-- ДАННЫЕ ПОДКЛЮЧЕНИЯ -->
          <div class="glass-card mb-6">
            <h3 class="card-title">Данные для подключения</h3>
            
            <div class="field-group">
              <label>IP Адрес</label>
              <div class="input-copy">
                <input readonly :value="service.ip_address || 'В обработке...'" />
                <button @click="copyToClipboard(service.ip_address || '')" title="Копировать">
                  <IconCopy class="w-4 h-4"/>
                </button>
              </div>
            </div>

            <div class="row-fields">
              <div class="field-group">
                <label>Логин</label>
                <div class="input-copy">
                  <input readonly :value="`user_${service.id}`" />
                  <button @click="copyToClipboard(`user_${service.id}`)">
                    <IconCopy class="w-4 h-4"/>
                  </button>
                </div>
              </div>
              <div class="field-group">
                <label>Пароль</label>
                <div class="input-copy">
                  <input readonly :type="showPassword ? 'text' : 'password'" value="S3cretP@ss" />
                  <button @click="showPassword = !showPassword" class="text-xs font-bold text-neutral-400 hover:text-white transition-colors">
                    {{ showPassword ? 'HIDE' : 'SHOW' }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- ТАРИФ -->
          <div class="glass-card">
            <h3 class="card-title">Информация о тарифе</h3>
            <div class="specs-row">
              <div class="spec-box">
                <span class="label">Тариф</span>
                <span class="val">{{ service.product?.name || 'Custom' }}</span>
              </div>
              <div class="spec-box">
                <span class="label">Цена</span>
                <span class="val">{{ Number(service.price_monthly).toFixed(0) }} ₽/мес</span>
              </div>
              <div class="spec-box">
                <span class="label">Ядро</span>
                <span class="val">{{ service.core }}</span>
              </div>
            </div>
          </div>

        </div>

        <!-- ПРАВАЯ КОЛОНКА -->
        <div class="right-col">
          
          <!-- ПРОДЛЕНИЕ -->
          <div class="glass-card action-card">
            <div class="glow-bg"></div>
            <h3 class="card-title">Продление</h3>
            <p class="text-sm text-neutral-400 mb-4">
              Активен до: <br>
              <span class="text-white font-bold text-lg">{{ formatDate(service.expires_at) }}</span>
            </p>
            <button class="extend-btn-large">Продлить аренду</button>
          </div>

          <!-- УДАЛЕНИЕ -->
          <div class="danger-zone mt-6">
            <h3 class="text-red-500 font-bold mb-2 text-sm uppercase tracking-wider">Опасная зона</h3>
            <button class="delete-btn" @click="deleteService">
              <IconTrash class="w-4 h-4" />
              Отказаться от услуги
            </button>
          </div>

        </div>

      </div>

    </div>
  </div>
</template>

<style scoped>
.container-custom { width: 100%; max-width: 1350px; margin: 0; padding-bottom: 100px; color: #f5f5f5; }

/* HEADER */
.top-bar { margin-bottom: 40px; }
.back-link { 
  display: flex; align-items: center; gap: 8px; background: none; border: none; 
  color: #737373; font-size: 14px; cursor: pointer; margin-bottom: 20px; transition: 0.2s; 
}
.back-link:hover { color: white; }
.icon-back { width: 16px; height: 16px; transform: rotate(180deg); }

.header-flex { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px; }
.page-title { font-size: 32px; font-weight: 800; color: white; margin: 0; line-height: 1; }
.page-subtitle { color: #888; font-size: 14px; margin-top: 6px; font-family: monospace; }

.status-tag { 
  font-size: 12px; padding: 4px 10px; border-radius: 20px; font-weight: 700; text-transform: uppercase; 
}
.status-tag.active { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }

.panel-btn-hero {
  background: white; color: black; border: none; padding: 14px 24px; 
  border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; 
  display: flex; align-items: center; gap: 10px; transition: 0.2s;
}
.panel-btn-hero:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(255,255,255,0.15); }
.icon-next { width: 18px; height: 18px; }

/* GRID */
.grid-layout { display: grid; grid-template-columns: 1fr; gap: 30px; }
@media(min-width: 1024px) { .grid-layout { grid-template-columns: 2fr 1fr; } }

/* CARDS */
.glass-card {
  background: rgba(23, 23, 23, 0.5); border: 1px solid #262626; 
  border-radius: 16px; padding: 30px; position: relative; overflow: hidden;
}
.card-title { font-size: 18px; font-weight: 700; color: white; margin-bottom: 20px; }

/* INPUTS */
.field-group { margin-bottom: 16px; }
.field-group label { display: block; font-size: 12px; color: #737373; margin-bottom: 6px; font-weight: 600; }
.input-copy { 
  display: flex; align-items: center; background: rgba(0,0,0,0.3); 
  border: 1px solid #333; border-radius: 8px; padding: 0 12px; 
}
.input-copy input { 
  flex-grow: 1; background: transparent; border: none; color: white; 
  padding: 12px 0; font-family: monospace; outline: none; font-size: 14px;
}
.input-copy button { 
  background: none; border: none; color: #737373; cursor: pointer; padding: 8px; transition: 0.2s; 
}
.input-copy button:hover { color: white; }

.row-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

/* SPECS */
.specs-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
.spec-box { background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px; }
.spec-box .label { display: block; font-size: 11px; color: #737373; margin-bottom: 4px; }
.spec-box .val { display: block; font-size: 14px; font-weight: 600; color: white; }

/* RIGHT COL */
.action-card { text-align: center; }
.glow-bg { 
  position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; 
  background: radial-gradient(circle, rgba(168, 85, 247, 0.2), transparent); 
  filter: blur(50px); pointer-events: none; 
}
.extend-btn-large {
  width: 100%; background: transparent; border: 1px solid #444; color: white; 
  padding: 14px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s;
}
.extend-btn-large:hover { border-color: white; background: rgba(255,255,255,0.05); }

.delete-btn {
  width: 100%; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);
  color: #ef4444; padding: 12px; border-radius: 10px; font-size: 13px; font-weight: 600;
  display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: 0.2s;
}
.delete-btn:hover { background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); }

.loading-screen { height: 60vh; display: flex; align-items: center; justify-content: center; }
.spinner { width: 40px; height: 40px; border: 3px solid #333; border-top-color: white; border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>