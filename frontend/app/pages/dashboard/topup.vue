<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth' // <-- Импортируем стор

// ИМПОРТ ИКОНОК
import IconWallet from '~/assets/icons/wallet.svg?component'
import IconTicket from '~/assets/icons/ticket.svg?component'
import IconCard from '~/assets/icons/credit-card.svg?component'
import IconArrow from '~/assets/icons/arrow-right.svg?component'
import IconCart from '~/assets/icons/cart.svg?component'

definePageMeta({
  layout: 'dashboard'
})

// --- ПОДКЛЮЧАЕМ PINIA ---
const auth = useAuthStore()

// Загружаем свежие данные при входе на страницу
onMounted(() => {
  auth.fetchUser()
})

// --- ЛОГИКА ---
const amount = ref<number | string>('')
const promoCode = ref('')
const voucherCode = ref('')
const showVoucherInput = ref(false)
const showDiscordAlert = ref(false)

// --- ДАННЫЕ ТРАНЗАКЦИЙ ---
const transactions = [
  { id: 1, title: 'Пополнение баланса', date: '20.11.2025', amount: '+500 ₽', status: 'success' },
  { id: 2, title: 'Покупка: Minecraft Server', date: '18.11.2025', amount: '-1200 ₽', status: 'paid' },
  { id: 3, title: 'Продление: VDS Start', date: '01.11.2025', amount: '-450 ₽', status: 'paid' },
]

// --- ЛОГИКА 3D HUD ---
const cardRef = ref<HTMLElement | null>(null)
const mouseX = ref(0)
const mouseY = ref(0)

const handleMouseMove = (e: MouseEvent) => {
  if (!cardRef.value) return
  const rect = cardRef.value.getBoundingClientRect()
  mouseX.value = e.clientX - rect.left
  mouseY.value = e.clientY - rect.top
}

const glowStyle = computed(() => ({
  background: `radial-gradient(
    800px circle at ${mouseX.value}px ${mouseY.value}px, 
    rgba(255, 255, 255, 0.07), 
    transparent 40%
  )`
}))

const tiltStyle = computed(() => {
  if (!cardRef.value) return {}
  const rect = cardRef.value?.getBoundingClientRect() || { width: 0, height: 0 }
  const cx = rect.width / 2
  const cy = rect.height / 2
  const rx = (mouseY.value - cy) / 30 
  const ry = (cx - mouseX.value) / 30
  return {
    transform: `perspective(1000px) rotateX(${rx}deg) rotateY(${ry}deg)`
  }
})

const applyVoucher = () => {
  alert(`Активация: ${voucherCode.value}`)
  showVoucherInput.value = false
}
</script>

<template>
  <div class="finance-page">

    <div class="page-header">
      <h1>Финансы</h1>
      <p>Управление счетом и транзакции</p>
    </div>

    <div class="grid-layout">

      <!-- === ЛЕВАЯ КОЛОНКА === -->
      <div class="left-col">
        
        <!-- 3D HUD КАРТОЧКА -->
        <div 
          class="cyber-card"
          ref="cardRef"
          @mousemove="handleMouseMove"
          :style="tiltStyle"
        >
          <div class="noise-overlay"></div>
          <div class="glow-layer" :style="glowStyle"></div>
          
          <div class="cyber-decor-top">
            <div class="sim-chip">
              <span></span><span></span><span></span><span></span>
            </div>
            <span class="card-label">SAKURANET // WALLET</span>
          </div>

          <div class="balance-wrapper">
            <span class="currency-symbol">RUB</span>
            <!-- 👇 ЗДЕСЬ ТЕПЕРЬ РЕАЛЬНЫЙ БАЛАНС ИЗ PINIA 👇 -->
            <span class="balance-value">
               {{ auth.formattedBalance }}
            </span>
          </div>

          <div class="cyber-decor-bottom">
            <div class="status-indicator">
              <div class="status-dot"></div>
              <span class="status-text">ENCRYPTED CONNECTION</span>
            </div>
            <div class="barcode">|| | ||| ||</div>
          </div>
        </div>
        
        <!-- ... ВАУЧЕР (оставь код без изменений, он ниже) ... -->
        <div class="voucher-wrapper">
          <button 
            @click="showVoucherInput = !showVoucherInput" 
            class="voucher-toggle-btn"
            :class="{ active: showVoucherInput }"
          >
            <div class="btn-content">
              <IconTicket class="icon-svg" /> 
              <span>Активировать ваучер</span>
            </div>
            <span class="arrow" :class="{ rotated: showVoucherInput }">▼</span>
          </button>

          <Transition name="expand">
            <div v-if="showVoucherInput" class="voucher-dropdown-wrapper">
              <div class="voucher-dropdown">
                <form @submit.prevent="applyVoucher">
                  <label>Код доступа</label>
                  <div class="input-row">
                    <input 
                      v-model="voucherCode" 
                      type="text" 
                      placeholder="XXXX-XXXX-XXXX" 
                      class="glass-input"
                    >
                    <button type="submit" class="btn-voucher-submit">
                      <IconArrow class="icon-svg" />
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </Transition>
        </div>

      </div>

      <!-- ... ПРАВАЯ КОЛОНКА (код без изменений) ... -->
      <div class="right-col">
        <div class="glass-panel">
          <h2 class="panel-title">Пополнение</h2>
          
          <form @submit.prevent="showDiscordAlert = true" class="payment-form">
            <div class="form-group">
              <label>
                <IconWallet class="icon-svg text-gray" /> 
                Сумма платежа
              </label>
              <input 
                v-model="amount" 
                type="number" 
                placeholder="0" 
                class="glass-input big-text no-spinners"
              >
            </div>

            <div class="form-group">
              <label>
                <IconTicket class="icon-svg text-gray" /> 
                Промокод
              </label>
              <input 
                v-model="promoCode" 
                type="text" 
                placeholder="Опционально" 
                class="glass-input"
              >
            </div>

            <div class="action-grid">
              <div class="method-display">
                <span class="label">Метод</span>
                <div class="value">
                  <IconCard class="icon-svg" />
                  Карты РФ
                </div>
              </div>
              <button type="submit" class="btn-pay-outline">
                <span>Перейти к оплате</span>
                <IconCart class="icon-svg" />
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
    
    <!-- ИСТОРИЯ ТРАНЗАКЦИЙ (код без изменений) -->
    <div class="history-section">
      <h2 class="section-title small">Последние операции</h2>
      <div class="history-list">
        <div v-for="tx in transactions" :key="tx.id" class="history-item">
           <div class="tx-icon" :class="tx.status">
            <IconArrow v-if="tx.status === 'success'" class="icon-svg rotate-down" /> 
            <IconWallet v-else class="icon-svg" /> 
          </div>
           <div class="tx-info">
            <div class="tx-title">{{ tx.title }}</div>
            <div class="tx-date">{{ tx.date }}</div>
          </div>
          <div class="tx-amount" :class="{ positive: tx.status === 'success' }">
            {{ tx.amount }}
          </div>
        </div>
      </div>
    </div>

    <!-- МОДАЛКА (код без изменений) -->
    <Transition name="fade">
      <div v-if="showDiscordAlert" class="modal-overlay" @click.self="showDiscordAlert = false">
        <div class="modal-content">
          <h3>Технические работы</h3>
          <p>Платежный шлюз обновляется. Пожалуйста, создайте тикет в Discord.</p>
          <a href="https://discord.gg/zdpbKZ55w4" target="_blank" class="btn-discord">
            Перейти в Discord
          </a>
        </div>
      </div>
    </Transition>

  </div>
</template>

<!-- СТИЛИ ОСТАЮТСЯ ТЕМИ ЖЕ, НЕ ТРОГАЙ ИХ -->
<style scoped>
/* ... весь CSS, который мы писали в прошлом шаге ... */
/* --- ОСНОВА --- */
.finance-page {
  width: 100%;
  max-width: 1100px;
  margin: 0;
  padding-bottom: 80px;
}

.page-header { margin-bottom: 30px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: white; margin: 0; }
.page-header p { color: #666; font-size: 13px; margin-top: 4px; }

.grid-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 120px; /* Твой выбор */
  margin-bottom: 50px;
}

@media (min-width: 1024px) {
  .grid-layout { 
    grid-template-columns: 0.9fr 1.1fr; 
  } 
}

/* SVG */
.icon-svg { width: 20px; height: 20px; min-width: 20px; display: inline-block; fill: none; stroke: currentColor; stroke-width: 2; }
.icon-svg.small { width: 16px; height: 16px; min-width: 16px; }
.icon-svg.text-gray { color: #666; margin-right: 4px; }
.rotate-down { transform: rotate(45deg); }

/* КАРТОЧКА HUD */
.cyber-card {
  position: relative; width: 100%; height: 210px; border-radius: 20px;
  background: #050505; border: 1px solid #1a1a1a;
  overflow: hidden; display: flex; flex-direction: column; justify-content: space-between;
  padding: 25px; margin-bottom: 20px; transition: border-color 0.3s;
}
.cyber-card:hover { border-color: #22c55e; }
.noise-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.05; pointer-events: none; z-index: 0; background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E"); }
.glow-layer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; mix-blend-mode: overlay; }
.cyber-decor-top { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; }
.card-label { font-size: 10px; color: #555; letter-spacing: 2px; font-weight: 700; font-family: monospace; }
.sim-chip { width: 34px; height: 24px; background: linear-gradient(135deg, #d4af37 0%, #f9e69d 50%, #b88a00 100%); border-radius: 4px; position: relative; overflow: hidden; opacity: 0.8; }
.sim-chip span { position: absolute; background: rgba(0,0,0,0.2); }
.sim-chip span:nth-child(1) { width: 1px; height: 100%; left: 33%; top: 0; }
.sim-chip span:nth-child(2) { width: 1px; height: 100%; left: 66%; top: 0; }
.sim-chip span:nth-child(3) { width: 100%; height: 1px; left: 0; top: 33%; }
.sim-chip span:nth-child(4) { width: 100%; height: 1px; left: 0; top: 66%; }
.balance-wrapper { position: relative; z-index: 2; text-align: center; margin-top: 10px; }
.currency-symbol { font-size: 11px; color: #22c55e; font-weight: bold; letter-spacing: 2px; margin-bottom: 4px; display: block; }
.balance-value { font-size: 48px; font-weight: 800; color: white; letter-spacing: 1px; background: linear-gradient(180deg, #fff 0%, #aaa 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.cyber-decor-bottom { position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; }
.status-indicator { display: flex; align-items: center; gap: 8px; }
.status-dot { width: 6px; height: 6px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 8px #22c55e; animation: pulse 2s infinite; }
.status-text { font-size: 9px; color: #22c55e; opacity: 0.8; letter-spacing: 1px; }
.barcode { font-family: monospace; font-size: 14px; color: #333; letter-spacing: 3px; }
@keyframes pulse { 0% { opacity: 0.5; } 50% { opacity: 1; } 100% { opacity: 0.5; } }

/* --- ВАУЧЕР (FIXED) --- */
.voucher-toggle-btn {
  width: 100%; display: flex; align-items: center; justify-content: space-between;
  padding: 14px 18px; background: rgba(255,255,255,0.02); border: 1px solid #222;
  border-radius: 14px; color: #888; cursor: pointer; transition: 0.2s;
}
.voucher-toggle-btn:hover { background: rgba(255,255,255,0.05); border-color: #444; color: white; }
.btn-content { display: flex; align-items: center; gap: 10px; font-size: 14px; }
.arrow { font-size: 10px; transition: 0.3s; }
.arrow.rotated { transform: rotate(180deg); }

.voucher-dropdown-wrapper { overflow: hidden; } /* Для анимации */
.voucher-dropdown { margin-top: 8px; background: #0a0a0a; border: 1px solid #222; padding: 15px; border-radius: 14px; }
.input-row { display: flex; gap: 8px; }

/* НОВАЯ КНОПКА ВАУЧЕРА */
.btn-voucher-submit {
  width: 44px; flex-shrink: 0;
  background: transparent; border: 1px solid #22c55e; border-radius: 10px;
  color: #22c55e; cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: 0.2s;
}
.btn-voucher-submit:hover { background: #22c55e; color: black; }

/* --- АНИМАЦИЯ РАСКРЫТИЯ ВАУЧЕРА (ПЛАВНАЯ) --- */
.expand-enter-active,
.expand-leave-active {
  transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
  max-height: 200px; /* Должно быть больше реальной высоты контента */
  opacity: 1;
}
.expand-enter-from,
.expand-leave-to {
  max-height: 0;
  opacity: 0;
  margin-top: 0;
  transform: translateY(-10px);
}

/* --- ПРАВАЯ КОЛОНКА (FIXED) --- */
.glass-panel { background: rgba(255, 255, 255, 0.01); border: 1px solid #222; padding: 30px; border-radius: 24px; }
.panel-title { font-size: 18px; margin: 0 0 25px 0; color: white; font-weight: 600; }
.form-group { margin-bottom: 20px; }
.form-group label { display: flex; align-items: center; gap: 8px; color: #666; font-size: 12px; margin-bottom: 8px; font-weight: 600; }

/* ИСПРАВЛЕНИЕ ВЫЛЕЗАНИЯ ЗА ГРАНИЦЫ */
.glass-input {
  width: 100%; 
  box-sizing: border-box; /* ВАЖНО! */
  background: #080808; border: 1px solid #222; 
  padding: 12px 16px; border-radius: 12px; 
  color: white; font-size: 14px; transition: 0.2s; outline: none;
}
.glass-input.big-text { font-size: 18px; font-weight: bold; }
.glass-input:focus { border-color: #444; background: #0f0f0f; }
.no-spinners::-webkit-outer-spin-button, .no-spinners::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

.action-grid { display: flex; gap: 15px; align-items: stretch; margin-top: 40px; }
.method-display { flex: 1; background: #080808; border: 1px solid #222; border-radius: 12px; padding: 10px 15px; display: flex; flex-direction: column; justify-content: center; }
.method-display .label { font-size: 10px; color: #555; margin-bottom: 2px; }
.method-display .value { display: flex; align-items: center; gap: 8px; color: white; font-size: 13px; font-weight: 500; }

.btn-pay-outline {
  flex: 1.2; background: transparent; border: 1px solid #22c55e; color: #22c55e; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 10px;
}
.btn-pay-outline:hover { background: rgba(34, 197, 94, 0.1); box-shadow: 0 0 15px rgba(34, 197, 94, 0.2); }

/* ИСТОРИЯ */
.section-title.small { font-size: 16px; color: #888; margin-bottom: 15px; margin-top: 0; }
.history-list { display: flex; flex-direction: column; gap: 10px; }
.history-item { display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.01); border: 1px solid #1a1a1a; padding: 12px 20px; border-radius: 14px; transition: 0.2s; }
.history-item:hover { background: rgba(255,255,255,0.03); }
.tx-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: #111; color: #555; }
.tx-icon.success { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
.tx-icon.paid { background: rgba(255, 255, 255, 0.05); color: #aaa; }
.tx-info { flex-grow: 1; }
.tx-title { color: white; font-size: 14px; font-weight: 500; margin-bottom: 2px; }
.tx-date { color: #555; font-size: 12px; }
.tx-amount { color: white; font-weight: bold; font-size: 14px; }
.tx-amount.positive { color: #22c55e; }

/* МОДАЛКА */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 100; }
.modal-content { background: #111; border: 1px solid #333; padding: 30px; border-radius: 20px; max-width: 350px; text-align: center; }
.modal-content h3 { color: white; margin-top: 0; }
.modal-content p { color: #888; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
.btn-discord { display: inline-block; background: #5865F2; color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 14px; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.3s ease; }
.slide-fade-enter-from, .slide-fade-leave-to { transform: translateY(-10px); opacity: 0; }
</style>