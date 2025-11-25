<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '~/stores/auth'

// ИКОНКИ
import IconHome from '~/assets/icons/home.svg?component'
import IconBell from '~/assets/icons/bell.svg?component'
import IconWallet from '~/assets/icons/wallet.svg?component'
import IconBox from '~/assets/icons/box.svg?component'
import IconServer from '~/assets/icons/server.svg?component'
import IconGamepad from '~/assets/icons/gamepad.svg?component'
import IconBalance from '~/assets/icons/balance.svg?component'
import IconLogout from '~/assets/icons/logout.svg?component'
import IconCamera from '~/assets/icons/camera.svg?component'
import IconTrash from '~/assets/icons/trash.svg?component'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const config = useRuntimeConfig()
const fileInput = ref<HTMLInputElement | null>(null)

const getAvatarUrl = () => {
  const avatarPath = auth.user?.avatar
  if (!avatarPath) return ''
  if (avatarPath.startsWith('http')) return avatarPath
  const filename = avatarPath.split('/').pop() 
  return `${config.public.apiBase}/api/avatar/${filename}`
}

const isAvatarMenuOpen = ref(false)
let closeTimeout: any = null

const openMenu = () => {
  clearTimeout(closeTimeout)
  isAvatarMenuOpen.value = true
}

const closeMenu = () => {
  closeTimeout = setTimeout(() => {
    isAvatarMenuOpen.value = false
  }, 400)
}

const changeAvatar = () => {
  fileInput.value?.click()
  isAvatarMenuOpen.value = false
}

const handleFileSelect = async (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    await auth.uploadAvatar(file)
  }
}

const deleteAvatar = async () => {
  await auth.deleteAvatar()
  isAvatarMenuOpen.value = false
}

watch(() => route.fullPath, () => isAvatarMenuOpen.value = false)

const handleLogout = () => {
  auth.logout()
}

onMounted(() => {
  if (!auth.user && auth.token) auth.fetchUser()
})

const isMenuOpen = ref(false)
const toggleMenu = () => isMenuOpen.value = !isMenuOpen.value
watch(() => route.fullPath, () => isMenuOpen.value = false)
</script>

<template>
  <div class="layout-container">
    
    <input 
      type="file" 
      ref="fileInput" 
      accept="image/*" 
      class="hidden" 
      @change="handleFileSelect"
    >

    <header class="mobile-header">
      <button class="burger-btn" @click="toggleMenu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
      <span class="mobile-title">SakuraNet</span>
    </header>

    <div class="mobile-overlay" :class="{ active: isMenuOpen }" @click="isMenuOpen = false"></div>

    <aside class="sidebar" :class="{ open: isMenuOpen }">
      
      <div class="profile-block">
        
        <div class="avatar-container" @mouseenter="openMenu" @mouseleave="closeMenu">
          <div class="avatar-wrapper">
            <div class="avatar-circle">
              <img 
                v-if="auth.user?.avatar" 
                :src="getAvatarUrl()" 
                :key="auth.user.avatar"
                class="avatar-img" 
                alt="Avatar" 
              />
              <span v-else-if="auth.user?.name" class="avatar-text">
                {{ auth.user.name[0].toUpperCase() }}
              </span>
              <span v-else class="avatar-text">?</span>
            </div>
            <div class="edit-badge" :class="{ active: isAvatarMenuOpen }">⚙️</div>
          </div>

          <Transition name="drop-down">
            <div v-if="isAvatarMenuOpen" class="avatar-dropdown" @mouseenter="openMenu">
              <button @click="changeAvatar" class="menu-btn">
                <IconCamera class="menu-icon" /> 
                <span>Поставить новую</span>
              </button>
              <div class="menu-divider"></div>
              <button @click="deleteAvatar" class="menu-btn delete">
                <IconTrash class="menu-icon" /> 
                <span>Удалить эту</span>
              </button>
            </div>
          </Transition>
        </div>
        
        <div class="profile-info">
          
          <div class="profile-header">
            <div class="nickname">
               <template v-if="auth.user">{{ auth.user.name }}</template>
               <template v-else>Загрузка...</template>
            </div>
            <button class="logout-mini-btn" @click="handleLogout" title="Выйти">
              <IconLogout class="logout-icon-mini" />
            </button>
          </div>

          <div class="balance-row">
            <IconBalance class="balance-icon" />
            <div class="balance-text">{{ auth.formattedBalance }} ₽</div>
          </div>

        </div>
      </div>
      <nav class="nav-list">
        <NuxtLink to="/dashboard" class="nav-item">
          <IconHome />
          <span class="link-text">Главная</span>
        </NuxtLink>
        <NuxtLink to="/dashboard/notifications" class="nav-item">
          <IconBell />
          <span class="link-text">Уведомления</span>
        </NuxtLink>
        <NuxtLink to="/dashboard/topup" class="nav-item">
          <IconWallet />
          <span class="link-text">Пополнить</span>
        </NuxtLink>
        <NuxtLink to="/dashboard/services" class="nav-item">
          <IconBox />
          <span class="link-text">Услуги</span>
        </NuxtLink>
        <div class="spacer"></div>
        <div class="section-title">Услуги</div>
        
        <NuxtLink to="/dashboard/order" class="nav-item">
          <IconGamepad />
          <span class="link-text">Серверы</span> 
        </NuxtLink>
      </nav>
    </aside>

    <main class="main-content">
      <NuxtPage 
        :page-key="route.fullPath"
        :transition="{ name: 'page', mode: 'out-in' }" 
      />
    </main>

  </div>
</template>

<style scoped>
.hidden { display: none; }

.layout-container {
  --global-top-padding: 155px; 
  --sb-width: 260px; 
  --sb-padding-top: 155px; 
  --sb-padding-left: 120px; 
  --sb-padding-right: 30px; 
  --avatar-size: 64px; 
  --avatar-border-radius: 12px; 
  --profile-gap: 16px; 
  --profile-bottom: 20px; 
  --bg-color: #000000; 
  --sb-bg: rgba(255,255,255, 0.02);
  --menu-icon-size: 20px; 

  display: flex; 
  min-height: 100vh; 
  width: 100vw; 
  background-color: var(--bg-color); 
  color: #ffffff; 
  font-family: 'Segoe UI', sans-serif; 
  overflow: hidden; 
}

.sidebar { 
  width: var(--sb-width); 
  background-color: var(--sb-bg); 
  display: flex; flex-direction: column; 
  padding: var(--sb-padding-top) var(--sb-padding-right) 40px var(--sb-padding-left); 
  flex-shrink: 0; 
  transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  isolation: isolate; 
}

/* 🔥 overflow-x: hidden чтобы анимация вылета не расширяла страницу */
.main-content { 
  flex-grow: 1; 
  padding-top: var(--global-top-padding); 
  padding-left: 40px; 
  padding-right: 40px; 
  padding-bottom: 40px; 
  overflow-y: auto;
  overflow-x: hidden;
  position: relative; 
}

.profile-block {
  display: flex; align-items: center; gap: var(--profile-gap);
  margin-bottom: var(--profile-bottom); position: relative; z-index: 1000;
  width: fit-content; 
}

.avatar-container { position: relative; }
.avatar-wrapper { position: relative; cursor: pointer; z-index: 1001; }
.avatar-circle { 
  width: var(--avatar-size); height: var(--avatar-size); 
  background: linear-gradient(135deg, #333, #111); 
  border-radius: var(--avatar-border-radius); 
  display: flex; align-items: center; justify-content: center;
  overflow: hidden; box-shadow: none; 
}
.avatar-img { width: 100%; height: 100%; object-fit: cover; }
.avatar-text { font-weight: bold; color: #777; font-size: 22px; } 

.edit-badge {
  position: absolute; bottom: -2px; right: -2px;
  width: 18px; height: 18px;
  background: #1a1a1a; border: 1px solid #333; border-radius: 50%;
  font-size: 9px; display: flex; align-items: center; justify-content: center;
  transition: 0.2s; opacity: 0; transform: scale(0.8);
}
.avatar-wrapper:hover .edit-badge, .edit-badge.active { opacity: 1; transform: scale(1); }

.profile-info { display: flex; flex-direction: column; gap: 6px; justify-content: center; }
.profile-header { display: flex; align-items: center; gap: 12px; }
.nickname { 
  font-weight: 800; font-size: 17px; color: #fff;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1;
}

.logout-mini-btn {
  background: transparent; border: none; padding: 0; cursor: pointer;
  color: #6b7280; display: flex; align-items: center; transition: color 0.2s;
}
.logout-mini-btn:hover { color: #fff; }
.logout-icon-mini { width: 16px; height: 16px; stroke-width: 2.5px; }

.balance-row { display: flex; align-items: center; gap: 8px; }
.balance-icon { width: 20px; height: 20px; color: #6b7280; stroke-width: 2px; flex-shrink: 0; }
.balance-text { font-size: 15px; color: #fff; font-weight: 700; letter-spacing: 0.5px; }

.avatar-dropdown {
  position: absolute; top: 100%; left: 0; width: 190px;
  padding-top: 15px; z-index: 1002; display: flex; flex-direction: column;
}
.avatar-dropdown::before {
  content: ''; position: absolute; top: 15px; bottom: 0; left: 0; right: 0; 
  background-color: rgba(10, 10, 10, 0.45);
  backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
  border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px; 
  box-shadow: 0 20px 50px -10px rgba(0,0,0,0.9); z-index: -1;
}
.menu-btn {
  width: 100%; display: flex; align-items: center; justify-content: flex-start; gap: 12px;
  background: transparent; border: none; color: #e5e5e5; padding: 12px 16px;
  font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;
  text-align: left; position: relative; z-index: 1;
}
.menu-btn:hover { background: rgba(255, 255, 255, 0.05); color: #fff; }
.menu-icon { width: 16px; height: 16px; stroke-width: 2px; opacity: 0.6; transition: 0.2s; }
.menu-btn:hover .menu-icon { opacity: 1; }
.menu-btn.delete { color: #ff5555; margin-top: 2px; } 
.menu-btn.delete .menu-icon { stroke: #ff5555; opacity: 0.8; }
.menu-btn.delete:hover { background: rgba(220, 38, 38, 0.15); }
.menu-divider { height: 1px; background: rgba(255,255,255,0.05); margin: 0 10px; position: relative; z-index: 1; }

.nav-list { display: flex; flex-direction: column; gap: 6px; position: relative; z-index: 1; }
.nav-item { 
  text-decoration: none; color: #777; min-height: 44px; padding: 0 12px; 
  border-radius: 12px; font-size: 14px; font-weight: 500; 
  transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94); 
  display: flex; align-items: center; gap: 12px; position: relative; background-color: transparent; 
}
.nav-item svg { width: 20px; height: 20px; min-width: 20px; fill: none; stroke: currentColor; stroke-width: 2; flex-shrink: 0; }
.nav-item:hover { color: #fff; transform: scale(1.01); }
.router-link-active { color: #fff; font-weight: 600; }
.nav-item:active { transform: scale(0.98); transition: transform 0.1s ease-out; }
.router-link-active:hover { background-color: transparent; }
.spacer { height: 30px; }
.section-title { font-size: 11px; color: #444; font-weight: 700; letter-spacing: 1.5px; margin-bottom: 10px; padding-left: 12px; text-transform: uppercase; }

.mobile-header { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 60px; background: rgba(0,0,0, 0.8); backdrop-filter: blur(10px); z-index: 50; align-items: center; padding: 0 20px; border-bottom: 1px solid #222; }
.burger-btn { background: none; border: none; color: white; cursor: pointer; padding: 5px; }
.mobile-title { font-weight: bold; margin-left: 15px; font-size: 18px; }
.mobile-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 90; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
.mobile-overlay.active { opacity: 1; pointer-events: auto; }

@media (max-width: 768px) {
  .layout-container { flex-direction: column; }
  .mobile-header { display: flex; }
  .sidebar { 
    position: fixed; top: 0; left: 0; height: 100vh; 
    z-index: 100; transform: translateX(-100%); width: 280px; 
    padding: 80px 20px 40px 20px; background-color: #000000; border-right: none; 
  }
  .sidebar.open { transform: translateX(0); }
  .main-content { margin-top: 60px; padding: 20px; }
}

.drop-down-enter-active, .drop-down-leave-active { transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1); }
.drop-down-enter-from, .drop-down-leave-to { opacity: 0; transform: translateY(-15px) translateX(-10px) scale(0.95); }
</style>