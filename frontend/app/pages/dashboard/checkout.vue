<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApiFetch } from '~/composables/useApi'

// Иконки
import IconBox from '~/assets/icons/box.svg?component'
import IconServer from '~/assets/icons/server.svg?component'
import IconCode from '~/assets/icons/code.svg?component'
import IconCpu from '~/assets/icons/cpu.svg?component'
import IconRam from '~/assets/icons/ram.svg?component'
import IconDisk from '~/assets/icons/disk.svg?component'
import IconShield from '~/assets/icons/sword.svg?component'
import IconArrow from '~/assets/icons/arrow-right.svg?component'

// Флаг
import imgFlagDE from '~/assets/flags/de.png'

definePageMeta({
  layout: 'dashboard'
})

const route = useRoute()
const router = useRouter()
const isLoading = ref(true)
const isSubmitting = ref(false)
const product = ref<any>(null)

// Данные формы
const serverName = ref('')
const selectedCore = ref('')
const selectedPeriod = ref(1)

// Данные для выбора (моки)
const coresMap: Record<string, { name: string, version: string, icon: any }[]> = {
  gaming: [
    { name: 'Minecraft', version: 'Vanilla 1.20.4', icon: IconBox },
    { name: 'Minecraft', version: 'Paper (Optimized)', icon: IconBox },
    { name: 'CS 2', version: 'Latest Stable', icon: IconServer },
    { name: 'Rust', version: 'Oxide Modded', icon: IconServer },
    { name: 'GTA V', version: 'FiveM', icon: IconServer },
  ],
  virtual: [
    { name: 'Ubuntu', version: '22.04 LTS', icon: IconCode },
    { name: 'Debian', version: '12 (Bookworm)', icon: IconCode },
    { name: 'Windows', version: 'Server 2022', icon: IconCode },
  ],
  dedicated: [
    { name: 'Proxmox', version: 'VE 8.1', icon: IconServer },
  ]
}

const periods = [
  { months: 1, label: '1 Месяц', discount: 0 },
  { months: 3, label: '3 Месяца', discount: 5 },
  { months: 6, label: '6 Месяцев', discount: 10 },
  { months: 12, label: '1 Год', discount: 20 },
]

// Загрузка данных
onMounted(async () => {
  const id = route.query.id
  if (!id) return router.push('/dashboard/order')

  try {
    // server: false предотвращает ошибку 500 при SSR
    const { data } = await useApiFetch<any[]>('/api/products', { server: false })
    
    if (data.value) {
      product.value = data.value.find((p: any) => p.id == id)
    }
    
    if (!product.value) throw new Error('Товар не найден')

    serverName.value = `Server-${Math.floor(Math.random() * 1000)}`
    
    const cores = coresMap[product.value.category] || coresMap['virtual']
    if (cores.length) selectedCore.value = getCoreKey(cores[0])

  } catch (e) {
    console.error(e)
    router.push('/dashboard/order')
  } finally {
    isLoading.value = false
  }
})

const getCoreKey = (core: any) => `${core.name} ${core.version}`

const availableCores = computed(() => {
  if (!product.value) return []
  return coresMap[product.value.category] || []
})

const totalPrice = computed(() => {
  if (!product.value) return 0
  const base = Number(product.value.price)
  const per = periods.find(p => p.months === selectedPeriod.value)
  return (base * selectedPeriod.value) * (1 - (per?.discount || 0) / 100)
})

const formatPrice = (val: number) => {
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(val)
}

const handleCheckout = async () => {
  if (!serverName.value.trim()) return alert('Введите имя сервера')
  isSubmitting.value = true
  
  try {
    const { error } = await useApiFetch('/api/services', {
      method: 'POST',
      body: {
        product_id: product.value.id,
        name: serverName.value,
        core: selectedCore.value,
        period: selectedPeriod.value
      }
    })

    if (error.value) throw error.value

    // Успех -> редирект на страницу услуг
    alert('Услуга успешно активирована!')
    router.push('/dashboard/services')
  } catch (e: any) {
    const msg = e.data?.message || 'Ошибка создания'
    alert(msg)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="container-custom">
    
    <div v-if="isLoading" class="loading-wrapper">
      <div class="spinner"></div>
    </div>

    <div v-else class="main-content-wrapper">
      
      <!-- HEADER -->
      <div class="header-section">
        <button @click="router.back()" class="back-link">
          <IconArrow class="icon-arrow" /> Назад к тарифам
        </button>
        <h1 class="page-title">Конфигурация</h1>
        <p class="page-subtitle">Настройте параметры вашего сервера.</p>
      </div>

      <div class="checkout-grid">
        
        <!-- ЛЕВАЯ КОЛОНКА -->
        <div class="left-column">
          
          <!-- 1. ИМЯ -->
          <div class="glass-card">
            <div class="card-header">
              <div class="icon-label">
                <svg class="icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                <h2>Название сервера</h2>
              </div>
            </div>
            <input v-model="serverName" type="text" class="custom-input" placeholder="Введите название">
          </div>

          <!-- 2. ЯДРО -->
          <div class="glass-card">
            <div class="card-header">
              <div class="icon-label">
                 <svg class="icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                 <h2>Выберите ядро</h2>
              </div>
            </div>
            <div class="cores-grid custom-scrollbar">
              <div 
                v-for="(core, idx) in availableCores" :key="idx"
                @click="selectedCore = getCoreKey(core)"
                class="core-card" :class="{ active: selectedCore === getCoreKey(core) }"
              >
                <div class="core-info">
                  <div class="core-icon-box" :class="{ active: selectedCore === getCoreKey(core) }">
                    <component :is="core.icon" class="core-svg" />
                  </div>
                  <div class="core-text">
                    <span class="core-name">{{ core.name }}</span>
                    <span class="core-version">{{ core.version }}</span>
                  </div>
                </div>
                <div class="radio-circle" :class="{ active: selectedCore === getCoreKey(core) }">
                   <div v-if="selectedCore === getCoreKey(core)" class="radio-dot"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. ПЕРИОД -->
          <div class="glass-card">
            <div class="card-header">
              <div class="icon-label">
                 <svg class="icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                 <h2>Период оплаты</h2>
              </div>
            </div>
            <div class="periods-grid">
              <button 
                v-for="per in periods" :key="per.months"
                @click="selectedPeriod = per.months"
                class="period-btn" :class="{ active: selectedPeriod === per.months }"
              >
                <span class="period-label">{{ per.label }}</span>
                <span v-if="per.discount > 0" class="discount-badge">-{{ per.discount }}%</span>
              </button>
            </div>
          </div>
        </div>

        <!-- ПРАВАЯ КОЛОНКА -->
        <div class="right-column">
          <div class="summary-wrapper">
            <div class="glass-card summary-card">
              
              <div class="glow-effect"></div>
              <div class="flag-position"><img :src="imgFlagDE" class="flag-img" /></div>

              <div class="summary-content">
                <div class="summary-header">
                  <IconBox class="product-icon" />
                  <h3>{{ product.name }}</h3>
                </div>

                <div class="price-block">
                  <p class="price-value">
                    {{ formatPrice(totalPrice) }}
                    <span class="price-period">/ мес.</span>
                  </p>
                </div>

                <div class="specs-list">
                  <div class="spec-item">
                    <div class="spec-icon-box"><IconCpu class="spec-icon" /></div>
                    <div class="spec-details">
                      <span class="spec-label">Процессор</span>
                      <span class="spec-value">{{ product.specs?.find((s:any) => s.key === 'CPU')?.value || '1 vCore' }}</span>
                    </div>
                  </div>
                  <div class="spec-item">
                    <div class="spec-icon-box"><IconRam class="spec-icon" /></div>
                    <div class="spec-details">
                      <span class="spec-label">Оперативная память</span>
                      <span class="spec-value">{{ product.specs?.find((s:any) => s.key === 'RAM')?.value || '2 GB' }}</span>
                    </div>
                  </div>
                  <div class="spec-item">
                    <div class="spec-icon-box"><IconDisk class="spec-icon" /></div>
                    <div class="spec-details">
                      <span class="spec-label">Память</span>
                      <span class="spec-value">{{ product.specs?.find((s:any) => s.key === 'Disk')?.value || '20 GB NVMe' }}</span>
                    </div>
                  </div>
                  <div class="spec-item">
                    <div class="spec-icon-box"><IconShield class="spec-icon" /></div>
                    <div class="spec-details">
                      <span class="spec-label">Защита от DDoS</span>
                      <span class="spec-value">Начальная</span>
                    </div>
                  </div>
                </div>

                <button @click="handleCheckout" :disabled="isSubmitting" class="checkout-btn">
                  <span v-if="!isSubmitting">Перейти к покупке</span>
                  <span v-else>Обработка...</span>
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
.container-custom { width: 100%; max-width: 1350px; margin: 0; padding-bottom: 100px; color: #f5f5f5; }

.header-section { margin-bottom: 3rem; }
.back-link { background: none; border: none; color: #737373; font-size: 15px; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 8px; margin-bottom: 16px; transition: color 0.2s; }
.back-link:hover { color: white; }
.icon-arrow { width: 18px; height: 18px; transform: rotate(180deg); }
.page-title { font-size: 32px; font-weight: 800; color: white; margin: 0 0 6px 0; line-height: 1.1; }
.page-subtitle { color: #888; font-size: 15px; margin: 0; }

.checkout-grid { display: grid; grid-template-columns: 1fr; gap: 2.5rem; }
@media (min-width: 1024px) { .checkout-grid { grid-template-columns: 1.8fr 1fr; gap: 60px; } }

.glass-card { background: rgba(23, 23, 23, 0.5); border: 1px solid #262626; border-radius: 16px; padding: 32px; margin-bottom: 30px; transition: border-color 0.3s ease; }
.glass-card:hover { border-color: #404040; }

.card-header { margin-bottom: 20px; }
.icon-label { display: flex; align-items: center; gap: 10px; }
.icon-svg { width: 24px; height: 24px; color: #a3a3a3; }
.icon-label h2 { font-size: 20px; font-weight: 700; color: white; margin: 0; }

.custom-input { width: 100%; background: rgba(38, 38, 38, 0.5); border: 1px solid #404040; border-radius: 12px; padding: 16px 20px; color: white; font-size: 16px; outline: none; transition: all 0.2s; box-sizing: border-box; }
.custom-input:focus { border-color: #737373; background: rgba(38, 38, 38, 0.8); }

.cores-grid { display: grid; grid-template-columns: 1fr; gap: 16px; max-height: 420px; overflow-y: auto; padding-right: 6px; }
@media (min-width: 640px) { .cores-grid { grid-template-columns: 1fr 1fr; } }
.core-card { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-radius: 12px; border: 1px solid #404040; background: rgba(38, 38, 38, 0.3); cursor: pointer; transition: all 0.2s; }
.core-card:hover { background: rgba(38, 38, 38, 0.6); border-color: #525252; }
.core-card.active { background: #262626; border-color: #737373; box-shadow: 0 0 20px rgba(255, 255, 255, 0.03); }
.core-info { display: flex; align-items: center; gap: 16px; }
.core-icon-box { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(64, 64, 64, 0.3); color: #d4d4d4; transition: background 0.2s; }
.core-icon-box.active { background: rgba(255, 255, 255, 0.1); color: white; }
.core-svg { width: 28px; height: 28px; }
.core-text { display: flex; flex-direction: column; gap: 2px; }
.core-name { font-size: 16px; font-weight: 600; color: white; line-height: 1.2; }
.core-version { font-size: 13px; color: #a3a3a3; }
.radio-circle { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #525252; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.core-card:hover .radio-circle { border-color: #737373; }
.radio-circle.active { border-color: white; background: white; }
.radio-dot { width: 10px; height: 10px; background: black; border-radius: 50%; }

.periods-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
@media (min-width: 640px) { .periods-grid { grid-template-columns: repeat(4, 1fr); } }
.period-btn { position: relative; padding: 16px; border-radius: 12px; border: 1px solid #404040; background: rgba(38, 38, 38, 0.3); color: #a3a3a3; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: all 0.2s; }
.period-btn:hover { border-color: #525252; color: #d4d4d4; }
.period-btn.active { background: #262626; border-color: #737373; color: white; }
.period-label { font-size: 15px; font-weight: 600; }
.discount-badge { position: absolute; top: -10px; right: -10px; font-size: 11px; font-weight: 700; background: white; color: black; padding: 3px 8px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }

.summary-wrapper { position: sticky; top: 20px; }
.summary-card { position: relative; overflow: hidden; backdrop-filter: blur(10px); padding: 40px; }
.glow-effect { position: absolute; top: -50px; right: -50px; width: 250px; height: 250px; background: radial-gradient(circle, rgba(99, 102, 241, 0.15), rgba(168, 85, 247, 0.15), rgba(236, 72, 153, 0.15)); border-radius: 50%; filter: blur(70px); pointer-events: none; z-index: 0; }
.flag-position { position: absolute; top: 32px; right: 32px; z-index: 20; }
.flag-img { width: 60px; height: auto; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.4); }
.summary-content { position: relative; z-index: 10; }
.summary-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-right: 70px; }
.product-icon { width: 36px; height: 36px; color: white; }
.summary-header h3 { font-size: 24px; font-weight: 700; color: white; margin: 0; line-height: 1.1; }
.price-value { font-size: 40px; font-weight: 700; color: white; margin: 0 0 32px 0; letter-spacing: -0.03em; }
.price-period { font-size: 18px; font-weight: 400; color: #888; letter-spacing: 0; }
.specs-list { display: flex; flex-direction: column; gap: 20px; margin-bottom: 40px; }
.spec-item { display: flex; align-items: center; gap: 16px; }
.spec-icon-box { width: 28px; display: flex; justify-content: center; }
.spec-icon { width: 24px; height: 24px; color: #525252; flex-shrink: 0; }
.spec-details { flex-grow: 1; display: flex; flex-direction: column; }
.spec-label { font-size: 13px; color: #737373; margin-bottom: 2px; line-height: 1; }
.spec-value { font-size: 16px; font-weight: 500; color: white; line-height: 1.2; }
.checkout-btn { width: 100%; background: transparent; border: 2px solid white; color: white; font-weight: 600; padding: 18px; border-radius: 10px; font-size: 18px; cursor: pointer; transition: all 0.3s ease; }
.checkout-btn:hover:not(:disabled) { background: white; color: black; }
.checkout-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.loading-wrapper { height: 60vh; display: flex; align-items: center; justify-content: center; }
.spinner { width: 50px; height: 50px; border: 3px solid #525252; border-top-color: white; border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(64, 64, 64, 0.2); border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(115, 115, 115, 0.4); border-radius: 3px; }
</style>