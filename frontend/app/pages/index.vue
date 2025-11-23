<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  middleware: [
    function (to, from) {
      const auth = useAuthStore()
      
      // Используем токен из стора (куки), как и везде в приложении
      if (auth.token) {
        // Если токен есть — летим в дашборд
        return navigateTo('/dashboard') 
      } else {
        // Если токена нет — на страницу входа
        return navigateTo('/login') 
      }
    }
  ]
})
</script>

<template>
  <div class="loading-screen">
    Загрузка...
  </div>
</template>

<style scoped>
.loading-screen {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #000;
  color: #fff;
}
</style>