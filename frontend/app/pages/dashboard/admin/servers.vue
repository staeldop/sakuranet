<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { $api } from '~/composables/useApi' // Импортируем $api вместо useApiFetch

// Иконки
import IconServer from '~/assets/icons/server.svg?component'
import IconCpu from '~/assets/icons/cpu.svg?component'
import IconRam from '~/assets/icons/ram.svg?component'
import IconDisk from '~/assets/icons/disk.svg?component'

definePageMeta({ layout: 'admin' })

interface ServerAttributes {
  id: number
  identifier: string
  name: string
  node: string
  status: string | null
  suspended: boolean
  limits: {
    memory: number
    swap: number
    disk: number
    io: number
    cpu: number
  }
}

interface PterodactylServer {
  object: string
  attributes: ServerAttributes
}

const servers = ref<PterodactylServer[]>([])
const isLoading = ref(true)
const searchQuery = ref('')
const debugError = ref('')

// --- ЗАГРУЗКА ---
const fetchServers = async () => {
  isLoading.value = true
  debugError.value = ''
  
  try {
    // ИСПРАВЛЕНИЕ: Используем $api вместо useApiFetch
    const response: any = await $api('/api/admin/servers', {
      method: 'GET'
    })

    console.log('🔥 API Response:', response)

    // Если Pterodactyl вернул ошибку, которую мы поймали в контроллере
    if (response.error) {
      throw new Error(response.message || JSON.stringify(response.details))
    }

    // Разбираем ответ (иногда он в data, иногда сразу массив)
    if (response.data && Array.isArray(response.data)) {
      servers.value = response.data
    } else if (Array.isArray(response)) {
      servers.value = response
    } else {
      console.warn('Странный формат ответа:', response)
    }

  } catch (e: any) {
    console.error('Ошибка загрузки:', e)
    // Выводим ошибку на экран, чтобы ты увидел причину 500-й
    debugError.value = e.data?.message || e.message || 'Неизвестная ошибка'
  } finally {
    isLoading.value = false
  }
}

// --- ПОИСК ---
const filteredServers = computed(() => {
  if (!searchQuery.value) return servers.value
  const q = searchQuery.value.toLowerCase()
  return servers.value.filter(s => 
    s.attributes.name.toLowerCase().includes(q) || 
    s.attributes.identifier.toLowerCase().includes(q)
  )
})

// Хелперы
const getStatusColor = (attrs: ServerAttributes) => {
  if (attrs.suspended) return 'text-red-500' 
  if (attrs.status === null) return 'text-green-400' 
  return 'text-yellow-400'
}

const getStatusText = (attrs: ServerAttributes) => {
  if (attrs.suspended) return 'SUSPENDED'
  if (attrs.status === null) return 'ACTIVE'
  return attrs.status?.toUpperCase() || 'UNKNOWN'
}

onMounted(fetchServers)
</script>

<template>
  <div class="admin-shell">
    <div class="glow glow-purple" />
    
    <div class="content-wrapper">
      
      <div class="page-header">
        <div class="title-group">
          <div class="auth-badge mb-2">
            <span class="badge-dot" />
            <span class="badge-text">PTERODACTYL LINKED</span>
          </div>
          <h1 class="title">Игровые Серверы</h1>
          <div class="subtitle">Мониторинг активных контейнеров</div>
        </div>
        
        <div class="search-wrapper">
          <div class="search-icon">🔍</div>
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="UUID, Название, Нода..." 
            class="search-input"
          >
        </div>
      </div>

      <div v-if="debugError" class="error-box">
        <h3>Ошибка API</h3>
        <code>{{ debugError }}</code>
        <button @click="fetchServers" class="retry-btn">Повторить</button>
      </div>

      <div v-else-if="isLoading" class="loading-state">
        <div class="loader"></div> Загрузка данных с панели...
      </div>

      <div v-else-if="filteredServers.length === 0" class="empty-state">
        Серверы не найдены
      </div>

      <div v-else class="servers-grid">
        <div 
          v-for="item in filteredServers" 
          :key="item.attributes.id"
          class="server-card glass-card"
        >
          <div class="card-glow-top" />
          
          <div class="card-head">
            <div class="server-icon">
              <IconServer class="w-5 h-5" />
            </div>
            <div class="status-badge" :class="getStatusColor(item.attributes)">
              <span class="dot" :class="getStatusColor(item.attributes).replace('text-', 'bg-')"></span>
              {{ getStatusText(item.attributes) }}
            </div>
          </div>

          <h3 class="server-name" :title="item.attributes.name">{{ item.attributes.name }}</h3>
          <div class="server-uuid">{{ item.attributes.identifier }} • Node: {{ item.attributes.node }}</div>

          <div class="specs-row">
            <div class="spec-item">
              <IconRam class="spec-icon" />
              <span>{{ item.attributes.limits.memory }} MB</span>
            </div>
            <div class="spec-item">
              <IconCpu class="spec-icon" />
              <span>{{ item.attributes.limits.cpu }}%</span>
            </div>
            <div class="spec-item">
              <IconDisk class="spec-icon" />
              <span>{{ item.attributes.limits.disk }} MB</span>
            </div>
          </div>

          <div class="card-actions">
            <a 
              :href="`https://panel.tvoy-host.com/server/${item.attributes.identifier}`" 
              target="_blank"
              class="action-btn"
            >
              Управлять в Панели →
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.admin-shell { position: relative; min-height: 100%; width: 100%; overflow: hidden; padding-bottom: 40px; }
.content-wrapper { position: relative; z-index: 10; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.glow { position: absolute; width: 600px; height: 600px; border-radius: 50%; filter: blur(100px); opacity: 0.15; pointer-events: none; }
.glow-purple { top: -10%; right: -10%; background: radial-gradient(circle, #a855f7, transparent 70%); }

.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin: 30px 0; gap: 20px; flex-wrap: wrap; }
.title { font-size: 28px; font-weight: 700; color: #fff; margin: 0; }
.subtitle { color: #888; font-size: 14px; margin-top: 4px; }
.auth-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 100px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); width: fit-content; }
.badge-dot { width: 6px; height: 6px; background: #3b82f6; border-radius: 50%; box-shadow: 0 0 8px #3b82f6; }
.badge-text { font-size: 10px; font-weight: 700; letter-spacing: 1px; color: #aaa; }

.search-wrapper { position: relative; width: 300px; }
.search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #666; font-size: 14px; }
.search-input { width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 12px 12px 12px 40px; border-radius: 12px; color: #fff; outline: none; transition: 0.2s; font-size: 14px; box-sizing: border-box; }
.search-input:focus { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.05); }

.error-box { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); padding: 20px; border-radius: 12px; color: #f87171; margin-bottom: 20px; }
.error-box code { display: block; margin: 10px 0; font-family: monospace; background: rgba(0,0,0,0.3); padding: 10px; border-radius: 6px; }
.retry-btn { background: #f87171; color: #000; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; }

.servers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.glass-card { position: relative; background: rgba(20, 20, 20, 0.6); border: 1px solid rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border-radius: 16px; padding: 20px; transition: 0.3s; display: flex; flex-direction: column; }
.glass-card:hover { transform: translateY(-3px); background: rgba(30, 30, 30, 0.7); border-color: rgba(255,255,255,0.15); }
.card-glow-top { position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); }

.card-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.server-icon { width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; }

.status-badge { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 5px; background: rgba(0,0,0,0.3); padding: 4px 8px; border-radius: 6px; }
.dot { width: 5px; height: 5px; border-radius: 50%; }

.server-name { font-size: 16px; font-weight: 600; color: #fff; margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.server-uuid { font-size: 12px; color: #666; font-family: monospace; margin-bottom: 16px; }

.specs-row { display: flex; gap: 12px; margin-bottom: 20px; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 8px; }
.spec-item { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #aaa; }
.spec-icon { width: 14px; height: 14px; color: #666; }

.card-actions { margin-top: auto; }
.action-btn { display: block; width: 100%; text-align: center; padding: 10px; background: rgba(255,255,255,0.1); color: #fff; border-radius: 8px; font-size: 13px; font-weight: 500; text-decoration: none; transition: 0.2s; }
.action-btn:hover { background: #3b82f6; color: #fff; }

.loading-state, .empty-state { text-align: center; padding: 60px; color: #666; font-size: 14px; }
</style>