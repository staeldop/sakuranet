<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiFetch } from '~/composables/useApi'
import FileExplorer from '~/components/FileExplorer.vue'

// Иконки
import IconArrow from '~/assets/icons/arrow-right.svg?component'
import IconSearch from '~/assets/icons/search.svg?component'
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

// --- ЛОГИКА ДЕРЕВА (С ЗАЩИТОЙ ОТ ОШИБОК) ---
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

      // Убираем дублирование имен в пути (Paper > Paper -> Paper)
      if (pathParts.length > 0) {
        const lastFolder = pathParts[pathParts.length - 1].toLowerCase()
        const eggName = egg.attributes.name.toLowerCase()
        if (eggName.includes(lastFolder) || lastFolder === eggName) {
          pathParts.pop()
        }
      }

      let currentLevel = root[nestName].children

      pathParts.forEach(part => {
        // Если папки нет - создаем
        if (!currentLevel[part]) {
          currentLevel[part] = { 
            name: part, 
            type: 'folder', 
            children: {}, 
            containsSelected: false,
            isPriority: prioritySet.has(part.toLowerCase()) 
          }
        }
        // 🔥 ФИКС ОШИБКИ: Если на этом месте стоит ФАЙЛ, превращаем его в ПАПКУ
        else if (currentLevel[part].type === 'file') {
             const existingFile = currentLevel[part]; // Сохраняем файл
             currentLevel[part] = {
                 name: part,
                 type: 'folder',
                 children: {}, // Создаем детей
                 containsSelected: false,
                 isPriority: existingFile.isPriority
             };
             // Возвращаем файл обратно, но уже внутрь папки
             currentLevel[part].children[existingFile.name] = existingFile;
        }

        currentLevel = currentLevel[part].children
      })
      
      // Добавляем само ядро (файл)
      // Если мы хотим положить файл, а там уже папка с таким именем - кладем внутрь
      const eggName = egg.attributes.name;
      
      if (currentLevel[eggName] && currentLevel[eggName].type === 'folder') {
          currentLevel[eggName].children[eggName] = { 
            name: eggName, 
            type: 'file', 
            data: egg,
            isPriority: false 
          }
      } else {
          currentLevel[eggName] = { 
            name: eggName, 
            type: 'file', 
            data: egg,
            isPriority: false 
          }
      }
    })
  })
  return root
}

// Фильтрация
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

const onSelect = (egg: any) => {
  selectedEgg.value = egg
  selectedNestId.value = egg._nestId
}

// Покупка
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
    // Получаем полный ответ (data), чтобы вытащить пароль
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

    if (error.value) {
      const msg = error.value.data?.message || error.value.message || 'Ошибка при создании'
      throw new Error(msg)
    }

    // Проверяем, пришел ли новый пароль (для новых юзеров Pterodactyl)
    const newPassword = data.value?.new_user_password
    
    if (newPassword) {
      alert(`Успешно!\n\nВаш аккаунт в панели управления создан.\nПароль: ${newPassword}\n\n(Обязательно сохраните его! Также его можно найти в настройках сервера)`)
    } else {
      alert('Сервер успешно оплачен и устанавливается!')
    }

    // Перенаправляем сразу в настройки созданного сервера
    if (data.value?.service?.id) {
       router.push(`/dashboard/services/${data.value.service.id}`)
    } else {
       router.push('/dashboard/services')
    }

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
    
    // Поддержка и плоского массива, и JSON:API
    let nestsArray = [];
    if (treeData.value) {
        if (Array.isArray(treeData.value)) {
            nestsArray = treeData.value;
        } else if (Array.isArray(treeData.value.data)) {
            nestsArray = treeData.value.data;
        }
    }

    if (nestsArray.length > 0) {
      fileTree.value = buildTreeCorrected(nestsArray)
    }
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
                      const nodeA = filteredTree[a];
                      const nodeB = filteredTree[b];
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
          <div class="summary-card sticky top-5">
            <div class="ambient-glow purple"></div>
            
            <div class="summary-header">
              <img :src="imgFlagDE" class="flag-icon" />
              <div class="prod-info">
                <ClientOnly>
                    <h3 class="prod-name">{{ product?.name || '...' }}</h3>
                    <span class="prod-cat">Falkenstein, DE</span>
                </ClientOnly>
              </div>
            </div>

            <ClientOnly>
                <div class="price-section">
                <div class="price-val">{{ formatPrice(totalPrice) }}</div>
                <div class="price-sub">за {{ selectedPeriod }} мес.</div>
                </div>
            </ClientOnly>

            <div class="divider"></div>

            <ClientOnly>
                <div class="specs-list">
                <div class="spec-row">
                    <span class="label">CPU</span>
                    <span class="val">{{ product?.cpu_limit ? product.cpu_limit + '%' : '...' }}</span>
                </div>
                <div class="spec-row">
                    <span class="label">RAM</span>
                    <span class="val">{{ product?.memory_mb ? product.memory_mb + ' MB' : '...' }}</span>
                </div>
                <div class="spec-row">
                    <span class="label">NVMe Disk</span>
                    <span class="val">{{ product?.disk_mb ? product.disk_mb + ' MB' : '...' }}</span>
                </div>
                
                <div class="selected-core-box" :class="{ 'has-core': selectedEgg }">
                    <div class="sc-label">Ядро</div>
                    <div class="sc-val">{{ selectedEgg ? selectedEgg.attributes.name : 'Не выбрано' }}</div>
                </div>
                </div>
            </ClientOnly>

            <button @click="handleCheckout" :disabled="isSubmitting" class="checkout-btn">
              {{ isSubmitting ? 'Создание...' : 'Оплатить и создать' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Layout */
.checkout-page { width: 100%; max-width: 1200px; padding-bottom: 80px; padding-left: 20px; padding-right: 20px; color: #eee; font-family: 'Inter', sans-serif; }
.header-row { margin-bottom: 30px; }
.back-link { background: none; border: none; color: #666; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: 0.2s; padding: 0; font-size: 14px; margin-bottom: 10px; }
.back-link:hover { color: #fff; }
.icon-arrow { width: 16px; transform: rotate(180deg); }
.page-title { color: #fff; font-size: 32px; font-weight: 700; margin: 0; }
.subtitle { color: #666; font-size: 14px; margin-top: 5px; }

.checkout-grid { display: grid; grid-template-columns: 1fr; gap: 40px; }
@media(min-width: 1024px) { .checkout-grid { grid-template-columns: 1.8fr 1fr; } }

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

.explorer-container { 
  max-height: 500px; overflow-y: auto; 
  padding: 4px; margin: -4px; 
}
.empty-msg { text-align: center; color: #555; padding: 40px; border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px; }

/* Period (Locked) */
.periods-locked-wrapper { position: relative; border-radius: 16px; overflow: hidden; }
.periods-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }

.disabled-state {
  opacity: 0.4; filter: grayscale(0.8); pointer-events: none;
}

.period-card { position: relative; padding: 16px; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px; cursor: pointer; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: 0.2s; }
.period-card.active { border-color: #3b82f6; background: rgba(59, 130, 246, 0.05); }
.period-val { font-weight: 500; font-size: 14px; color: #ddd; z-index: 2; }
.period-card.active .period-val { color: #fff; }
.discount-tag { position: absolute; top: 5px; right: 5px; background: #22c55e; color: #000; font-size: 10px; padding: 2px 6px; border-radius: 100px; font-weight: 700; z-index: 2; }

/* Locked Overlay */
.locked-overlay {
  position: absolute; inset: 0; z-index: 10;
  display: flex; align-items: center; justify-content: center;
  background: rgba(0, 0, 0, 0.2); backdrop-filter: blur(2px);
}
.status-badge {
  background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 8px 16px; border-radius: 50px; font-weight: 600; font-size: 13px; color: #fff;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

/* Glows */
.ambient-glow { position: absolute; left: 0; top: 0; bottom: 0; width: 80px; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
.ambient-glow.blue { background: radial-gradient(circle at left center, #3b82f6, transparent 70%); }
.ambient-glow.purple { background: radial-gradient(circle at top left, #a855f7, transparent 70%); width: 200px; height: 200px; }
.period-card:hover .ambient-glow { opacity: 0.15; }
.period-card.active .ambient-glow { opacity: 0.25; }
.summary-card .ambient-glow { opacity: 0.15; }

/* Summary */
.summary-card { position: relative; background: rgba(20, 20, 20, 0.6); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 24px; padding: 30px; overflow: hidden; }
.summary-header { display: flex; gap: 15px; align-items: center; margin-bottom: 25px; position: relative; z-index: 2; }
.flag-icon { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; }
.prod-name { font-size: 20px; font-weight: 700; margin: 0 0 4px 0; color: #fff; }
.prod-cat { font-size: 12px; color: #888; }
.price-section { margin-bottom: 25px; position: relative; z-index: 2; }
.price-val { font-size: 36px; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
.price-sub { color: #666; font-size: 14px; }
.divider { height: 1px; background: rgba(255,255,255,0.05); margin-bottom: 25px; }
.spec-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; position: relative; z-index: 2; }
.label { color: #666; }
.val { color: #ddd; font-weight: 500; font-family: monospace; }
.selected-core-box { margin-top: 20px; padding: 16px; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); position: relative; z-index: 2; }
.selected-core-box.has-core { border-color: rgba(59, 130, 246, 0.3); background: rgba(59, 130, 246, 0.05); }
.sc-label { font-size: 11px; color: #666; text-transform: uppercase; margin-bottom: 4px; }
.sc-val { color: #fff; font-weight: 600; font-size: 14px; }
.checkout-btn { width: 100%; padding: 16px; margin-top: 30px; background: #fff; color: #000; font-weight: 700; border-radius: 14px; border: none; cursor: pointer; transition: 0.2s; font-size: 15px; position: relative; z-index: 2; }
.checkout-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(255,255,255,0.1); }
.checkout-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.loading-wrapper { height: 60vh; display: flex; align-items: center; justify-content: center; }
.spinner { width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.1); border-top-color: #fff; border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #333; border-radius: 2px; }
.list-move, .list-enter-active, .list-leave-active { transition: all 0.4s ease; }
.list-leave-to { opacity: 0; transform: translateX(20px); }
.list-enter-from { opacity: 0; transform: translateX(-20px); }
.list-leave-active { position: absolute; width: 100%; z-index: -1; }
</style>