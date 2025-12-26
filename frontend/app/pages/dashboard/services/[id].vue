<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { $api, useApiFetch } from '~/composables/useApi'
import { useAuthStore } from '~/stores/auth' 
import ModalConfirm from '~/components/ModalConfirm.vue'
import FileExplorer from '~/components/FileExplorer.vue'

// ИМПОРТ ИКОНОК
import IconArrow from '~/assets/icons/arrow-right.svg?component'
import IconTrash from '~/assets/icons/trash.svg?component'
import IconCpu from '~/assets/icons/cpu.svg?component'
import IconRam from '~/assets/icons/ram.svg?component'
import IconDisk from '~/assets/icons/disk.svg?component'
import IconExternal from '~/assets/icons/gamepad.svg?component'
import IconBox from '~/assets/icons/box.svg?component'
import IconSearch from '~/assets/icons/search.svg?component'
import IconCheck from '~/assets/icons/check.svg?component'

definePageMeta({ layout: 'dashboard' })

interface Service {
  id: number
  identifier: string
  name: string
  status: 'active' | 'stopped' | 'suspended' | 'installing'
  core: string
  price_monthly: string | number
  created_at: string
  expires_at: string
  auto_renew: boolean
  product?: { name: string }
  ip_address?: string
}

const route = useRoute()
const router = useRouter()
const auth = useAuthStore() 
const service = ref<Service | null>(null)
const isLoading = ref(true)
const showPassword = ref(false)
const isDeleteModalOpen = ref(false)
const isDeleting = ref(false)
const isRenewLoading = ref(false)
const copiedField = ref<string | null>(null)

// --- СОСТОЯНИЕ ДЛЯ СМЕНЫ ЯДРА ---
const isChangeCoreOpen = ref(false)
const isCoreChanging = ref(false)
const selectedNestId = ref<number | null>(null)
const selectedEggId = ref<number | null>(null)
const selectedEggName = ref<string>('')
const searchQuery = ref('')
const fileTree = ref<any>({})
const IGNORED_PATH_WORDS = ['game_eggs', 'twitch', 'voice_servers', 'other', 'misc', 'software']

const PANEL_URL = 'https://panel.sakuranet.space'

// --- ЗАГРУЗКА ДАННЫХ СЕРВИСА ---
const fetchService = async () => {
  if (!route.params.id) return router.push('/dashboard/services')
  isLoading.value = true
  try {
    const { data } = await useApiFetch<any>(`/api/services/${route.params.id}`)
    if (data.value) service.value = data.value
    // Если поле auto_renew не пришло, ставим false
    if (service.value && service.value.auto_renew === undefined) service.value.auto_renew = false 
  } catch (e) { 
    console.error(e) 
  } finally { 
    isLoading.value = false 
  }
}

// --- ЛОГИКА ДЕРЕВА ЯДЕР (С ПРИОРИТЕТАМИ) ---
const buildTreeCorrected = (nestsData: any[]) => {
  const root: any = {}
  const prioritySet = new Set<string>()

  // 1. Собираем приоритеты
  nestsData.forEach(nest => {
    if (!nest.attributes?.relationships?.eggs?.data) return;
    nest.attributes.relationships.eggs.data.forEach((egg: any) => {
      const desc = egg.attributes.description || ''
      const priorityMatch = desc.match(/\[priority:\s*([^\]]+)\]/i)
      if (priorityMatch) prioritySet.add(priorityMatch[1].trim().toLowerCase())
    })
  })

  // 2. Строим дерево
  nestsData.forEach(nest => {
    const nestName = nest.attributes.name
    const eggs = nest.attributes.relationships?.eggs?.data
    
    if (!eggs || eggs.length === 0) return

    // Создаем корневую категорию
    if (!root[nestName]) {
      root[nestName] = {
        name: nestName,
        type: 'folder',
        children: {},
        containsSelected: false,
        isPriority: prioritySet.has(nestName.toLowerCase())
      }
    }

    eggs.forEach((egg: any) => {
      egg._nestId = nest.attributes.id 

      const desc = egg.attributes.description || ''
      const pathMatch = desc.match(/\[PATH:\s*([^\]]+)\]/i)
      
      let pathParts: string[] = []

      if (pathMatch) {
        const rawParts = pathMatch[1].split('>').map((s: string) => s.trim())
        pathParts = rawParts.filter(part => {
          const p = part.toLowerCase()
          return !IGNORED_PATH_WORDS.includes(p) && p !== nestName.toLowerCase()
        })
      }

      if (pathParts.length > 0) {
        const lastFolder = pathParts[pathParts.length - 1].toLowerCase()
        const eggName = egg.attributes.name.toLowerCase()
        if (eggName.includes(lastFolder) || lastFolder === eggName) {
          pathParts.pop()
        }
      }

      let currentLevel = root[nestName].children

      pathParts.forEach(part => {
        if (!currentLevel[part]) {
          currentLevel[part] = { 
            name: part, 
            type: 'folder', 
            children: {}, 
            containsSelected: false, 
            isPriority: prioritySet.has(part.toLowerCase()) 
          }
        }
        else if (currentLevel[part].type === 'file') {
             const existingFile = currentLevel[part];
             currentLevel[part] = {
                 name: part,
                 type: 'folder',
                 children: {},
                 containsSelected: false,
                 isPriority: existingFile.isPriority
             };
             currentLevel[part].children[existingFile.name] = existingFile;
        }

        currentLevel = currentLevel[part].children
      })
      
      const eggName = egg.attributes.name;
      
      const fileNode = { 
        name: eggName, 
        type: 'file', 
        data: egg,
        isPriority: false 
      }

      if (currentLevel[eggName] && currentLevel[eggName].type === 'folder') {
          currentLevel[eggName].children[eggName] = fileNode
      } else {
          currentLevel[eggName] = fileNode
      }
    })
  })
  return root
}

// --- ФИЛЬТРАЦИЯ И ПОИСК ---
const filterNode = (node: any, query: string): any => {
  if (node.type === 'file') {
    return node.name.toLowerCase().includes(query) ? node : null
  }
  const filteredChildren: any = {}
  let hasMatchingChildren = false
  
  if (node.children) {
      Object.keys(node.children).forEach(key => {
        const result = filterNode(node.children[key], query)
        if (result) {
          filteredChildren[key] = result
          hasMatchingChildren = true
        }
      })
  }
  if (hasMatchingChildren) {
    return { ...node, children: filteredChildren, containsSelected: true }
  }
  return null
}

const filteredTree = computed(() => {
  if (!searchQuery.value) return fileTree.value
  const query = searchQuery.value.toLowerCase()
  const result: any = {}
  Object.keys(fileTree.value).forEach(key => {
    const filtered = filterNode(fileTree.value[key], query)
    if (filtered) result[key] = filtered
  })
  return result
})

// --- ОБРАБОТЧИК ВЫБОРА (ИСПРАВЛЕННЫЙ) ---
const onSelectEgg = (egg: any) => {
  if (egg && egg.attributes) {
      selectedNestId.value = egg._nestId
      selectedEggId.value = egg.attributes.id
      selectedEggName.value = egg.attributes.name
  }
}

// --- ЗАГРУЗКА ЯДЕР ---
const fetchNests = async () => {
  try {
    const { data } = await useApiFetch<any>('/api/eggs/tree')
    let nestsArray = []
    
    if (data.value) {
        if (Array.isArray(data.value)) {
            nestsArray = data.value
        } else if (Array.isArray(data.value.data)) {
            nestsArray = data.value.data
        }
    }

    if (nestsArray.length > 0) {
      fileTree.value = buildTreeCorrected(nestsArray)
    }
  } catch (e) {
    console.error('Ошибка загрузки ядер', e)
  }
}

// --- ОТПРАВКА ЗАПРОСА НА СМЕНУ ---
const confirmChangeCore = async () => {
  if (!selectedNestId.value || !selectedEggId.value) return alert('Выберите ядро из списка!')
  
  if (!confirm('Внимание! Смена ядра приведет к полному удалению файлов сервера и установке нового ядра. Продолжить?')) return

  isCoreChanging.value = true
  try {
    if (!service.value) return
    const { error } = await useApiFetch(`/api/services/${service.value.id}/change-core`, {
      method: 'POST',
      body: {
        nest_id: selectedNestId.value,
        egg_id: selectedEggId.value
      }
    })
    
    if (error.value) throw error.value
    
    alert('Ядро успешно изменено! Сервер отправлен на переустановку.')
    isChangeCoreOpen.value = false
    fetchService()
  } catch (e: any) {
    alert(e.data?.message || e.message || 'Ошибка смены ядра')
  } finally {
    isCoreChanging.value = false
  }
}

// --- УПРАВЛЕНИЕ БИЛЛИНГОМ ---
const toggleAutoRenew = async () => {
  if (!service.value) return
  const oldState = service.value.auto_renew
  service.value.auto_renew = !oldState
  isRenewLoading.value = true
  try {
    await $api(`/api/services/${service.value.id}/toggle-renew`, { method: 'POST', body: { auto_renew: !oldState } })
  } catch (e) {
    service.value.auto_renew = oldState
    alert('Ошибка обновления автопродления')
  } finally { isRenewLoading.value = false }
}

const requestDelete = () => { isDeleteModalOpen.value = true }
const confirmDelete = async () => {
  isDeleting.value = true
  try {
    if(service.value) await $api(`/api/services/${service.value.id}`, { method: 'DELETE' })
    router.push('/dashboard/services')
  } catch (e) { 
    isDeleteModalOpen.value = false 
    alert('Не удалось удалить услугу')
  } finally { isDeleting.value = false }
}

const copyToClipboard = (text: string, field: string) => { 
  if (!text) return
  navigator.clipboard.writeText(text)
  copiedField.value = field
  setTimeout(() => { copiedField.value = null }, 2000)
}

const formatDateFull = (dateStr?: string) => {
  if (!dateStr) return '...'
  return new Date(dateStr).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })
}
const goToPanel = () => { window.open(PANEL_URL, '_blank') }

onMounted(async () => {
  await fetchService()
  fetchNests()
  
  try {
    const { data } = await useApiFetch<any>('/api/user')
    if (data.value) {
      auth.user = data.value
    }
  } catch (e) {
    console.error('Ошибка обновления профиля:', e)
  }
})
</script>

<template>
  <div class="cyber-page">
    
    <ModalConfirm 
      :is-open="isDeleteModalOpen"
      title="Уничтожить сервер?"
      message="Внимание! Это действие удалит все данные безвозвратно."
      :loading="isDeleting"
      @close="isDeleteModalOpen = false"
      @confirm="confirmDelete"
    />

    <div v-if="isChangeCoreOpen" class="modal-backdrop">
      <div class="modal-window glass-panel extended-modal">
        <h2 class="panel-title">Смена ядра (Reinstall)</h2>
        <p class="text-xs text-gray-400 mb-4">
          Внимание: Смена ядра приведет к <span class="text-red-500 font-bold">полному удалению файлов</span> сервера и установке нового ядра.
        </p>
        
        <div class="search-box mb-4">
             <IconSearch class="search-icon"/>
             <input v-model="searchQuery" placeholder="Поиск ядра (Vanilla, Paper...)" class="search-input">
        </div>

        <div class="explorer-container custom-scrollbar mb-4">
             <TransitionGroup name="list">
                <FileExplorer 
                  v-for="key in Object.keys(filteredTree).sort((a,b) => {
                      const nodeA = filteredTree[a];
                      const nodeB = filteredTree[b];
                      if (nodeA.isPriority && !nodeB.isPriority) return -1;
                      if (!nodeA.isPriority && nodeB.isPriority) return 1;
                      return a.localeCompare(b);
                  })" 
                  :key="key" 
                  :node="filteredTree[key]" 
                  :selectedId="selectedEggId"
                  @select="onSelectEgg" 
                />
             </TransitionGroup>
             <div v-if="Object.keys(filteredTree).length === 0" class="empty-msg">
                Ничего не найдено
             </div>
        </div>

        <div class="selected-core-preview" :class="{ 'has-selection': selectedEggId }">
            <div class="scp-icon">
                <IconCheck v-if="selectedEggId" class="w-4 h-4 text-green-400" />
                <IconBox v-else class="w-4 h-4 text-gray-500" />
            </div>
            <div class="scp-info">
                <span class="scp-label">ВЫБРАННОЕ ЯДРО</span>
                <span class="scp-value">{{ selectedEggName || 'Не выбрано' }}</span>
            </div>
        </div>

        <div class="modal-actions mt-4">
          <button class="cosmic-btn danger" @click="isChangeCoreOpen = false">Отмена</button>
          <button class="cosmic-btn" :disabled="isCoreChanging || !selectedEggId" @click="confirmChangeCore">
            {{ isCoreChanging ? 'Установка...' : 'Установить' }}
            <div class="btn-glow"></div>
          </button>
        </div>
      </div>
    </div>

    <transition name="fade" mode="out-in">
      
      <div v-if="isLoading" class="loading-state" key="loading">
        <div class="loader-ring"><div></div><div></div><div></div><div></div></div>
        <p class="blink-text">ЗАГРУЗКА ДАННЫХ...</p>
      </div>

      <div v-else-if="service" class="content-wrapper" key="content">
        
        <div class="page-header slide-in-top">
          <button @click="router.push('/dashboard/services')" class="back-btn">
            <IconArrow class="icon-back" /> <span>НАЗАД</span>
          </button>
          
          <div class="header-content">
            <div class="header-left">
              <h1 class="page-title">{{ service.name }}</h1>
              <div class="id-badge">
                <span class="id-label">UUID:</span>
                <span class="id-val">{{ service.identifier }}</span>
              </div>
            </div>
            
            <button class="cyber-action-btn" @click="goToPanel">
              <span class="btn-text">ПЕРЕЙТИ В ПАНЕЛЬ</span>
              <IconExternal class="btn-icon" />
              <div class="btn-bg-glitch"></div>
            </button>
          </div>
        </div>

        <div class="grid-layout">
          
          <div class="left-col">
            
            <div class="server-status-panel stagger-1">
              <div class="status-glow-bg" :class="service.status"></div>
              
              <div class="panel-content">
                <div class="status-header">
                  <span class="panel-label">СТАТУС</span>
                  <div class="live-indicator" :class="service.status">
                    <span class="dot"></span><span class="ping-ring"></span>
                  </div>
                </div>

                <div class="status-main-text" :class="service.status">
                  {{ service.status === 'active' ? 'ONLINE' : (service.status === 'installing' ? 'INSTALLING' : 'SUSPENDED') }}
                </div>

                <div class="status-footer">
                   <div class="footer-item">
                     <span class="f-label">IP АДРЕС</span>
                     <span class="f-val">{{ service.ip_address || 'Загрузка...' }}</span>
                   </div>
                   <div class="vertical-line"></div>
                   <div class="footer-item">
                     <span class="f-label">РЕГИОН</span>
                     <span class="f-val">Europe (DE)</span>
                   </div>
                </div>
              </div>
            </div>

            <div class="glass-panel hud-panel stagger-2">
              <div class="hud-corner top-left"></div><div class="hud-corner top-right"></div>
              <div class="hud-corner bottom-left"></div><div class="hud-corner bottom-right"></div>

              <h2 class="panel-title">Данные для входа в Панель</h2>
              <div class="panel-desc mb-4 text-xs text-gray-500">
                Используйте эти данные для входа на {{ PANEL_URL }}
              </div>
              
              <div class="fields-grid">
                <div class="form-group">
                  <label>ЛОГИН (EMAIL)</label>
                  <div class="input-wrapper">
                    <input readonly :value="auth.user?.email" class="glass-input mono" />
                    <button class="copy-btn" @click="copyToClipboard(auth.user?.email || '', 'login')">
                      <svg v-if="copiedField === 'login'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="icon-success"><polyline points="20 6 9 17 4 12"></polyline></svg>
                      <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    </button>
                  </div>
                </div>

                <div class="form-group">
                  <label>ПАРОЛЬ</label>
                  <div class="input-wrapper">
                    <input 
                      readonly 
                      :type="showPassword ? 'text' : 'password'" 
                      :value="auth.user?.ptero_password || 'Пароль на почте / Старый'" 
                      class="glass-input mono" 
                    />
                    
                    <button class="copy-btn text-btn" @click="showPassword = !showPassword">
                      {{ showPassword ? 'СКРЫТЬ' : 'ПОКАЗАТЬ' }}
                    </button>
                    
                    <button v-if="auth.user?.ptero_password" class="copy-btn" @click="copyToClipboard(auth.user?.ptero_password || '', 'pass')">
                        <svg v-if="copiedField === 'pass'" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="icon-success"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="glass-panel mt-6 stagger-3 resource-panel-wrapper">
               <div class="cyber-line-top"></div>

               <div class="flex justify-between items-center mb-5 relative z-10">
                  <h2 class="panel-title mb-0">
                    <span class="text-icon">///</span> СИСТЕМНЫЕ РЕСУРСЫ
                  </h2>
                  
                  <button class="tech-btn" @click="isChangeCoreOpen = true">
                    <span class="btn-content">
                      <IconBox class="w-3 h-3 mr-2" /> 
                      <span>СМЕНИТЬ ЯДРО</span>
                    </span>
                    <div class="tech-btn-bg"></div>
                  </button>
               </div>
               
               <div class="resources-grid-v2">
                  <div class="res-card-v2 core-module">
                    <div class="card-bg-glow"></div>
                    <div class="card-header">
                      <IconCpu class="res-icon cpu-icon" />
                      <span class="res-label">АКТИВНОЕ ЯДРО</span>
                    </div>
                    <div class="card-value-group">
                      <div class="core-name">{{ service.core }}</div>
                      <div class="core-status">
                        <span class="blink-dot"></span> АКТИВНО
                      </div>
                    </div>
                    <div class="decor-corner-br"></div>
                  </div>

                  <div class="res-card-v2 tariff-module">
                    <div class="card-header">
                      <IconRam class="res-icon" />
                      <span class="res-label">ТАРИФ / ПЛАН</span>
                    </div>
                    <div class="card-value-group">
                       <div class="main-val">{{ service.product?.name }}</div>
                       <div class="sub-bar">
                          <div class="bar-fill" style="width: 75%"></div>
                       </div>
                    </div>
                  </div>

                  <div class="res-card-v2 price-module">
                    <div class="card-header">
                      <IconDisk class="res-icon" />
                      <span class="res-label">СТОИМОСТЬ</span>
                    </div>
                    <div class="card-value-group">
                       <div class="price-val">
                          {{ Number(service.price_monthly).toFixed(0) }} <span class="currency">₽</span>
                       </div>
                       <div class="price-cycle">Ежемесячно</div>
                    </div>
                  </div>
               </div>
            </div>

          </div>

          <div class="right-col">
            <div class="glass-panel billing-panel stagger-4">
              <div class="glow-blue-bottom"></div>
              <h2 class="panel-title">Биллинг</h2>
              <div class="billing-info">
                <div class="b-row"><span>СОЗДАН</span><span class="mono">{{ formatDateFull(service.created_at) }}</span></div>
                <div class="b-row highlight"><span>ИСТЕКАЕТ</span><span class="mono text-white">{{ formatDateFull(service.expires_at) }}</span></div>
              </div>
              <div class="divider"></div>
              <div class="auto-renew-row" @click="toggleAutoRenew">
                <div class="ar-info"><span class="ar-title">Автопродление</span><span class="ar-desc">Списание с баланса</span></div>
                <div class="cyber-toggle" :class="{ active: service.auto_renew }"><div class="toggle-track"></div><div class="toggle-thumb"></div></div>
              </div>
              <button class="cosmic-btn w-full mt-6"><span class="relative z-10">ПРОДЛИТЬ</span><div class="btn-glow"></div></button>
            </div>

            <div class="danger-panel mt-6 stagger-5">
              <div class="hazard-stripes"></div>
              <h3 class="danger-title">ОПАСНАЯ ЗОНА</h3>
              <p class="danger-desc">Удаление сервиса приведет к потере данных.</p>
              <button class="danger-btn" @click="requestDelete"><IconTrash class="d-icon" /> УДАЛИТЬ</button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
/* СТИЛИ МОДАЛКИ И НОВЫХ ЭЛЕМЕНТОВ */
.modal-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 999; display: flex; align-items: center; justify-content: center; padding: 20px; }
.modal-window { width: 100%; max-width: 450px; display: flex; flex-direction: column; max-height: 80vh; }
.extended-modal { max-width: 650px; } 

.search-box { position: relative; width: 100%; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: #666; }
.search-input { width: 100%; padding: 10px 10px 10px 38px; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 10px; color: #fff; font-size: 13px; transition: 0.2s; box-sizing: border-box; }
.search-input:focus { border-color: rgba(168, 85, 247, 0.5); outline: none; background: rgba(168, 85, 247, 0.05); }

.explorer-container { 
  flex: 1; overflow-y: auto; 
  padding: 4px; margin: -4px; 
  background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);
  min-height: 250px;
}
.empty-msg { text-align: center; color: #555; padding: 40px; }

/* Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #333; border-radius: 2px; }

/* Selected Core Preview */
.selected-core-preview { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 10px; transition: 0.3s; }
.selected-core-preview.has-selection { background: rgba(34, 197, 94, 0.05); border-color: rgba(34, 197, 94, 0.2); }
.scp-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2); border-radius: 8px; }
.scp-info { display: flex; flex-direction: column; }
.scp-label { font-size: 10px; color: #666; font-weight: 700; letter-spacing: 0.5px; }
.scp-value { font-size: 13px; color: #ddd; font-weight: 600; }
.has-selection .scp-value { color: #fff; }

.modal-actions { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; }
.small-action-btn { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #aaa; font-size: 10px; padding: 4px 10px; border-radius: 4px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; }
.small-action-btn:hover { background: #a855f7; color: white; border-color: #a855f7; }

/* Styles */
.cyber-page { position: relative; width: 100%; max-width: 1350px; margin: 0; padding-bottom: 100px; font-family: 'Inter', sans-serif; }
.content-wrapper { position: relative; z-index: 10; padding: 0 0; }
.mono { font-family: 'JetBrains Mono', monospace; }
.mt-6 { margin-top: 24px; }
.mt-4 { margin-top: 16px; }
.w-full { width: 100%; }
.grid-layout { display: grid; grid-template-columns: 1fr; gap: 30px; }
@media(min-width: 1024px) { .grid-layout { grid-template-columns: 1.3fr 0.7fr; gap: 40px; } }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
@keyframes slideUpFade { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.slide-in-top { animation: slideUpFade 0.6s ease-out forwards; }
.stagger-1 { animation: slideUpFade 0.6s ease-out 0.1s forwards; opacity: 0; }
.stagger-2 { animation: slideUpFade 0.6s ease-out 0.2s forwards; opacity: 0; }
.stagger-3 { animation: slideUpFade 0.6s ease-out 0.3s forwards; opacity: 0; }
.stagger-4 { animation: slideUpFade 0.6s ease-out 0.4s forwards; opacity: 0; }
.stagger-5 { animation: slideUpFade 0.6s ease-out 0.5s forwards; opacity: 0; }
.page-header { margin-bottom: 40px; }
.back-btn { background: none; border: none; color: #666; display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; cursor: pointer; letter-spacing: 1.5px; transition: 0.3s; margin-bottom: 20px; }
.back-btn:hover { color: #a855f7; gap: 12px; }
.icon-back { width: 12px; transform: rotate(180deg); }
.header-content { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px; }
.id-badge { display: flex; align-items: center; gap: 8px; font-family: 'JetBrains Mono', monospace; font-size: 12px; margin-top: 8px; }
.id-label { color: #555; }
.id-val { color: #888; background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px; }
.page-title { font-size: 42px; font-weight: 900; color: white; margin: 0; letter-spacing: -1px; text-transform: uppercase; line-height: 1; }
.cyber-action-btn { background: rgba(0,0,0,0.6); border: 1px solid #a855f7; color: #a855f7; border-radius: 4px; padding: 0 28px; height: 48px; display: flex; align-items: center; gap: 10px; cursor: pointer; position: relative; overflow: hidden; transition: all 0.3s ease; }
.cyber-action-btn:hover { background: #a855f7; color: white; box-shadow: 0 0 30px rgba(168, 85, 247, 0.4); }
.btn-text { font-weight: 800; font-size: 12px; letter-spacing: 1px; z-index: 2; }
.btn-icon { width: 16px; height: 16px; z-index: 2; }
.glass-panel { background: rgba(10, 10, 10, 0.6); border: 1px solid rgba(255, 255, 255, 0.06); backdrop-filter: blur(16px); padding: 25px; border-radius: 1px; position: relative; }
.hud-panel { border: 1px solid rgba(255,255,255,0.03); border-radius: 0; }
.hud-corner { position: absolute; width: 10px; height: 10px; border-color: rgba(255,255,255,0.2); border-style: solid; transition: 0.3s; }
.top-left { top: -1px; left: -1px; border-width: 2px 0 0 2px; }
.top-right { top: -1px; right: -1px; border-width: 2px 2px 0 0; }
.bottom-left { bottom: -1px; left: -1px; border-width: 0 0 2px 2px; }
.bottom-right { bottom: -1px; right: -1px; border-width: 0 2px 2px 0; }
.glass-panel:hover .hud-corner { border-color: #a855f7; width: 20px; height: 20px; }
.panel-title { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 24px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.panel-title::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.05); }
.fields-grid { display: flex; flex-direction: column; gap: 20px; }
.form-group label { display: block; font-size: 10px; color: #888; margin-bottom: 8px; font-weight: 600; letter-spacing: 1px; }
.input-wrapper { display: flex; gap: 0; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; overflow: hidden; transition: 0.3s; }
.input-wrapper:focus-within { border-color: #a855f7; box-shadow: 0 0 15px rgba(168, 85, 247, 0.1); }
.glass-input { flex: 1; background: transparent; border: none; padding: 14px; color: #ddd; font-size: 13px; outline: none; }
.copy-btn { background: rgba(255,255,255,0.03); border: none; border-left: 1px solid rgba(255,255,255,0.1); color: #777; cursor: pointer; padding: 0 16px; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
.copy-btn:hover { background: rgba(255,255,255,0.1); color: white; }
.text-btn { font-size: 10px; font-weight: 700; width: 80px; }
.icon-success { color: #22c55e; }
.billing-info { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; }
.b-row { display: flex; justify-content: space-between; font-size: 12px; color: #777; letter-spacing: 0.5px; }
.divider { height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent); margin: 24px 0; }
.auto-renew-row { display: flex; align-items: center; justify-content: space-between; cursor: pointer; padding: 10px; margin: -10px; border-radius: 8px; transition: 0.2s; }
.auto-renew-row:hover { background: rgba(255,255,255,0.02); }
.ar-title { display: block; font-size: 13px; font-weight: 600; color: #ddd; }
.ar-desc { font-size: 11px; color: #555; }
.cyber-toggle { width: 44px; height: 24px; position: relative; }
.toggle-track { width: 100%; height: 100%; background: #222; border-radius: 20px; border: 1px solid #333; transition: 0.3s; }
.toggle-thumb { width: 18px; height: 18px; background: #555; border-radius: 50%; position: absolute; top: 3px; left: 3px; transition: 0.3s cubic-bezier(0.5, 1.5, 0.5, 1); display: flex; align-items: center; justify-content: center; }
.cyber-toggle.active .toggle-track { border-color: #a855f7; background: rgba(168, 85, 247, 0.1); }
.cyber-toggle.active .toggle-thumb { transform: translateX(20px); background: #a855f7; box-shadow: 0 0 10px #a855f7; }
.cosmic-btn { position: relative; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); color: white; font-weight: 700; font-size: 12px; padding: 14px; border-radius: 6px; cursor: pointer; overflow: hidden; letter-spacing: 1px; transition: 0.3s; }
.cosmic-btn:hover { border-color: #a855f7; background: rgba(168, 85, 247, 0.05); }
.btn-glow { position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); transform: skewX(-20deg); transition: 0.5s; }
.cosmic-btn:hover .btn-glow { left: 150%; transition: 0.7s; }
.danger-panel { background: rgba(20, 5, 5, 0.4); border: 1px solid rgba(239, 68, 68, 0.15); border-radius: 8px; padding: 20px; text-align: center; position: relative; overflow: hidden; }
.hazard-stripes { position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: repeating-linear-gradient(45deg, #ef4444, #ef4444 10px, transparent 10px, transparent 20px); opacity: 0.6; }
.danger-title { color: #ef4444; font-size: 12px; font-weight: 800; letter-spacing: 2px; margin-bottom: 6px; }
.danger-desc { color: #777; font-size: 11px; margin-bottom: 16px; }
.danger-btn { background: transparent; border: 1px solid #ef4444; color: #ef4444; width: 100%; padding: 12px; border-radius: 4px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s; }
.danger-btn:hover { background: #ef4444; color: white; box-shadow: 0 0 15px rgba(239, 68, 68, 0.3); }
.d-icon { width: 16px; height: 16px; min-width: 16px; flex-shrink: 0; margin-right: 8px; }
.loading-state { height: 60vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; }
.loader-ring { display: inline-block; position: relative; width: 64px; height: 64px; }
.loader-ring div { box-sizing: border-box; display: block; position: absolute; width: 50px; height: 50px; margin: 8px; border: 3px solid #a855f7; border-radius: 50%; animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite; border-color: #a855f7 transparent transparent transparent; }
.loader-ring div:nth-child(1) { animation-delay: -0.45s; }
.loader-ring div:nth-child(2) { animation-delay: -0.3s; }
.loader-ring div:nth-child(3) { animation-delay: -0.15s; }
@keyframes lds-ring { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.blink-text { color: #555; font-family: monospace; letter-spacing: 2px; animation: blink 2s infinite; font-size: 12px; }
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
.server-status-panel { position: relative; width: 100%; border-radius: 12px; background: rgba(15, 15, 20, 0.6); border: 1px solid rgba(255, 255, 255, 0.08); overflow: hidden; margin-bottom: 30px; backdrop-filter: blur(10px); }
.status-glow-bg { position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; filter: blur(80px); opacity: 0.25; transition: 0.5s; border-radius: 50%; }
.status-glow-bg.active { background: #22c55e; }
.status-glow-bg.stopped { background: #ef4444; }
.status-glow-bg.installing { background: #c084fc; } 
.panel-content { position: relative; z-index: 2; padding: 24px 30px; display: flex; flex-direction: column; gap: 16px; }
.status-header { display: flex; justify-content: space-between; align-items: center; }
.panel-label { font-size: 11px; font-weight: 700; letter-spacing: 1px; color: #555; }
.live-indicator { position: relative; display: flex; align-items: center; justify-content: center; width: 12px; height: 12px; }
.live-indicator .dot { width: 8px; height: 8px; background: #444; border-radius: 50%; z-index: 2; }
.live-indicator .ping-ring { position: absolute; width: 100%; height: 100%; border-radius: 50%; opacity: 0; z-index: 1; }
.live-indicator.active .dot { background: #22c55e; box-shadow: 0 0 10px rgba(34, 197, 94, 0.6); }
.live-indicator.active .ping-ring { background: #22c55e; animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite; }
.live-indicator.stopped .dot { background: #ef4444; box-shadow: 0 0 10px rgba(239, 68, 68, 0.6); }
.live-indicator.installing .dot { background: #c084fc; box-shadow: 0 0 10px rgba(192, 132, 252, 0.6); }
@keyframes ping { 75%, 100% { transform: scale(2.5); opacity: 0; } }
.status-main-text { font-family: 'JetBrains Mono', monospace; font-size: 36px; font-weight: 800; letter-spacing: -1px; color: #333; transition: 0.3s; }
.status-main-text.active { color: #fff; text-shadow: 0 0 30px rgba(34, 197, 94, 0.3); }
.status-main-text.stopped { color: #ef4444; text-shadow: 0 0 30px rgba(239, 68, 68, 0.3); }
.status-main-text.installing { color: #c084fc; text-shadow: 0 0 30px rgba(192, 132, 252, 0.3); }
.status-footer { display: flex; align-items: center; gap: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.05); }
.footer-item { display: flex; flex-direction: column; gap: 2px; }
.f-label { font-size: 10px; color: #555; font-weight: 600; letter-spacing: 0.5px; }
.f-val { font-size: 13px; color: #bbb; font-weight: 500; font-family: 'JetBrains Mono', monospace; }
.f-val.ok { color: #22c55e; }
.f-val.err { color: #ef4444; }
.vertical-line { width: 1px; height: 20px; background: rgba(255,255,255,0.05); }
.text-purple-400 { color: #c084fc; }
.text-red-500 { color: #ef4444; }
.text-green-400 { color: #4ade80; }
.text-gray-500 { color: #6b7280; }
.font-bold { font-weight: 700; }
.w-4 { width: 1rem; }
.h-4 { height: 1rem; }
.flex { display: flex; }
.justify-between { justify-content: space-between; }
.items-center { align-items: center; }
.mb-0 { margin-bottom: 0 !important; }
.mb-4 { margin-bottom: 1rem; }
.mb-5 { margin-bottom: 1.25rem; }
.mr-2 { margin-right: 0.5rem; }
.w-3 { width: 0.75rem; }
.h-3 { height: 0.75rem; }
.cosmic-btn.danger { background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3); color: #fca5a5; }
.cosmic-btn.danger:hover { background: rgba(239, 68, 68, 0.2); border-color: #ef4444; color: white; }

/* --- НОВЫЕ СТИЛИ ДЛЯ ПАНЕЛИ РЕСУРСОВ --- */
.resource-panel-wrapper {
  background: linear-gradient(160deg, rgba(10, 10, 15, 0.8) 0%, rgba(20, 20, 25, 0.6) 100%);
  border: 1px solid rgba(255, 255, 255, 0.05);
  overflow: hidden;
}

.cyber-line-top {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 2px;
  background: linear-gradient(90deg, transparent, #a855f7, transparent);
  opacity: 0.5;
}

.text-icon {
  color: #a855f7;
  margin-right: 8px;
  font-weight: 900;
  letter-spacing: -2px;
}

.resources-grid-v2 {
  display: grid;
  grid-template-columns: 1.2fr 1fr 0.8fr; 
  gap: 12px;
}

@media (max-width: 768px) {
  .resources-grid-v2 {
    grid-template-columns: 1fr;
  }
}

.res-card-v2 {
  position: relative;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  padding: 16px;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 100px;
  transition: all 0.3s ease;
  overflow: hidden;
}

.res-card-v2:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.1);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.res-icon {
  width: 14px;
  height: 14px;
  color: #666;
  transition: 0.3s;
}

.res-card-v2:hover .res-icon {
  color: #fff;
}

.res-label {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 1px;
  color: #555;
  text-transform: uppercase;
}

.card-value-group {
  position: relative;
  z-index: 2;
}

/* === CORE MODULE === */
.core-module {
  background: radial-gradient(circle at top right, rgba(168, 85, 247, 0.1), transparent 70%), rgba(255, 255, 255, 0.02);
  border-color: rgba(168, 85, 247, 0.2);
}

.core-module:hover {
  border-color: rgba(168, 85, 247, 0.5);
  box-shadow: 0 0 20px rgba(168, 85, 247, 0.1);
}

.core-module .res-icon {
  color: #a855f7;
}

.core-name {
  font-family: 'JetBrains Mono', monospace;
  font-size: 16px;
  font-weight: 700;
  color: #fff;
  margin-bottom: 4px;
  word-break: break-all;
}

.core-status {
  font-size: 9px;
  color: #a855f7;
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
  letter-spacing: 1px;
}

.blink-dot {
  width: 4px;
  height: 4px;
  background: #a855f7;
  border-radius: 50%;
  box-shadow: 0 0 5px #a855f7;
  animation: blink 2s infinite;
}

.decor-corner-br {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 0 0 20px 20px;
  border-color: transparent transparent rgba(168, 85, 247, 0.2) transparent;
}

/* === TARIFF MODULE === */
.main-val {
  font-size: 14px;
  font-weight: 600;
  color: #ddd;
  margin-bottom: 8px;
}

.sub-bar {
  width: 100%;
  height: 2px;
  background: rgba(255,255,255,0.1);
  border-radius: 2px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  background: #60a5fa;
  box-shadow: 0 0 8px #60a5fa;
}

.tariff-module:hover .bar-fill {
  background: #93c5fd;
}

/* === PRICE MODULE === */
.price-val {
  font-family: 'JetBrains Mono', monospace;
  font-size: 18px;
  font-weight: 700;
  color: #fff;
}

.currency {
  font-size: 12px;
  color: #666;
  font-weight: 400;
}

.price-cycle {
  font-size: 9px;
  color: #444;
  margin-top: 2px;
}

/* === TECH BUTTON === */
.tech-btn {
  position: relative;
  background: transparent;
  border: 1px solid rgba(168, 85, 247, 0.3);
  height: 28px;
  padding: 0 12px;
  border-radius: 2px;
  cursor: pointer;
  overflow: hidden;
  transition: 0.2s;
}

.tech-btn .btn-content {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  font-size: 10px;
  font-weight: 700;
  color: #a855f7;
  letter-spacing: 1px;
}

.tech-btn-bg {
  position: absolute;
  top: 0;
  left: 0;
  width: 0%;
  height: 100%;
  background: #a855f7;
  z-index: 1;
  transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.tech-btn:hover {
  border-color: #a855f7;
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.2);
}

.tech-btn:hover .tech-btn-bg {
  width: 100%;
}

.tech-btn:hover .btn-content {
  color: #fff;
}
</style>