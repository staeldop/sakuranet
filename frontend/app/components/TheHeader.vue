<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import LogoImage from '~/assets/logo/logo-2.png'

const auth = useAuthStore()
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
        <NuxtLink to="/dashboard" class="nav-link">
          <span class="icon">🏠</span> Главная
        </NuxtLink>
        <a href="https://wiki.sakuranet.space" target="_blank" class="nav-link">
          <span class="icon">📖</span> Вики
        </a>
      </nav>

      <div class="header-right">
        <a href="https://sakuranet.space/" class="site-btn" target="_blank">
          <span>На основной сайт</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
            <polyline points="15 3 21 3 21 9"></polyline>
            <line x1="10" y1="14" x2="21" y2="3"></line>
          </svg>
        </a>

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
.nav-link.router-link-active { color: #fff; }

/* Правая часть */
.header-right {
  display: flex;
  align-items: center;
  gap: 20px;
}

.site-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  color: #aaa;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  transition: 0.3s;
}
.site-btn:hover {
  background: rgba(255, 255, 255, 0.07);
  color: #fff;
  border-color: rgba(255, 255, 255, 0.2);
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
</style>