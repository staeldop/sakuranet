<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
// Вся логика загрузки пользователя теперь находится в plugins/auth.ts.
// Здесь нам ничего вызывать не нужно, просто следим за состоянием загрузки.
</script>

<template>
  <div>
    <!-- Показываем глобальный загрузчик, пока плагин или store грузят данные -->
    <Transition name="fade">
      <div v-if="auth.loading" class="global-loader">
        <div class="spinner"></div>
      </div>
    </Transition>

    <!-- Основной макет приложения -->
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>

<style>
/* Глобальные стили */
body {
  margin: 0;
  background-color: #000;
  color: #fff;
  font-family: 'Segoe UI', sans-serif;
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
  border-top-color: #fff;
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