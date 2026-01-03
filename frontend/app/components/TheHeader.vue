<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { useUiStore } from '~/stores/ui' // 1. Импортируем UI стор
import LogoImage from '~/assets/logo/logo-2.png'

// Импортируем иконки
import IconHome from '~/assets/icons/home.svg?component'
import IconFlower from '~/assets/icons/flower.svg?component' // 2. Иконка цветка

const auth = useAuthStore()
const uiStore = useUiStore() // 3. Инициализируем
const config = useRuntimeConfig()

const getAvatarUrl = () => {
  const avatarPath = auth.user?.avatar
  if (!avatarPath) return ''
  if (avatarPath.startsWith('http')) return avatarPath
  const filename = avatarPath.split('/').pop() 
  return `${config.public.apiBase}/api/avatar/${filename}`
}
</script>

<template>
  <header class="main-header">
    <div class="header-container">
      
      <div class="header-left">
        <NuxtLink to="/dashboard" class="logo-link">
          <img :src="LogoImage" alt="SakuraNet" class="header-logo" />
        </NuxtLink>
      </div>

      <nav class="header-nav">
        <a href="https://sakuranet.space" class="nav-link">
          <IconHome class="nav-icon" />
          Главная
        </a>

        <a href="https://wiki.sakuranet.space" target="_blank" class="nav-link">
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
          </svg>
          Вики
        </a>
      </nav>

      <div class="header-right">
        
        <button 
          class="sakura-btn" 
          :class="{ 'disabled': !uiStore.isSakuraEnabled }"
          @click="uiStore.toggleSakura"
          title="Вкл/Выкл фон"
        >
          <IconFlower class="sakura-icon" />
          <span class="sakura-text">Сакура</span>
        </button>

        <div class="user-pill">
          <div class="pill-avatar">
             <img v-if="auth.user?.avatar" :src="getAvatarUrl()" alt="Avatar" />
             <span v-else>{{ auth.user?.name?.[0] || '?' }}</span>
          </div>
          <span class="pill-name">{{ auth.user?.name || 'Загрузка...' }}</span>
        </div>
      </div>

    </div>
  </header>
</template>

<style scoped>
.main-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 64px;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  z-index: 1000;
}

.header-container {
  max-width: 1600px;
  height: 100%;
  margin: 0 auto;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* Логотип */
.header-logo {
  height: 30px;
  width: auto;
  display: block;
}

/* Навигация по центру */
.header-nav {
  display: flex;
  gap: 30px;
}
.nav-link {
  color: #888;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: 0.2s;
}
.nav-link:hover { color: #fff; }

/* Стиль для иконок */
.nav-icon {
  width: 18px;
  height: 18px;
  opacity: 0.8;
  transition: 0.2s;
}
.nav-link:hover .nav-icon {
  opacity: 1;
  transform: scale(1.05);
}

/* Правая часть */
.header-right {
  display: flex;
  align-items: center;
  gap: 16px; /* Чуть увеличил отступ между кнопкой и профилем */
}

/* --- 🔥 СТИЛИ КНОПКИ САКУРЫ --- */
.sakura-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px; /* Более округлая, чтобы сочеталась с User Pill */
  padding: 6px 12px;
  cursor: pointer;
  color: #ffb7b2; /* Розовый цвет */
  font-family: inherit;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.sakura-btn:hover {
  background: rgba(255, 183, 178, 0.1);
  border-color: rgba(255, 183, 178, 0.3);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255, 183, 178, 0.15);
}

.sakura-btn:active {
  transform: translateY(0);
}

.sakura-icon {
  width: 18px;
  height: 18px;
  stroke: currentColor;
  /* Анимация вращения по умолчанию, если включено */
  animation: spin 8s linear infinite; 
}

.sakura-text {
  font-size: 13px;
  font-weight: 600;
}

/* Состояние: Выключено */
.sakura-btn.disabled {
  background: transparent;
  border-color: rgba(255, 255, 255, 0.05);
  color: #555; /* Серый цвет */
}

.sakura-btn.disabled .sakura-icon {
  animation: none; /* Останавливаем вращение */
  filter: grayscale(1);
}

.sakura-btn.disabled .sakura-text {
  text-decoration: line-through; /* Зачеркиваем текст */
  opacity: 0.6;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Юзер-пилюля */
.user-pill {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 14px 4px 4px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 999px;
}
.pill-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #1a1a1a;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  font-size: 12px;
  font-weight: 700;
}
.pill-avatar img { width: 100%; height: 100%; object-fit: cover; }
.pill-name {
  font-size: 13px;
  font-weight: 600;
  color: #eee;
}

/* Скрываем шапку на мобильных устройствах */
@media (max-width: 768px) {
  .main-header {
    display: none;
  }
}
</style>