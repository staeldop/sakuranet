<script setup lang="ts">
import { ref, computed } from 'vue'

// --- 1. ИМПОРТЫ ---
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

definePageMeta({
  layout: 'dashboard'
})

// --- 2. СЛОВАРИ ---
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

// --- СОСТОЯНИЕ ---
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

// --- ДАННЫЕ ---
const products = ref([
  {
    id: 1,
    name: 'coding start',
    category: 'gaming',
    country: 'DE', 
    gameType: 'coding',
    price: 69.00,
    attributes: [
      { key: 'CPU', value: '1 vCore', icon: 'cpu' },
      { key: 'RAM', value: '1 ГБ', icon: 'ram' },
      { key: 'Disk', value: '5 ГБ NVMe', icon: 'disk' },
      { key: 'Env', value: 'Начальная', icon: 'code' }
    ]
  },
  {
    id: 2,
    name: 'minecraft start',
    category: 'gaming',
    country: 'RU',
    gameType: 'gaming',
    price: 499,
    attributes: [
      { key: 'CPU', value: 'Ryzen 5 3600', icon: 'cpu' },
      { key: 'RAM', value: '8 GB DDR4', icon: 'ram' },
      { key: 'Disk', value: '50 GB NVMe', icon: 'disk' },
    ]
  },
  {
    id: 3,
    name: 'minecraft pro',
    category: 'gaming',
    country: 'DE',
    gameType: 'gaming',
    price: 1200,
    attributes: [
      { key: 'CPU', value: 'Ryzen 9 7950X', icon: 'cpu' },
      { key: 'RAM', value: '16 GB DDR5', icon: 'ram' },
      { key: 'Disk', value: '100 GB NVMe', icon: 'disk' },
    ]
  },
  {
    id: 4,
    name: 'hi-load-1',
    category: 'virtual',
    country: 'FI',
    gameType: null,
    price: 450,
    attributes: [
      { key: 'vCPU', value: '1 vCore', icon: 'cpu' },
      { key: 'RAM', value: '2 GB DDR5', icon: 'ram' },
      { key: 'SSD', value: '80 GB NVMe', icon: 'disk' },
    ]
  },
  {
    id: 5,
    name: 'dedicated i9',
    category: 'dedicated',
    country: 'RU',
    gameType: null,
    price: 15000,
    attributes: [
      { key: 'CPU', value: 'Core i9-13900K', icon: 'cpu' },
      { key: 'RAM', value: '64 GB DDR5', icon: 'ram' },
      { key: 'Port', value: '10 Gbit/s', icon: 'disk' },
    ]
  }
])

const filteredProducts = computed(() => {
  return products.value.filter(product => {
    if (product.category !== activeCategory.value) return false
    if (activeCategory.value === 'gaming') {
      if (activeCountry.value && product.country !== activeCountry.value) return false
      if (activeGameType.value && product.gameType !== activeGameType.value) return false
    }
    return true
  })
})

const animationKey = computed(() => {
  return `${activeCategory.value}-${activeCountry.value}-${activeGameType.value}`
})

const getCardIcon = (product: any) => {
  if (product.gameType === 'coding') return IconCode
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
                <span class="price">₽{{ product.price.toFixed(2).replace('.', ',') }}</span> 
                <span class="period"> / мес.</span>
              </div>

              <div class="specs-list">
                <div v-for="(attr, idx) in product.attributes" :key="idx" class="spec-item">
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

        <div v-if="filteredProducts.length === 0" class="col-span-full text-center py-20 text-neutral-500">
          В этой категории пока нет товаров
        </div>

      </div>
    </Transition>

  </div>
</template>

<style scoped>
/* ================================================= */
/* ФИНАЛЬНАЯ МИКРО-КОРРЕКТИРОВКА МАСШТАБА */
/* ================================================= */

.container-custom { width: 100%; max-width: 1100px; /* Вернули к исходному */ margin: 0; padding-bottom: 80px; padding-top: 0; }
/* Вернули к исходному */
.page-title { font-size: 24px; font-weight: 700; color: white; margin-top: 0; margin-bottom: 4px; line-height: 1.2; }
/* Скорректировано */
.page-subtitle { color: #737373; font-size: 13px; margin-top: 0; margin-bottom: 30px; }

/* НАВИГАЦИЯ */
/* Скорректировано */
.nav-btn { background: transparent; border: none; padding: 0; display: flex; align-items: center; gap: 10px; color: #737373; font-size: 16px; font-weight: 500; cursor: pointer; transition: color 0.3s ease; margin: 0; }
.nav-btn:hover, .nav-btn.active { color: #ffffff; }
/* ИЗМЕНЕНИЕ: Сделали иконку СВЕТЛО-СЕРОЙ (#d4d4d4) */
.nav-icon { 
  width: 20px; 
  height: 20px; 
  fill: none; 
  stroke: #d4d4d4; /* СВЕТЛО-СЕРЫЙ */
  color: #d4d4d4; /* СВЕТЛО-СЕРЫЙ */
  stroke-width: 1.5; 
}

/* ФИЛЬТРЫ */
.filter-bar { display: flex; align-items: center; margin-bottom: 30px; /* Скорректировано */ animation: fadeIn 0.6s ease-out; }
/* Вернули к исходному */
.filter-btn { background: transparent; border: none; padding: 0; color: #525252; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: color 0.3s ease; margin: 0; }
.filter-btn:hover, .filter-btn.active { color: #ffffff; }

/* ФЛАГ */
.flag-img { 
  width: 18px; 
  height: auto; 
  border-radius: 2px; 
  display: block; 
  opacity: 0.4; 
  transition: opacity 0.3s ease; 
}
/* ФИКС: Делаем флаг ярким (opacity: 1) без тени при активности */
.filter-btn.active .flag-img { 
  opacity: 1; 
}
/* Сохраняем осветление при наведении */
.filter-btn:hover .flag-img { opacity: 1; }

/* Остальные фильтры */
.type-icon { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 1.5; opacity: 0.5; transition: opacity 0.4s; }
.filter-btn.active .type-icon { opacity: 1; }
.divider { width: 1px; height: 24px; background: #333; margin: 0 30px; display: none; }
@media (min-width: 768px) { .divider { display: block; } }

/* СЕТКА */
.products-grid { 
  display: grid; 
  grid-template-columns: 1fr; 
  gap: 24px; /* Вернули к исходному */
  position: relative; 
  align-items: start;
}
@media (min-width: 768px) { .products-grid { grid-template-columns: 1fr 1fr; } }
@media (min-width: 1024px) { .products-grid { grid-template-columns: 1fr 1fr 1fr; } }

.product-card-wrapper { } 

.product-card {
  position: relative; background: #050505; border: 1px solid #1a1a1a; border-radius: 16px; /* Вернули к исходному */
  padding: 24px; /* Вернули к исходному */
  overflow: hidden; 
  display: flex; flex-direction: column; 
  transition: border-color 0.3s ease;
}
.product-card:hover { border-color: #333; }

/* ИЗМЕНЕНИЕ: УВЕЛИЧЕННАЯ ЯРКОСТЬ СВЕЧЕНИЯ */
.glow-purple-top { 
  position: absolute; 
  top: -60px; right: -60px; 
  width: 220px; height: 220px; 
  /* Увеличен цвет градиента до 0.4 */
  background: radial-gradient(circle, rgba(168, 85, 247, 0.4) 0%, transparent 70%); 
  filter: blur(50px); 
  opacity: 0.6; /* Увеличена базовая непрозрачность */
  transition: opacity 1.5s ease, transform 1.5s ease; 
  z-index: 0; 
}
.product-card:hover .glow-purple-top { opacity: 0.9; transform: scale(1.2); /* Увеличена непрозрачность при наведении */ }

.glow-red-bottom { 
  position: absolute; 
  bottom: -50px; right: -50px; 
  width: 200px; height: 200px; 
  /* Увеличен цвет градиента до 0.25 */
  background: radial-gradient(circle, rgba(229, 59, 53, 0.25) 0%, transparent 70%); 
  filter: blur(40px); 
  opacity: 0.5; /* Увеличена базовая непрозрачность */
  transition: opacity 1.5s ease, transform 1.5s ease; 
  z-index: 0; 
}
.product-card:hover .glow-red-bottom { opacity: 0.8; transform: scale(1.2); /* Увеличена непрозрачность при наведении */ }

.card-content { position: relative; z-index: 10; display: flex; flex-direction: column; }

/* ХЕДЕР */
.card-header { margin-bottom: 4px; /* Вернули к исходному */ width: 100%; }
.header-left { gap: 12px; /* Вернули к исходному */ width: 100%; }
/* Вернули к исходному */
.cat-icon-box { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: #fff; }
/* Вернули к исходному */
.main-icon { width: 32px; height: 32px; fill: none; stroke: currentColor; stroke-width: 1.5; }
/* Вернули к исходному */
.product-title { margin: 0; font-size: 20px; font-weight: 700; color: white; letter-spacing: 0.02em; text-transform: lowercase; line-height: 1; padding-top: 3px; }

/* ФЛАГ */
/* Скорректировано */
.flag-absolute { position: absolute; top: 24px; right: 24px; z-index: 20; }
/* Скорректировано */
.flag-img-header { width: 40px; height: auto; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.3); }

/* ЦЕНА */
.price-block { margin-bottom: 16px; /* Вернули к исходному */ display: flex; align-items: baseline; gap: 2px; }
/* Скорректировано */
.price { font-size: 24px; font-weight: 700; color: #a3a3a3; letter-spacing: -0.01em; }
/* Вернули к исходному */
.period { color: #737373; font-size: 14px; margin-left: 4px; }

/* СПИСОК ХАРАКТЕРИСТИК */
.specs-list { 
  display: flex; flex-direction: column; gap: 16px; /* Вернули к исходному */
}
.spec-item { display: flex; align-items: center; gap: 12px; } /* Вернули к исходному */
.spec-icon-col { padding-top: 0; color: #e5e5e5; }
/* Вернули к исходному */
.spec-svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.5; }
.spec-text-col { display: flex; flex-direction: column; }
/* Вернули к исходному */
.spec-label { font-size: 11px; color: #737373; margin-bottom: 2px; }
/* Вернули к исходному */
.spec-val { font-size: 13px; font-weight: 500; color: #f5f5f5; line-height: 1.4; }

/* КНОПКА */
.mt-6 { margin-top: 18px; /* Скорректировано */ } 

.buy-btn {
  display: flex; align-items: center; justify-content: center; 
  padding: 12px; /* Вернули к исходному */
  background: transparent; border: 1px solid #262626; border-radius: 10px; /* Вернули к исходному */
  color: #d4d4d4; font-size: 13px; font-weight: 500; /* Вернули к исходному */
  text-decoration: none; 
  transition: all 0.3s ease;
  white-space: nowrap; 
  position: relative; z-index: 20;
}
.buy-btn:hover { background: #111; border-color: #404040; color: white; }

/* АНИМАЦИЯ */
.grid-diag-enter-active, .grid-diag-leave-active { transition: all 0.8s cubic-bezier(0.25, 0.8, 0.25, 1); }
.grid-diag-leave-to { opacity: 0; transform: translate(20px, 20px); }
.grid-diag-enter-from { opacity: 0; transform: translate(20px, 20px); }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
</style>