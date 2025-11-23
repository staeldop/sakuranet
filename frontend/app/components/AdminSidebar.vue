<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

// Импортируем иконки (убедись, что они есть, или используй заглушки)
import IconUsers from '~/assets/icons/users.svg?component' // Создай или возьми любую иконку
import IconHome from '~/assets/icons/home.svg?component'
import IconLogout from '~/assets/icons/logout.svg?component'

const auth = useAuthStore()
const router = useRouter()

const handleLogout = () => {
  auth.logout()
}
</script>

<template>
  <aside class="admin-sidebar">
    <div class="sidebar-header">
      <span class="brand">SakuraNet <span class="badge">ADMIN</span></span>
    </div>

    <nav class="nav-menu">
      <!-- Ссылка обратно на сайт -->
      <NuxtLink to="/dashboard" class="nav-item back-link">
        <IconHome class="icon" />
        <span>Вернуться на сайт</span>
      </NuxtLink>

      <div class="divider"></div>

      <div class="section-title">Управление</div>

      <!-- Ссылки админки -->
      <NuxtLink to="/dashboard/admin/users" class="nav-item">
        <!-- Если нет иконки users, используй home временно -->
        <IconUsers class="icon" /> 
        <span>Пользователи</span>
      </NuxtLink>
      
      <!-- Сюда можно добавить Серверы, Транзакции и т.д. -->
    </nav>

    <div class="sidebar-footer">
      <div class="admin-info">
        <div class="admin-name">{{ auth.user?.name }}</div>
        <div class="admin-role">Administrator</div>
      </div>
      <button @click="handleLogout" class="logout-btn">
        <IconLogout class="icon" />
      </button>
    </div>
  </aside>
</template>

<style scoped>
.admin-sidebar {
  width: 260px;
  height: 100vh;
  background: #0a0a0a;
  border-right: 1px solid #222;
  display: flex;
  flex-direction: column;
  padding: 20px;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 100;
}

.sidebar-header {
  margin-bottom: 40px;
  padding-left: 10px;
}

.brand {
  font-size: 18px;
  font-weight: 700;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 8px;
}

.badge {
  font-size: 10px;
  background: #ef4444; /* Красный для админки */
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
}

.nav-menu {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.section-title {
  font-size: 11px;
  color: #555;
  text-transform: uppercase;
  font-weight: 700;
  margin: 15px 0 5px 12px;
  letter-spacing: 1px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  color: #888;
  text-decoration: none;
  border-radius: 8px;
  transition: 0.2s;
  font-size: 14px;
  font-weight: 500;
}

.nav-item:hover {
  background: rgba(255,255,255,0.03);
  color: #fff;
}

.nav-item.router-link-active {
  background: rgba(239, 68, 68, 0.1); /* Красный фон активного элемента */
  color: #ef4444;
}

.nav-item.back-link {
  color: #aaa;
}
.nav-item.back-link:hover {
  color: #fff;
}

.icon {
  width: 18px;
  height: 18px;
  stroke-width: 2px;
}

.divider {
  height: 1px;
  background: #222;
  margin: 10px 0;
}

.sidebar-footer {
  margin-top: auto;
  padding-top: 20px;
  border-top: 1px solid #222;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.admin-info {
  display: flex;
  flex-direction: column;
}

.admin-name {
  font-size: 14px;
  color: #fff;
  font-weight: 600;
}

.admin-role {
  font-size: 11px;
  color: #555;
}

.logout-btn {
  background: transparent;
  border: none;
  color: #555;
  cursor: pointer;
  transition: 0.2s;
}

.logout-btn:hover {
  color: #ef4444;
}
</style>