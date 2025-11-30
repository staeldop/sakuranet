<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

// Импорт иконок
import IconUsers from '~/assets/icons/users.svg?component' 
import IconHome from '~/assets/icons/home.svg?component'
import IconLogout from '~/assets/icons/logout.svg?component'
import IconServer from '~/assets/icons/server.svg?component'
import IconBox from '~/assets/icons/box.svg?component' 
import IconTicket from '~/assets/icons/ticket.svg?component'

const auth = useAuthStore()

const handleLogout = () => {
  auth.logout()
}
</script>

<template>
  <aside class="admin-sidebar glass-effect">
    <!-- Подсветка фона -->
    <div class="sidebar-glow"></div>

    <div class="sidebar-header">
      <div class="logo-container">
        <span class="brand-dot"></span>
        <span class="brand-text">SAKURANET</span>
      </div>
      <div class="admin-badge">ADMIN PANEL</div>
    </div>

    <nav class="nav-menu">
      <!-- Главная -->
      <div class="menu-section">
        <span class="section-label">Навигация</span>
        <NuxtLink to="/dashboard" class="nav-item back-link">
          <IconHome class="icon" />
          <span>Вернуться на сайт</span>
        </NuxtLink>
      </div>

      <div class="divider"></div>

      <!-- Управление -->
      <div class="menu-section">
        <span class="section-label">Управление</span>
        
        <NuxtLink to="/dashboard/admin/users" class="nav-item">
          <IconUsers class="icon" />
          <span>Пользователи</span>
        </NuxtLink>

        <NuxtLink to="/dashboard/admin/products" class="nav-item">
          <IconServer class="icon" />
          <span>Товары и Услуги</span>
        </NuxtLink>
        
        <!-- 🔥 ИСПРАВЛЕНО: Теперь ссылка ведет в правильное место -->
        <NuxtLink to="/dashboard/admin/tickets" class="nav-item">
          <IconTicket class="icon" />
          <span>Тикеты</span>
        </NuxtLink>
      </div>
    </nav>

    <!-- Футер -->
    <div class="sidebar-footer">
      <div class="user-profile">
        <div class="avatar-circle">
          {{ auth.user?.name?.charAt(0).toUpperCase() || 'A' }}
        </div>
        <div class="user-info">
          <div class="user-name">{{ auth.user?.name }}</div>
          <div class="user-role">Administrator</div>
        </div>
      </div>
      
      <button @click="handleLogout" class="logout-btn" title="Выйти">
        <IconLogout class="icon-logout" />
      </button>
    </div>
  </aside>
</template>

<style scoped>
/* === ОСНОВА === */
.admin-sidebar {
  width: 280px;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 100;
  display: flex;
  flex-direction: column;
  padding: 24px;
  box-sizing: border-box;
  
  /* Стеклянный стиль */
  background: rgba(10, 10, 10, 0.85);
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(20px);
}

.sidebar-glow {
  position: absolute;
  top: 0; left: 0; right: 0; height: 200px;
  background: radial-gradient(circle at top left, rgba(255, 0, 85, 0.15), transparent 70%);
  pointer-events: none;
  z-index: -1;
}

/* === HEADER === */
.sidebar-header {
  margin-bottom: 32px;
  padding-left: 4px;
}

.logo-container {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.brand-dot {
  width: 8px;
  height: 8px;
  background: #ff0055;
  border-radius: 50%;
  box-shadow: 0 0 10px #ff0055;
}

.brand-text {
  font-size: 16px;
  font-weight: 800;
  letter-spacing: 2px;
  color: white;
}

.admin-badge {
  display: inline-block;
  font-size: 9px;
  font-weight: 700;
  color: #666;
  background: rgba(255, 255, 255, 0.05);
  padding: 4px 8px;
  border-radius: 6px;
  letter-spacing: 1px;
  margin-left: 18px; /* Отступ под текст */
  border: 1px solid rgba(255, 255, 255, 0.05);
}

/* === NAV MENU === */
.nav-menu {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.menu-section {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.section-label {
  font-size: 10px;
  text-transform: uppercase;
  color: #555;
  font-weight: 700;
  letter-spacing: 1px;
  margin-bottom: 8px;
  padding-left: 12px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  color: #888;
  text-decoration: none;
  border-radius: 12px;
  transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
  font-size: 14px;
  font-weight: 500;
  border: 1px solid transparent;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
}

.nav-item.router-link-active {
  background: rgba(255, 0, 85, 0.1);
  color: #ff4d80;
  border-color: rgba(255, 0, 85, 0.2);
  box-shadow: 0 4px 12px rgba(255, 0, 85, 0.1);
}

.nav-item.back-link {
  color: #aaa;
  border: 1px solid rgba(255, 255, 255, 0.05);
}
.nav-item.back-link:hover {
  border-color: rgba(255, 255, 255, 0.15);
  background: rgba(255, 255, 255, 0.03);
  color: white;
}

.icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.divider {
  height: 1px;
  background: linear-gradient(90deg, rgba(255,255,255,0.08), transparent);
  margin: 0 10px;
}

/* === FOOTER === */
.sidebar-footer {
  margin-top: auto;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 10px;
}

.avatar-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #222, #111);
  border: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #888;
  font-weight: 700;
  font-size: 12px;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 13px;
  color: #fff;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 110px;
}

.user-role {
  font-size: 10px;
  color: #555;
  text-transform: uppercase;
}

.logout-btn {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  color: #666;
  cursor: pointer;
  transition: 0.2s;
}

.logout-btn:hover {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.2);
}

.icon-logout {
  width: 16px;
  height: 16px;
}
</style>