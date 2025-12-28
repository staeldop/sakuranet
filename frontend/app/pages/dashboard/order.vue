<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useApiFetch } from '~/composables/useApi' 

import IconGamepad from '~/assets/icons/gamepad.svg?component'
import IconCloud from '~/assets/icons/cloud.svg?component'
import IconServer from '~/assets/icons/server.svg?component'
import IconCpu from '~/assets/icons/cpu.svg?component'
import IconRam from '~/assets/icons/ram.svg?component'
import IconDisk from '~/assets/icons/disk.svg?component'
import IconSword from '~/assets/icons/sword.svg?component'
import IconCode from '~/assets/icons/code.svg?component'
import imgFlagRU from '~/assets/flags/ru.png'
import imgFlagDE from '~/assets/flags/de.png'
import imgFlagFI from '~/assets/flags/fi.png'
import IconBox from '~/assets/icons/box.svg?component'

definePageMeta({
  layout: 'dashboard'
})

const categoryIcons: Record<string, any> = {
  gaming: IconGamepad,
  virtual: IconCloud,
  dedicated: IconServer
}

const flagImages: Record<string, string> = {
  RU: imgFlagRU,
  DE: imgFlagDE,
  FI: imgFlagFI
}

const specIcons: Record<string, any> = {
  cpu: IconCpu,
  ram: IconRam,
  disk: IconDisk,
  code: IconCode
}

const attrLabels: Record<string, string> = {
  'CPU': 'Процессор',
  'vCPU': 'Процессор',
  'RAM': 'Оперативная память',
  'Disk': 'Память',
  'SSD': 'Память',
  'Port': 'Скорость сети',
  'Env': 'Защита от DDoS'
}

const activeCategory = ref('gaming')
const activeCountry = ref('RU')
const activeGameType = ref('gaming')

const setCategory = (cat: string) => {
  activeCategory.value = cat
  if (cat !== 'gaming') {
    activeCountry.value = ''
    activeGameType.value = ''
  } else {
    activeCountry.value = 'RU'
    activeGameType.value = 'gaming'
  }
}

const products = ref([]) 

onMounted(async () => {
  try {
    const { data } = await useApiFetch<any[]>('/api/products')
    if (data.value) {
      products.value = data.value
    }
  } catch (e) {
    console.error('Ошибка загрузки товаров:', e)
  }
})

const filteredProducts = computed(() => {
  return products.value.filter((product: any) => {
    if (product.category !== activeCategory.value) return false
    if (activeCategory.value === 'gaming') {
      if (activeCountry.value && product.country !== activeCountry.value) return false
      if (activeGameType.value && product.game_type !== activeGameType.value) return false
    }
    return true
  })
})

const animationKey = computed(() => {
  return `${activeCategory.value}-${activeCountry.value}-${activeGameType.value}`
})

const getCardIcon = (product: any) => {
  if (product.game_type === 'coding') return IconCode
  return categoryIcons[product.category]
}
</script>

<template>
  <div class="container-custom">
    
    <div class="mb-10">
      <h1 class="page-title">Аренда Серверных</h1>
      <p class="page-subtitle">Выберите тариф, и оплатите сервер для старта!</p>
    </div>

    <div class="flex flex-wrap items-center gap-12 mb-12">
      <button @click="setCategory('gaming')" class="nav-btn" :class="{ active: activeCategory === 'gaming' }">
        <IconGamepad class="nav-icon" />
        <span>Игровые серверы</span>
      </button>

      <button @click="setCategory('virtual')" class="nav-btn" :class="{ active: activeCategory === 'virtual' }">
        <IconCloud class="nav-icon" />
        <span>Виртуальные серверы</span>
      </button>

      <button @click="setCategory('dedicated')" class="nav-btn" :class="{ active: activeCategory === 'dedicated' }">
        <IconServer class="nav-icon" />
        <span>Выделенные сервера</span>
      </button>
    </div>

    <div v-if="activeCategory === 'gaming'" class="filter-bar">
      <div class="flex items-center gap-8">
        <button @click="activeCountry = 'RU'" class="filter-btn" :class="{ active: activeCountry === 'RU' }">
          <img :src="flagImages['RU']" alt="RU" class="flag-img" /> Россия
        </button>
        <button @click="activeCountry = 'DE'" class="filter-btn" :class="{ active: activeCountry === 'DE' }">
          <img :src="flagImages['DE']" alt="DE" class="flag-img" /> Германия
        </button>
        <button @click="activeCountry = 'FI'" class="filter-btn" :class="{ active: activeCountry === 'FI' }">
          <img :src="flagImages['FI']" alt="FI" class="flag-img" /> Финляндия
        </button>
      </div>

      <div class="divider"></div>

      <div class="flex items-center gap-8">
        <button @click="activeGameType = 'gaming'" class="filter-btn" :class="{ active: activeGameType === 'gaming' }">
          <IconSword class="type-icon" /> Игровой
        </button>
        <button @click="activeGameType = 'coding'" class="filter-btn" :class="{ active: activeGameType === 'coding' }">
          <IconCode class="type-icon" /> Coding
        </button>
      </div>
    </div>

    <Transition name="grid-diag" mode="out-in">
      <div :key="animationKey" class="products-grid">
        
        <div v-for="product in filteredProducts" :key="product.id" class="product-card-wrapper">
          <div class="product-card group">
            
            <div class="glow-purple-top"></div>
            <div class="glow-red-bottom"></div>

            <div v-if="product.country" class="flag-absolute">
               <img :src="flagImages[product.country]" class="flag-img-header" />
            </div>

            <div class="card-content">
              
              <div class="card-header flex flex-row items-center">
                 <div class="header-left flex flex-row items-center">
                   <div class="cat-icon-box flex-shrink-0">
                     <component :is="getCardIcon(product)" class="main-icon" />
                   </div>
                   <h3 class="product-title">{{ product.name }}</h3>
                 </div>
              </div>

              <div class="price-block">
                <span class="price">₽{{ Number(product.price).toFixed(2).replace('.', ',') }}</span> 
                <span class="period"> / мес.</span>
              </div>

              <div class="specs-list">
                <div v-for="(attr, idx) in product.specs" :key="idx" class="spec-item">
                   <div class="spec-icon-col flex-shrink-0">
                      <component :is="specIcons[attr.icon]" class="spec-svg" />
                   </div>
                   <div class="spec-text-col">
                     <div class="spec-label">{{ attrLabels[attr.key] || attr.key }}</div>
                     <div class="spec-val">{{ attr.value }}</div>
                   </div>
                </div>
              </div>

              <div class="mt-6">
                <NuxtLink :to="`/dashboard/checkout?id=${product.id}`" class="buy-btn">
                  Перейти к покупке
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>

        <div v-if="filteredProducts.length === 0" class="empty-state">
          <div class="empty-icon-wrapper">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="empty-svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M12.4856 1.12584C12.1836 0.958052 11.8164 0.958052 11.5144 1.12584L2.51436 6.12584L2.5073 6.13784L2.49287 6.13802C2.18749 6.3177 2 6.64568 2 7V16.9999C2 17.3631 2.19689 17.6977 2.51436 17.874L11.5022 22.8673C11.8059 23.0416 12.1791 23.0445 12.4856 22.8742L21.4856 17.8742C21.8031 17.6978 22 17.3632 22 17V7C22 6.64568 21.8125 6.31781 21.5071 6.13813C21.4996 6.13372 21.4921 6.12942 21.4845 6.12522L12.4856 1.12584ZM5.05923 6.99995L12.0001 10.856L14.4855 9.47519L7.74296 5.50898L5.05923 6.99995ZM16.5142 8.34816L18.9409 7L12 3.14396L9.77162 4.38195L16.5142 8.34816ZM4 16.4115V8.69951L11 12.5884V20.3004L4 16.4115ZM13 20.3005V12.5884L20 8.69951V16.4116L13 20.3005Z" fill="currentColor" />
            </svg>
          </div>
          <div class="empty-text">В этой категории пока нет товаров</div>
        </div>

      </div>
    </Transition>

  </div>
</template>

<style scoped>
.container-custom { width: 100%; max-width: 1100px; margin: 0; padding-bottom: 80px; padding-top: 0; }
.page-title { font-size: 32px; font-weight: 700; color: white; margin-top: 0; margin-bottom: 4px; line-height: 1.2; }
.page-subtitle { color: #737373; font-size: 13px; margin-top: 0; margin-bottom: 30px; }

.nav-btn { background: transparent; border: none; padding: 0; display: flex; align-items: center; gap: 10px; color: #737373; font-size: 16px; font-weight: 500; cursor: pointer; transition: color 0.3s ease; margin: 0; }
.nav-btn:hover, .nav-btn.active { color: #ffffff; }
.nav-icon { width: 20px; height: 20px; fill: none; stroke: #d4d4d4; color: #d4d4d4; stroke-width: 1.5; }

.filter-bar { display: flex; align-items: center; margin-bottom: 30px; animation: fadeIn 0.6s ease-out; }
.filter-btn { background: transparent; border: none; padding: 0; color: #525252; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: color 0.3s ease; margin: 0; }
.filter-btn:hover, .filter-btn.active { color: #ffffff; }
.flag-img { width: 18px; height: auto; border-radius: 2px; display: block; opacity: 0.4; transition: opacity 0.3s ease; }
.filter-btn.active .flag-img, .filter-btn:hover .flag-img { opacity: 1; }
.type-icon { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 1.5; opacity: 0.5; transition: opacity 0.4s; }
.filter-btn.active .type-icon { opacity: 1; }
.divider { width: 1px; height: 24px; background: #333; margin: 0 30px; display: none; }
@media (min-width: 768px) { .divider { display: block; } }

.products-grid { display: grid; grid-template-columns: 1fr; gap: 24px; position: relative; align-items: start; }
@media (min-width: 768px) { .products-grid { grid-template-columns: 1fr 1fr; } }
@media (min-width: 1024px) { .products-grid { grid-template-columns: 1fr 1fr 1fr; } }

.product-card { position: relative; background: #050505; border: 1px solid #1a1a1a; border-radius: 16px; padding: 24px; overflow: hidden; display: flex; flex-direction: column; transition: border-color 0.3s ease; }
.product-card:hover { border-color: #333; }
.glow-purple-top { position: absolute; top: -60px; right: -60px; width: 220px; height: 220px; background: radial-gradient(circle, rgba(168, 85, 247, 0.4) 0%, transparent 70%); filter: blur(50px); opacity: 0.6; transition: opacity 1.5s ease, transform 1.5s ease; z-index: 0; }
.product-card:hover .glow-purple-top { opacity: 0.9; transform: scale(1.2); }
.glow-red-bottom { position: absolute; bottom: -50px; right: -50px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(229, 59, 53, 0.25) 0%, transparent 70%); filter: blur(40px); opacity: 0.5; transition: opacity 1.5s ease, transform 1.5s ease; z-index: 0; }
.product-card:hover .glow-red-bottom { opacity: 0.8; transform: scale(1.2); }

.card-content { position: relative; z-index: 10; display: flex; flex-direction: column; }
.card-header { margin-bottom: 4px; width: 100%; }
.header-left { gap: 12px; width: 100%; }
.cat-icon-box { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: #fff; }
.main-icon { width: 32px; height: 32px; fill: none; stroke: currentColor; stroke-width: 1.5; }
.product-title { margin: 0; font-size: 20px; font-weight: 700; color: white; letter-spacing: 0.02em; text-transform: lowercase; line-height: 1; padding-top: 3px; }
.flag-absolute { position: absolute; top: 24px; right: 24px; z-index: 20; }
.flag-img-header { width: 40px; height: auto; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.3); }

.price-block { margin-bottom: 16px; display: flex; align-items: baseline; gap: 2px; }
.price { font-size: 24px; font-weight: 700; color: #a3a3a3; letter-spacing: -0.01em; }
.period { color: #737373; font-size: 14px; margin-left: 4px; }
.specs-list { display: flex; flex-direction: column; gap: 16px; }
.spec-item { display: flex; align-items: center; gap: 12px; }
.spec-icon-col { padding-top: 0; color: #e5e5e5; }
.spec-svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.5; }
.spec-text-col { display: flex; flex-direction: column; }
.spec-label { font-size: 11px; color: #737373; margin-bottom: 2px; }
.spec-val { font-size: 13px; font-weight: 500; color: #f5f5f5; line-height: 1.4; }
.mt-6 { margin-top: 18px; } 
.buy-btn { display: flex; align-items: center; justify-content: center; padding: 12px; background: transparent; border: 1px solid #262626; border-radius: 10px; color: #d4d4d4; font-size: 13px; font-weight: 500; text-decoration: none; transition: all 0.3s ease; white-space: nowrap; position: relative; z-index: 20; }
.buy-btn:hover { background: #111; border-color: #404040; color: white; }

/* === ПУСТОЕ СОСТОЯНИЕ === */
.empty-state {
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 0;
  text-align: center;
}
.empty-icon-wrapper {
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 84px;
  height: 84px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  color: #404040;
}
.empty-svg {
  width: 38px;
  height: 38px;
  opacity: 0.8;
}
.empty-text {
  font-size: 16px;
  font-weight: 600;
  color: #737373;
  margin-bottom: 6px;
}
.empty-subtext {
  font-size: 12px;
  color: #404040;
}

/* 🔥 ДИАГОНАЛЬНАЯ АНИМАЦИЯ */
.grid-diag-enter-active, .grid-diag-leave-active { transition: all 0.8s cubic-bezier(0.25, 0.8, 0.25, 1); }
.grid-diag-leave-to { opacity: 0; transform: translate(20px, 20px); }
.grid-diag-enter-from { opacity: 0; transform: translate(20px, 20px); }

@keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>