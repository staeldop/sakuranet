<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiFetch } from '~/composables/useApi'
import FileExplorer from '~/components/FileExplorer.vue'

// Иконки
import IconArrow from '~/assets/icons/arrow-right.svg?component'
import IconSearch from '~/assets/icons/search.svg?component'
// Иконки для правой карточки
import IconCpu from '~/assets/icons/cpu.svg?component'
import IconRam from '~/assets/icons/ram.svg?component'
import IconDisk from '~/assets/icons/disk.svg?component'
import IconServer from '~/assets/icons/server.svg?component'
import imgFlagDE from '~/assets/flags/de.png'

definePageMeta({ layout: 'dashboard' })

const route = useRoute()
const router = useRouter()
const isLoading = ref(true)
const isSubmitting = ref(false)
const product = ref<any>(null)

// Данные формы
const serverName = ref('')
const selectedPeriod = ref(1) // 1 Месяц по умолчанию
const selectedEgg = ref<any>(null)
const selectedNestId = ref<number | null>(null)
const fileTree = ref<any>({})
const searchQuery = ref('')

const periods = [
  { months: 1, label: '1 Месяц', discount: 0 },
  { months: 3, label: '3 Месяца', discount: 5 },
  { months: 6, label: '6 Месяцев', discount: 10 },
  { months: 12, label: '1 Год', discount: 20 },
]

const IGNORED_PATH_WORDS = ['game_eggs', 'twitch', 'voice_servers', 'other', 'misc', 'software']

// --- ЛОГИКА ДЕРЕВА ---
const buildTreeCorrected = (nestsData: any[]) => {
  const root: any = {}
  const prioritySet = new Set<string>()

  nestsData.forEach(nest => {
    if (!nest.attributes?.relationships?.eggs?.data) return;
    nest.attributes.relationships.eggs.data.forEach((egg: any) => {
      const desc = egg.attributes.description || ''
      const priorityMatch = desc.match(/\[priority:\s*([^\]]+)\]/i)
      if (priorityMatch) prioritySet.add(priorityMatch[1].trim().toLowerCase())
    })
  })

  nestsData.forEach(nest => {
    const nestName = nest.attributes.name
    const eggs = nest.attributes.relationships?.eggs?.data
    if (!eggs || eggs.length === 0) return

    if (!root[nestName]) {
      root[nestName] = {
        name: nestName, type: 'folder', children: {}, containsSelected: false,
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
        if (eggName.includes(lastFolder) || lastFolder === eggName) pathParts.pop()
      }

      let currentLevel = root[nestName].children
      pathParts.forEach(part => {
        if (!currentLevel[part]) {
          currentLevel[part] = { 
            name: part, type: 'folder', children: {}, containsSelected: false,
            isPriority: prioritySet.has(part.toLowerCase()) 
          }
        } else if (currentLevel[part].type === 'file') {
             const existingFile = currentLevel[part];
             currentLevel[part] = {
                 name: part, type: 'folder', children: {}, containsSelected: false,
                 isPriority: existingFile.isPriority
             };
             currentLevel[part].children[existingFile.name] = existingFile;
        }
        currentLevel = currentLevel[part].children
      })
      
      const eggName = egg.attributes.name;
      if (currentLevel[eggName] && currentLevel[eggName].type === 'folder') {
          currentLevel[eggName].children[eggName] = { name: eggName, type: 'file', data: egg, isPriority: false }
      } else {
          currentLevel[eggName] = { name: eggName, type: 'file', data: egg, isPriority: false }
      }
    })
  })
  return root
}

const filterNode = (node: any, query: string): any => {
  if (node.type === 'file') return node.name.toLowerCase().includes(query) ? node : null
  const filteredChildren: any = {}
  let hasMatchingChildren = false
  if (node.children) {
      Object.keys(node.children).forEach(key => {
        const result = filterNode(node.children[key], query)
        if (result) { filteredChildren[key] = result; hasMatchingChildren = true }
      })
  }
  return hasMatchingChildren ? { ...node, children: filteredChildren, containsSelected: true } : null
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

const onSelect = (egg: any) => {
  selectedEgg.value = egg
  selectedNestId.value = egg._nestId
}

const totalPrice = computed(() => {
  if (!product.value) return 0
  const base = Number(product.value.price)
  const per = periods.find(p => p.months === selectedPeriod.value)
  return (base * selectedPeriod.value) * (1 - (per?.discount || 0) / 100)
})

const formatPrice = (val: number) => new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val)

const handleCheckout = async () => {
  if (!serverName.value.trim()) return alert('Введите имя сервера')
  if (!selectedEgg.value) return alert('Выберите ядро')

  isSubmitting.value = true
  try {
    const { data, error } = await useApiFetch<any>('/api/services', {
      method: 'POST',
      body: {
        product_id: product.value.id,
        name: serverName.value,
        period: selectedPeriod.value,
        egg_id: selectedEgg.value.attributes.id,
        docker_image: selectedEgg.value.attributes.docker_image,
        nest_id: selectedNestId.value
      }
    })

    if (error.value) throw new Error(error.value.data?.message || error.value.message || 'Ошибка')

    const newPassword = data.value?.new_user_password
    if (newPassword) alert(`Успешно!\nВаш пароль: ${newPassword}\n(Сохраните его!)`)
    else alert('Сервер успешно оплачен и устанавливается!')

    if (data.value?.service?.id) router.push(`/dashboard/services/${data.value.service.id}`)
    else router.push('/dashboard/services')

  } catch (e: any) {
    alert(e.message || 'Ошибка')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(async () => {
  const id = route.query.id
  if (!id) return router.push('/dashboard/order')
  try {
    const { data: prodData } = await useApiFetch<any[]>('/api/products', { server: false })
    if (prodData.value) product.value = prodData.value.find((p: any) => p.id == id)
    
    const { data: treeData } = await useApiFetch<any>('/api/eggs/tree')
    let nestsArray = [];
    if (treeData.value) {
        if (Array.isArray(treeData.value)) nestsArray = treeData.value;
        else if (Array.isArray(treeData.value.data)) nestsArray = treeData.value.data;
    }
    if (nestsArray.length > 0) fileTree.value = buildTreeCorrected(nestsArray)
  } catch (e) { console.error(e) } finally { isLoading.value = false }
})
</script>

<template>
  <div class="checkout-page">
    <div class="content-wrapper">
      
      <div class="header-row">
        <button @click="router.back()" class="back-link"><IconArrow class="icon-arrow" /> Назад</button>
        <div class="title-group">
          <ClientOnly>
             <h1 class="page-title">Сборка сервера</h1>
             <p class="subtitle">Выберите конфигурацию для {{ product?.name }}</p>
          </ClientOnly>
        </div>
      </div>

      <div class="checkout-grid">
        
        <div class="left-column">
          
          <div class="section-block">
            <h2 class="block-title">1. Название сервера</h2>
            <div class="input-wrapper">
              <input v-model="serverName" class="modern-input" placeholder="Например: Survival Server">
            </div>
          </div>

          <div class="section-block">
            <div class="block-header">
              <h2 class="block-title">2. Программное обеспечение</h2>
              <div class="search-box">
                <IconSearch class="search-icon"/>
                <input v-model="searchQuery" placeholder="Поиск (Paper, Vanilla)..." class="search-input">
              </div>
            </div>

            <div class="explorer-container custom-scrollbar">
              <TransitionGroup name="list">
                <FileExplorer 
                  v-for="key in Object.keys(filteredTree).sort((a,b) => {
                      const nodeA = filteredTree[a]; const nodeB = filteredTree[b];
                      if (nodeA.isPriority && !nodeB.isPriority) return -1;
                      if (!nodeA.isPriority && nodeB.isPriority) return 1;
                      return a.localeCompare(b);
                  })" 
                  :key="key" 
                  :node="filteredTree[key]" 
                  :selectedId="selectedEgg?.attributes?.id || null"
                  @select="onSelect" 
                />
              </TransitionGroup>
              
              <div v-if="Object.keys(filteredTree).length === 0" class="empty-msg">
                Ничего не найдено
              </div>
            </div>
          </div>

          <div class="section-block">
            <h2 class="block-title">3. Срок аренды</h2>
            
            <div class="periods-locked-wrapper">
              <div class="periods-grid disabled-state">
                <button 
                  v-for="per in periods" :key="per.months"
                  class="period-card" :class="{ active: selectedPeriod === per.months }"
                >
                  <div class="ambient-glow blue"></div>
                  <span class="period-val">{{ per.label }}</span>
                  <span v-if="per.discount" class="discount-tag">-{{ per.discount }}%</span>
                </button>
              </div>

              <div class="locked-overlay">
                <div class="status-badge">
                  🚧 В будущем
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="right-column">
          
          <div class="product-card sticky top-5">
            
            <div class="glow-bg"></div>

            <div class="card-content">
                <div class="card-header">
                     <div class="header-icon-box">
                        <img :src="imgFlagDE" class="header-flag" />
                     </div>
                     <div class="header-info">
                        <ClientOnly>
                            <h3 class="product-title">{{ product?.name || 'Загрузка...' }}</h3>
                        </ClientOnly>
                        <div class="location-row">
                            <span class="status-dot"></span>
                            <span class="loc-text">Falkenstein, DE</span>
                        </div>
                     </div>
                </div>

                <div class="divider"></div>

                <ClientOnly>
                <div class="specs-grid">
                    <div class="spec-box">
                        <div class="spec-icon-wrap"><IconCpu class="spec-svg" /></div>
                        <div class="spec-info">
                            <span class="spec-val">{{ product?.cpu_limit ? product.cpu_limit + '%' : '-' }}</span>
                            <span class="spec-label">vCore</span>
                        </div>
                    </div>
                    <div class="spec-box">
                        <div class="spec-icon-wrap"><IconRam class="spec-svg" /></div>
                        <div class="spec-info">
                             <span class="spec-val">{{ product?.memory_mb ? (product.memory_mb / 1024).toFixed(0) + ' GB' : '-' }}</span>
                            <span class="spec-label">RAM</span>
                        </div>
                    </div>
                    <div class="spec-box">
                        <div class="spec-icon-wrap"><IconDisk class="spec-svg" /></div>
                        <div class="spec-info">
                            <span class="spec-val">{{ product?.disk_mb ? (product.disk_mb / 1024).toFixed(0) + ' GB' : '-' }}</span>
                            <span class="spec-label">NVMe</span>
                        </div>
                    </div>
                    <div class="spec-box">
                        <div class="spec-icon-wrap"><IconServer class="spec-svg" /></div>
                        <div class="spec-info">
                            <span class="spec-val">1 Gbps</span>
                            <span class="spec-label">Port</span>
                        </div>
                    </div>
                </div>
                </ClientOnly>

                <div class="software-status-box" :class="{ 'has-selection': selectedEgg }">
                    <div class="soft-icon-wrap">
                        <IconSearch v-if="!selectedEgg" class="spec-svg" />
                        <svg v-else class="spec-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12l5 5L20 7"></path></svg>
                    </div>
                    <div class="spec-info">
                         <span class="spec-val">{{ selectedEgg ? selectedEgg.attributes.name : 'Ядро не выбрано' }}</span>
                         <span class="spec-label">Software</span>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="price-row">
                        <span class="currency">₽</span>
                        <span class="amount">{{ formatPrice(totalPrice).replace('₽', '').trim() }}</span>
                        <span class="period">/мес</span>
                    </div>
                    
                    <button @click="handleCheckout" :disabled="isSubmitting || !selectedEgg" class="action-btn-full">
                         <span v-if="!isSubmitting">Создать сервер</span>
                         <span v-else class="spinner-sm"></span>
                    </button>
                </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* === LAYOUT & COMMON === */
.checkout-page { width: 100%; max-width: 1200px; padding-bottom: 80px; padding-left: 20px; padding-right: 20px; color: #eee; font-family: 'Inter', sans-serif; }
.header-row { margin-bottom: 30px; }
.back-link { background: none; border: none; color: #666; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: 0.2s; padding: 0; font-size: 14px; margin-bottom: 10px; }
.back-link:hover { color: #fff; }
.icon-arrow { width: 16px; transform: rotate(180deg); }
.page-title { color: #fff; font-size: 32px; font-weight: 700; margin: 0; }
.subtitle { color: #666; font-size: 14px; margin-top: 5px; }

.checkout-grid { display: grid; grid-template-columns: 1fr; gap: 40px; }
@media(min-width: 1024px) { .checkout-grid { grid-template-columns: 1.8fr 1fr; } }

/* === ЛЕВАЯ КОЛОНКА === */
.section-block { margin-bottom: 40px; }
.block-title { font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8; }
.block-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }

/* Inputs */
.modern-input { width: 100%; padding: 14px 16px; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; color: #fff; font-size: 15px; transition: 0.2s; box-sizing: border-box; }
.modern-input:focus { border-color: rgba(255, 255, 255, 0.2); background: rgba(255, 255, 255, 0.04); outline: none; }

.search-box { position: relative; width: 250px; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; color: #666; }
.search-input { width: 100%; padding: 10px 10px 10px 38px; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 10px; color: #fff; font-size: 13px; transition: 0.2s; box-sizing: border-box; }
.search-input:focus { border-color: rgba(255, 255, 255, 0.2); outline: none; }

.explorer-container { max-height: 500px; overflow-y: auto; padding: 4px; margin: -4px; }
.empty-msg { text-align: center; color: #555; padding: 40px; border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px; }

/* Periods */
.periods-locked-wrapper { position: relative; border-radius: 16px; overflow: hidden; }
.periods-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.disabled-state { opacity: 0.4; filter: grayscale(0.8); pointer-events: none; }
.period-card { position: relative; padding: 16px; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px; cursor: pointer; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: 0.2s; }
.period-card.active { border-color: #3b82f6; background: rgba(59, 130, 246, 0.05); }
.period-val { font-weight: 500; font-size: 14px; color: #ddd; z-index: 2; }
.period-card.active .period-val { color: #fff; }
.discount-tag { position: absolute; top: 5px; right: 5px; background: #22c55e; color: #000; font-size: 10px; padding: 2px 6px; border-radius: 100px; font-weight: 700; z-index: 2; }

.locked-overlay { position: absolute; inset: 0; z-index: 10; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.2); backdrop-filter: blur(2px); }
.status-badge { background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); padding: 8px 16px; border-radius: 50px; font-weight: 600; font-size: 13px; color: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }

.ambient-glow { position: absolute; left: 0; top: 0; bottom: 0; width: 80px; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
.ambient-glow.blue { background: radial-gradient(circle at left center, #3b82f6, transparent 70%); }
.period-card:hover .ambient-glow { opacity: 0.15; }
.period-card.active .ambient-glow { opacity: 0.25; }

/* === ПРАВАЯ КОЛОНКА (БЕЗ ЛИШНИХ ФОНОВ) === */
.product-card {
    position: relative;
    background: #050505;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    padding: 24px;
    overflow: hidden;
    display: flex; flex-direction: column;
}

.glow-bg {
    position: absolute; top: -50%; right: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle at 70% 30%, rgba(120, 119, 198, 0.05), transparent 50%);
    opacity: 1; pointer-events: none;
}

.card-content { position: relative; z-index: 2; display: flex; flex-direction: column; height: 100%; }

/* Header */
.card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
/* УБРАЛ ФОН И БОРДЮР У БЛОКА ФЛАГА */
.header-icon-box {
    width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.header-flag { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; opacity: 0.9; }
.header-info { display: flex; flex-direction: column; }
.product-title { margin: 0; font-size: 18px; font-weight: 700; color: #fff; line-height: 1.2; }
.location-row { display: flex; align-items: center; gap: 6px; margin-top: 4px; }
.status-dot { width: 6px; height: 6px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 8px #22c55e; }
.loc-text { font-size: 12px; color: #666; }

.divider { height: 1px; background: #1a1a1a; margin-bottom: 20px; width: 100%; }

/* Grid */
.specs-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 24px; /* Увеличил отступ между колонками */
    margin-bottom: 20px;
}
/* УБРАЛ ФОН И БОРДЮР У ПЛИТОК */
.spec-box {
    display: flex; align-items: center; gap: 12px;
}
.spec-icon-wrap { color: #666; display: flex; align-items: center; justify-content: center; } /* Чуть светлее иконки */
.spec-svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 1.5; }
.spec-info { display: flex; flex-direction: column; }
.spec-val { font-size: 14px; font-weight: 700; color: #eee; line-height: 1.1; }
.spec-label { font-size: 11px; color: #666; margin-top: 2px; }

/* Selected Software Row */
/* УБРАЛ БАЗОВЫЙ ФОН, ОСТАВИЛ ТОЛЬКО ПРИ ВЫБОРЕ */
.software-status-box {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 24px;
    transition: 0.3s;
    border-radius: 12px;
    padding: 10px 0;
}
.software-status-box.has-selection {
    background: rgba(34, 197, 94, 0.08); /* Легкий зеленый фон при выборе */
    padding-left: 12px;
}
.soft-icon-wrap { color: #666; }
.has-selection .soft-icon-wrap { color: #22c55e; }

/* Footer */
.card-footer { margin-top: auto; }
.price-row { display: flex; align-items: baseline; margin-bottom: 12px; }
.currency { font-size: 18px; color: #fff; font-weight: 500; margin-right: 2px; }
.amount { font-size: 32px; color: #fff; font-weight: 700; letter-spacing: -1px; }
.period { font-size: 14px; color: #666; margin-left: 6px; }

.action-btn-full {
    width: 100%;
    background: #fff; color: #000;
    text-decoration: none; padding: 14px;
    border-radius: 12px; font-size: 14px; font-weight: 700;
    border: none; cursor: pointer;
    transition: all 0.2s;
    display: flex; align-items: center; justify-content: center;
}
.action-btn-full:hover:not(:disabled) { background: #e5e5e5; transform: translateY(-1px); }
.action-btn-full:disabled { background: #222; color: #555; cursor: not-allowed; }

.spinner-sm {
  width: 18px; height: 18px;
  border: 2px solid rgba(255,255,255,0.2);
  border-top-color: #888; border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #333; border-radius: 2px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.list-move, .list-enter-active, .list-leave-active { transition: all 0.4s ease; }
.list-leave-to { opacity: 0; transform: translateX(20px); }
.list-enter-from { opacity: 0; transform: translateX(-20px); }
.list-leave-active { position: absolute; width: 100%; z-index: -1; }
</style>