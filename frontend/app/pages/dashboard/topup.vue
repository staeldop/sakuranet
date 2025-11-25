<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useApiFetch } from '~/composables/useApi'

// ИМПОРТ ИКОНОК
import IconWallet from '~/assets/icons/wallet.svg?component'
import IconTicket from '~/assets/icons/ticket.svg?component'
import IconCard from '~/assets/icons/credit-card.svg?component'
import IconArrow from '~/assets/icons/arrow-right.svg?component'
import IconCart from '~/assets/icons/cart.svg?component'

definePageMeta({
  layout: 'dashboard'
})

const auth = useAuthStore()

// --- СОСТОЯНИЕ ---
const transactions = ref<any[]>([])
const isLoading = ref(false) 

// --- ЛОГИКА ---
const amount = ref<number | string>('')
const promoCode = ref('')
const voucherCode = ref('')
const showVoucherInput = ref(false)
const isPaying = ref(false)

// --- ЗАГРУЗКА ДАННЫХ ---
const loadData = async () => {
  isLoading.value = true
  try {
    const { data, error } = await useApiFetch<any[]>('/api/payment/history')
    
    if (data.value) {
      transactions.value = data.value.map((t: any) => ({
        id: t.id,
        title: t.description,
        date: new Date(t.created_at).toLocaleDateString('ru-RU'),
        amount: (t.type === 'deposit' ? '+' : '-') + Math.abs(Number(t.amount)).toFixed(0) + ' ₽',
        status: 'success' 
      }))
    }
  } catch (e) {
    console.error('Ошибка загрузки истории:', e)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadData()
})

// --- 3D HUD ---
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
  background: `radial-gradient(400px circle at ${mouseX.value}px ${mouseY.value}px, rgba(255, 255, 255, 0.12), transparent 40%)`
}))

const tiltStyle = computed(() => {
  if (!cardRef.value) return {}
  const rect = cardRef.value.getBoundingClientRect()
  const cx = rect.width / 2
  const cy = rect.height / 2
  const rx = (mouseY.value - cy) / 30 
  const ry = (cx - mouseX.value) / 30
  return { transform: `perspective(1000px) rotateX(${rx}deg) rotateY(${ry}deg)` }
})

const applyVoucher = () => {
  alert(`Активация: ${voucherCode.value}`)
  showVoucherInput.value = false
}

const handlePayment = async () => {
  if (!amount.value || Number(amount.value) < 10) return alert('Минимум 10 рублей')
  isPaying.value = true
  try {
    const { error } = await useApiFetch('/api/payment/topup', {
      method: 'POST',
      body: { amount: amount.value }
    })

    if (error.value) throw error.value

    await auth.fetchUser()
    loadData()
    
    amount.value = ''
    alert('Успешно пополнено!')
    
  } catch (e) {
    alert('Ошибка при создании платежа')
  } finally {
    isPaying.value = false
  }
}
</script>

<template>
  <div class="finance-page">
    <div class="glow glow-1" />
    <div class="glow glow-2" />

    <div class="content-wrapper">
      <div class="page-header">
        <h1>Финансы</h1>
        <p>Управление счетом и транзакции</p>
      </div>

      <div class="grid-layout">
        <div class="left-col">
          <div 
            class="cyber-card"
            ref="cardRef"
            @mousemove="handleMouseMove"
            @mouseleave="() => { mouseX = 0; mouseY = 0 }"
            :style="tiltStyle"
          >
            <div class="noise-overlay"></div>
            <div class="card-ambient-purple"></div>
            <div class="card-ambient-blue"></div>
            
            <div class="glow-layer" :style="glowStyle"></div>
            <div class="cyber-decor-top">
              <div class="sim-chip"><span></span><span></span><span></span><span></span></div>
              <span class="card-label">SAKURANET // WALLET</span>
            </div>
            <div class="balance-wrapper">
              <span class="currency-symbol">RUB</span>
              <span class="balance-value">{{ auth.formattedBalance }}</span>
            </div>
            <div class="cyber-decor-bottom">
              <div class="status-indicator">
                <div class="status-dot"></div>
                <span class="status-text">ENCRYPTED CONNECTION</span>
              </div>
              <div class="barcode">|| | ||| ||</div>
            </div>
          </div>
          
          <div class="voucher-wrapper">
            <button @click="showVoucherInput = !showVoucherInput" class="voucher-toggle-btn" :class="{ active: showVoucherInput }">
              <div class="btn-content">
                <IconTicket class="icon-svg" /> <span>Активировать ваучер</span>
              </div>
              <span class="arrow" :class="{ rotated: showVoucherInput }">▼</span>
            </button>
            <Transition name="expand">
              <div v-if="showVoucherInput" class="voucher-dropdown-wrapper">
                <div class="voucher-dropdown">
                  <form @submit.prevent="applyVoucher">
                    <label>Код доступа</label>
                    <div class="input-row">
                      <input v-model="voucherCode" type="text" placeholder="XXXX-XXXX-XXXX" class="glass-input">
                      <button type="submit" class="btn-voucher-submit"><IconArrow class="icon-svg" /></button>
                    </div>
                  </form>
                </div>
              </div>
            </Transition>
          </div>
        </div>

        <div class="right-col">
          <div class="glass-panel">
            <div class="glow-purple-top"></div>
            <div class="glow-red-bottom"></div>

            <div class="card-glow-top" />
            <h2 class="panel-title">Пополнение</h2>
            <form @submit.prevent="handlePayment" class="payment-form">
              <div class="form-group">
                <label><IconWallet class="icon-svg text-gray" /> Сумма платежа</label>
                <div class="relative">
                    <input 
                      v-model="amount" type="number" min="10" placeholder="0" 
                      class="glass-input big-text no-spinners" :disabled="isPaying"
                    >
                    <span class="currency-abs">₽</span>
                </div>
              </div>
              <div class="form-group">
                <label><IconTicket class="icon-svg text-gray" /> Промокод</label>
                <input 
                  v-model="promoCode" type="text" placeholder="Опционально" 
                  class="glass-input" :disabled="isPaying"
                >
              </div>
              <div class="action-grid">
                <div class="method-display">
                  <span class="label">Метод</span>
                  <div class="value"><IconCard class="icon-svg" /> Карты РФ</div>
                </div>
                <button type="submit" class="cosmic-btn" :disabled="isPaying">
                  <span v-if="!isPaying">Оплатить</span>
                  <span v-else>Жди...</span>
                  <div class="btn-glow"></div>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <div class="history-section">
        <h2 class="section-title small">Последние операции</h2>
        
        <div v-if="isLoading" class="history-list">
           <div class="history-item skeleton" v-for="i in 3" :key="i"></div>
        </div>

        <div v-else-if="transactions.length === 0" class="empty-history">
           История операций пуста
        </div>

        <div v-else class="history-list">
          <div v-for="tx in transactions" :key="tx.id" class="history-item">
             <div class="tx-icon" :class="tx.status">
              <IconArrow v-if="tx.status === 'success'" class="icon-svg rotate-down" /> 
              <IconWallet v-else class="icon-svg" /> 
            </div>
             <div class="tx-info">
              <div class="tx-title">{{ tx.title }}</div>
              <div class="tx-date">{{ tx.date }}</div>
            </div>
            <div class="tx-amount" :class="{ positive: tx.amount.includes('+') }">
              {{ tx.amount }}
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.finance-page { position: relative; width: 100%; max-width: 1100px; margin: 0; padding-bottom: 80px; }
.content-wrapper { position: relative; z-index: 10; }
.grid-layout { display: grid; grid-template-columns: 1fr; gap: 60px; margin-bottom: 50px; }
@media (min-width: 1024px) { .grid-layout { grid-template-columns: 0.9fr 1.1fr; gap: 80px; } }

.glow { position: absolute; width: 600px; height: 600px; border-radius: 50%; filter: blur(100px); opacity: 0.15; pointer-events: none; z-index: 0; }
.glow-1 { top: -100px; left: -200px; background: radial-gradient(circle, #ff0055, transparent 70%); animation: floatGlow1 20s linear infinite; }
.glow-2 { bottom: 0; right: -200px; background: radial-gradient(circle, #0055ff, transparent 70%); animation: floatGlow2 25s linear infinite; }
@keyframes floatGlow1 { 0%, 100% { transform: translate(0,0); } 50% { transform: translate(40px, 30px); } }
@keyframes floatGlow2 { 0%, 100% { transform: translate(0,0); } 50% { transform: translate(-40px, -30px); } }

.page-header { margin-bottom: 30px; }
.page-header h1 { font-size: 24px; font-weight: 700; color: white; margin: 0; }
.page-header p { color: #888; font-size: 13px; margin-top: 4px; }
.section-title.small { font-size: 16px; color: #888; margin-bottom: 15px; margin-top: 0; }

/* КАРТОЧКА */
.cyber-card { 
  position: relative; width: 100%; height: 210px; border-radius: 20px; 
  background: rgba(20, 20, 20, 0.6); backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.1); 
  overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; padding: 25px; margin-bottom: 20px; 
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  box-shadow: 0 20px 50px rgba(0,0,0,0.5); 
}
.cyber-card:hover { border-color: rgba(255, 255, 255, 0.2); transform: translateY(-2px); }

.card-ambient-purple { position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(168, 85, 247, 0.3), transparent 70%); filter: blur(40px); z-index: 0; pointer-events: none; }
.card-ambient-blue { position: absolute; bottom: -30px; left: -30px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(59, 130, 246, 0.2), transparent 70%); filter: blur(40px); z-index: 0; pointer-events: none; }
.noise-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.07; pointer-events: none; z-index: 0; background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E"); }
.glow-layer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; mix-blend-mode: overlay; transition: background 0.1s; }
.cyber-decor-top, .cyber-decor-bottom, .balance-wrapper { position: relative; z-index: 2; }
.cyber-decor-top { display: flex; justify-content: space-between; align-items: center; }
.cyber-decor-bottom { display: flex; justify-content: space-between; align-items: center; }
.card-label { font-size: 10px; color: #777; letter-spacing: 2px; font-weight: 700; font-family: monospace; }
.sim-chip { width: 34px; height: 24px; background: linear-gradient(135deg, #e5e5e5 0%, #999 100%); border-radius: 4px; opacity: 0.9; position: relative; overflow: hidden; }
.sim-chip span { position: absolute; background: rgba(0,0,0,0.15); }
.sim-chip span:nth-child(1) { width: 1px; height: 100%; left: 33%; top: 0; }
.sim-chip span:nth-child(2) { width: 1px; height: 100%; left: 66%; top: 0; }
.sim-chip span:nth-child(3) { width: 100%; height: 1px; left: 0; top: 33%; }
.sim-chip span:nth-child(4) { width: 100%; height: 1px; left: 0; top: 66%; }
.balance-wrapper { text-align: center; margin-top: 10px; }
.currency-symbol { font-size: 11px; color: #22c55e; font-weight: bold; letter-spacing: 2px; margin-bottom: 4px; display: block; }
.balance-value { font-size: 48px; font-weight: 800; color: white; letter-spacing: -1px; background: linear-gradient(180deg, #fff 0%, #ccc 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.status-indicator { display: flex; align-items: center; gap: 8px; }
.status-dot { width: 6px; height: 6px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 8px #22c55e; animation: pulse 2s infinite; }
.status-text { font-size: 9px; color: #22c55e; opacity: 0.8; letter-spacing: 1px; }
.barcode { font-family: monospace; font-size: 14px; color: #444; letter-spacing: 3px; }

/* ВАУЧЕР */
.voucher-toggle-btn { width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; color: #888; cursor: pointer; transition: 0.2s; }
.voucher-toggle-btn:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.2); color: white; }
.btn-content { display: flex; align-items: center; gap: 10px; font-size: 14px; }
.voucher-dropdown { margin-top: 8px; background: rgba(10,10,10,0.8); border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 14px; }
.input-row { display: flex; gap: 8px; margin-top: 8px; }
.btn-voucher-submit { width: 44px; flex-shrink: 0; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.btn-voucher-submit:hover { background: #22c55e; border-color: #22c55e; color: black; }

/* ПРАВАЯ ПАНЕЛЬ */
.glass-panel { 
  background: rgba(12, 12, 12, 0.65); border: 1px solid rgba(255, 255, 255, 0.08); 
  backdrop-filter: blur(20px); padding: 30px; border-radius: 24px; 
  box-shadow: inset 0 0 0 1px rgba(255,255,255,0.03); 
  position: relative; overflow: hidden; 
}
.glow-purple-top { position: absolute; top: -80px; right: -80px; width: 250px; height: 250px; background: radial-gradient(circle, rgba(168, 85, 247, 0.15) 0%, transparent 70%); filter: blur(60px); opacity: 0.6; z-index: 0; pointer-events: none; }
.glow-red-bottom { position: absolute; bottom: -80px; left: -80px; width: 250px; height: 250px; background: radial-gradient(circle, rgba(239, 68, 68, 0.1) 0%, transparent 70%); filter: blur(60px); opacity: 0.5; z-index: 0; pointer-events: none; }
.payment-form { position: relative; z-index: 10; }
.panel-title { font-size: 18px; margin: 0 0 25px 0; color: white; font-weight: 600; position: relative; z-index: 10; }
.form-group { margin-bottom: 20px; }
.form-group label { display: flex; align-items: center; gap: 8px; color: #888; font-size: 12px; margin-bottom: 8px; font-weight: 500; }

/* INPUTS */
.glass-input { width: 100%; box-sizing: border-box; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); padding: 12px 16px; border-radius: 12px; color: white; font-size: 14px; transition: 0.2s; outline: none; }
.glass-input:focus { border-color: rgba(168, 85, 247, 0.5); background: rgba(168, 85, 247, 0.05); }
.glass-input.big-text { font-size: 20px; font-weight: 700; padding-right: 30px; }
.currency-abs { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #555; pointer-events: none; }

/* 🔥 УБИРАЕМ СТРЕЛОЧКИ (SPINNERS) 🔥 */
.no-spinners::-webkit-outer-spin-button,
.no-spinners::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.no-spinners {
  -moz-appearance: textfield; /* Для Firefox */
}

/* КНОПКА */
.action-grid { display: flex; gap: 15px; align-items: stretch; margin-top: 40px; }
.method-display { flex: 1; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 10px 15px; display: flex; flex-direction: column; justify-content: center; }
.method-display .label { font-size: 10px; color: #555; margin-bottom: 2px; }
.method-display .value { display: flex; align-items: center; gap: 8px; color: white; font-size: 13px; font-weight: 500; }
.cosmic-btn { flex: 1.2; position: relative; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; overflow: hidden; transition: all 0.3s ease; }
.btn-glow { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(168, 85, 247, 0.4), transparent); transform: translateX(-100%); transition: transform 0.5s ease; opacity: 0.5; }
.cosmic-btn:hover:not(:disabled) { background: rgba(168, 85, 247, 0.15); border-color: rgba(168, 85, 247, 0.5); box-shadow: 0 0 20px rgba(168, 85, 247, 0.2); }
.cosmic-btn:hover:not(:disabled) .btn-glow { transform: translateX(100%); }
.cosmic-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* ИСТОРИЯ */
.history-list { display: flex; flex-direction: column; gap: 10px; }
.history-item { display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04); padding: 12px 20px; border-radius: 14px; transition: 0.2s; }
.history-item:hover { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.1); }
.tx-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); color: #555; }
.tx-icon.success { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
.tx-info { flex-grow: 1; }
.tx-title { color: white; font-size: 14px; font-weight: 500; margin-bottom: 2px; }
.tx-date { color: #555; font-size: 12px; }
.tx-amount { color: white; font-weight: bold; font-size: 14px; }
.tx-amount.positive { color: #22c55e; }
.empty-history { text-align: center; padding: 30px; color: #555; font-size: 13px; background: rgba(255,255,255,0.01); border-radius: 14px; }
.relative { position: relative; }
.icon-svg { width: 20px; height: 20px; min-width: 20px; fill: none; stroke: currentColor; stroke-width: 2; }
.icon-svg.text-gray { color: #666; margin-right: 4px; }
.rotate-down { transform: rotate(45deg); }
.arrow { font-size: 10px; transition: 0.3s; }
.arrow.rotated { transform: rotate(180deg); }
.expand-enter-active, .expand-leave-active { transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); max-height: 200px; opacity: 1; }
.expand-enter-from, .expand-leave-to { max-height: 0; opacity: 0; margin-top: 0; transform: translateY(-10px); }
.voucher-dropdown-wrapper { overflow: hidden; }
</style>