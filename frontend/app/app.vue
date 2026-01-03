<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
const auth = useAuthStore()
</script>

<template>
  <div class="app-root">
    <Transition name="fade">
      <div v-if="auth.loading" class="global-loader">
        <div class="spinner"></div>
      </div>
    </Transition>

    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>

<style>
/* 🔥 ВЕРНУЛИ КАК БЫЛО: Убрали глобальный box-sizing */
.app-root {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background-color: #000000;
  color: #ffffff;
}

/* Глобальные стили */
body {
  margin: 0;
  background-color: #000;
  color: #fff;
  font-family: 'Segoe UI', sans-serif;
  overflow-x: hidden; /* Важно для анимаций */
}

.global-loader {
  position: fixed;
  top: 0; left: 0; width: 100%; height: 100%;
  background: #000;
  display: flex; align-items: center; justify-content: center;
  z-index: 9999;
}

/* Простой спиннер */
.spinner {
  width: 40px; height: 40px;
  border: 3px solid rgba(255,255,255,0.1);
  border-radius: 50%;
  border-top-color: #22c55e;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>