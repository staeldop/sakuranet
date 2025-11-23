<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'dashboard' 
})

const auth = useAuthStore()

// Запрос к API
const { data, error } = await useApiFetch("/api/ping", {
  lazy: true,
  server: false,
})

const logout = () => {
  auth.logout()
}
</script>

<template>
  <div class="dashboard-page">
    
    <!-- 🔥 ЭФФЕКТ СВЕЧЕНИЯ (GLOW) 🔥 -->
    <div class="glow glow-1" />
    <div class="glow glow-2" />

    <div class="content-wrapper">
      
      <h1 class="welcome-text">
        Добро пожаловать, <span class="username">{{ auth.user?.name || 'User' }}</span>! 👋
      </h1>
      <p class="subtitle">Все системы работают в штатном режиме.</p>

      <!-- Стеклянная карточка статуса -->
      <div class="glass-card status-card">
        <div class="card-header">
          <h3>📡 Статус API</h3>
          <span class="status-dot" :class="{ online: data?.status === 'ok' }"></span>
        </div>
        
        <div class="status-grid">
          <div class="status-item">
            <span class="label">Состояние</span>
            <span class="value" :style="{ color: data?.status === 'ok' ? '#4caf50' : '#f44336' }">
              {{ data?.status === 'ok' ? 'Активно' : 'Ошибка' }}
            </span>
          </div>

          <div class="status-item">
            <span class="label">Сервис</span>
            <span class="value">{{ data?.service || 'Загрузка...' }}</span>
          </div>

          <div class="status-item">
            <span class="label">Время сервера</span>
            <span class="value time">{{ data?.time || '--:--:--' }}</span>
          </div>
        </div>

        <div v-if="error" class="error-box">
          ❌ Ошибка подключения: {{ error.message }}
        </div>
      </div>

      <!-- Быстрые действия -->
      <div class="quick-actions">
        <NuxtLink to="/dashboard/order" class="action-card">
          <div class="icon-box">🎮</div>
          <span>Заказать сервер</span>
        </NuxtLink>
        
        <NuxtLink to="/dashboard/topup" class="action-card">
          <div class="icon-box">💳</div>
          <span>Пополнить счет</span>
        </NuxtLink>

        <button @click="logout" class="action-card logout">
          <div class="icon-box">🚪</div>
          <span>Выйти</span>
        </button>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* === ОСНОВА СТРАНИЦЫ === */
.dashboard-page {
  position: relative;
  min-height: 100%;
  width: 100%;
  overflow: hidden; /* Чтобы свечение не вызывало скролл */
  padding-bottom: 40px;
}

/* === КОНТЕНТ (поверх свечения) === */
.content-wrapper {
  position: relative;
  z-index: 10;
  max-width: 800px;
}

.welcome-text {
  font-size: 32px;
  font-weight: 700;
  color: white;
  margin-bottom: 8px;
}
.username {
  background: linear-gradient(90deg, #ff0055, #0055ff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.subtitle {
  color: #888;
  font-size: 16px;
  margin-bottom: 40px;
}

/* === СВЕЧЕНИЕ (GLOW) === */
.glow {
  position: absolute;
  width: 600px;
  height: 600px;
  border-radius: 50%;
  filter: blur(100px);
  opacity: 0.15; /* Прозрачность чуть ниже, чтобы не мешать читать */
  pointer-events: none;
  z-index: 0;
}
.glow-1 {
  top: -100px;
  left: -100px;
  background: radial-gradient(circle, #ff0055, transparent 70%);
  animation: floatGlow1 20s linear infinite;
}
.glow-2 {
  bottom: -100px;
  right: -100px;
  background: radial-gradient(circle, #0055ff, transparent 70%);
  animation: floatGlow2 25s linear infinite;
}

/* Анимации плавания */
@keyframes floatGlow1 {
  0% { transform: translate(0, 0); }
  50% { transform: translate(40px, 30px); }
  100% { transform: translate(0, 0); }
}
@keyframes floatGlow2 {
  0% { transform: translate(0, 0); }
  50% { transform: translate(-40px, -30px); }
  100% { transform: translate(0, 0); }
}

/* === КАРТОЧКА СТАТУСА === */
.glass-card {
  background: rgba(20, 20, 20, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 24px;
  margin-bottom: 30px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  padding-bottom: 15px;
  margin-bottom: 20px;
}
.card-header h3 {
  margin: 0;
  font-size: 16px;
  color: #ccc;
  text-transform: uppercase;
  letter-spacing: 1px;
}
.status-dot {
  width: 8px; height: 8px; border-radius: 50%; background: #444;
  box-shadow: 0 0 10px rgba(0,0,0,0.5);
}
.status-dot.online {
  background: #4caf50;
  box-shadow: 0 0 10px #4caf50;
}

.status-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}
@media (max-width: 600px) {
  .status-grid { grid-template-columns: 1fr; }
}

.status-item { display: flex; flex-direction: column; gap: 4px; }
.label { font-size: 12px; color: #666; font-weight: 600; text-transform: uppercase; }
.value { font-size: 15px; color: white; font-weight: 500; font-family: monospace; }
.time { color: #aaa; }

.error-box {
  margin-top: 20px; padding: 10px; background: rgba(244, 67, 54, 0.1);
  border: 1px solid rgba(244, 67, 54, 0.2); border-radius: 8px; color: #f44336; font-size: 13px;
}

/* === БЫСТРЫЕ ДЕЙСТВИЯ === */
.quick-actions {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}
.action-card {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 12px; padding: 20px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 16px;
  text-decoration: none;
  color: #ccc;
  transition: all 0.3s ease;
  cursor: pointer;
}
.action-card:hover {
  background: rgba(255,255,255,0.07);
  transform: translateY(-2px);
  color: white;
  border-color: rgba(255,255,255,0.1);
}
.icon-box { font-size: 24px; }
.action-card.logout:hover {
  background: rgba(244, 67, 54, 0.1);
  border-color: rgba(244, 67, 54, 0.3);
  color: #f44336;
}
</style>