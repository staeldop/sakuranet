<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

// Импорт иконок
import IconUsers from '~/assets/icons/users.svg?component' 
import IconHome from '~/assets/icons/home.svg?component'
import IconLogout from '~/assets/icons/logout.svg?component'
import IconServer from '~/assets/icons/server.svg?component'
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
        
        <NuxtLink to="/dashboard/admin/tickets" class="nav-item">
          <IconTicket class="icon" />
          <span>Тикеты</span>
        </NuxtLink>

        <!-- 🔥 НОВЫЙ ПУНКТ: УВЕДОМЛЕНИЯ -->
        <NuxtLink to="/dashboard/admin/notifications" class="nav-item">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
             <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke-linecap="round" stroke-linejoin="round" />
             <path d="M13.73 21a2 2 0 0 1-3.46 0" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          <span>Уведомления</span>
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
  backdrop-filter: blur(16px);
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  
  /* Анимация появления */
  animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
  from { transform: translateX(-20px); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

.sidebar-glow {
  position: absolute;
  top: 0; left: 0; right: 0; height: 100%;
  background: radial-gradient(circle at 0% 0%, rgba(59, 130, 246, 0.08), transparent 40%);
  pointer-events: none;
  z-index: -1;
}

/* === HEADER === */
.sidebar-header { margin-bottom: 30px; }

.logo-container {
  display: flex; align-items: center; gap: 10px; margin-bottom: 8px;
}
.brand-dot {
  width: 10px; height: 10px; border-radius: 50%;
  background: #3b82f6;
  box-shadow: 0 0 10px #3b82f6;
}
.brand-text {
  font-size: 20px; font-weight: 800; color: #fff; letter-spacing: 1px;
}

.admin-badge {
  display: inline-block;
  font-size: 10px; font-weight: 700;
  background: rgba(59, 130, 246, 0.15);
  color: #3b82f6;
  padding: 4px 8px;
  border-radius: 6px;
  letter-spacing: 0.5px;
  border: 1px solid rgba(59, 130, 246, 0.2);
}

/* === NAVIGATION === */
.nav-menu {
  flex-grow: 1;
  display: flex; flex-direction: column; gap: 20px;
  overflow-y: auto;
  /* Скроллбар */
  scrollbar-width: thin;
  scrollbar-color: rgba(255,255,255,0.1) transparent;
}

.menu-section { display: flex; flex-direction: column; gap: 6px; }

.section-label {
  font-size: 11px; text-transform: uppercase; color: #555; font-weight: 600;
  margin-bottom: 6px; padding-left: 12px;
}

.nav-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 14px;
  border-radius: 12px;
  color: #999;
  text-decoration: none;
  font-size: 14px; font-weight: 500;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
}

.nav-item.router-link-active {
  background: rgba(59, 130, 246, 0.1);
  color: #fff;
  border-color: rgba(59, 130, 246, 0.2);
  box-shadow: 0 4px 15px rgba(59, 130, 246, 0.1);
}

.nav-item .icon {
  width: 18px; height: 18px;
  transition: 0.2s;
}

.nav-item.router-link-active .icon { color: #3b82f6; transform: scale(1.1); }

.back-link {
  color: #aaa; font-size: 13px;
}
.back-link:hover .icon { transform: translateX(-3px); }

.divider {
  height: 1px; background: rgba(255,255,255,0.05); margin: 5px 0;
}

/* === FOOTER === */
.sidebar-footer {
  margin-top: 20px; padding-top: 20px;
  border-top: 1px solid rgba(255,255,255,0.08);
  display: flex; align-items: center; justify-content: space-between;
}

.user-profile { display: flex; align-items: center; gap: 12px; }

.avatar-circle {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-weight: 700; font-size: 14px;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
}

.user-info { display: flex; flex-direction: column; }
.user-name { color: #fff; font-size: 13px; font-weight: 600; white-space: nowrap; }
.user-role { color: #666; font-size: 11px; }

.logout-btn {
  background: rgba(255,255,255,0.05); border: none;
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  color: #888; cursor: pointer; transition: 0.2s;
}
.logout-btn:hover { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
.icon-logout { width: 16px; height: 16px; }

</style>
