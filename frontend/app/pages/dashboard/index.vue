<script setup lang="ts">
// --- ЛОГИКА ---
const config = useRuntimeConfig()
const token = useAuthToken()

// Подключаем наш макет с сайдбаром
definePageMeta({
  layout: 'dashboard' 
})

// Простой запрос к API для проверки связи
const { data, error } = await useFetch("/api/ping", {
  baseURL: config.public.apiBase,
  lazy: true,
  server: false,
})

// Логика выхода
const logout = async () => {
  token.value = null
  await navigateTo("/login")
}
</script>

<template>
  <div class="clean-workspace">
    
    <div class="api-box">
      <h3>📡 API Connection Status</h3>
      
      <div class="status-row">
        <span>Status:</span>
        <span class="value" :style="{ color: data?.status === 'ok' ? '#4caf50' : '#f44336' }">
          {{ data?.status || 'Loading...' }}
        </span>
      </div>

      <div class="status-row">
        <span>Service:</span>
        <span class="value">{{ data?.service || '...' }}</span>
      </div>

      <div v-if="error" class="error-msg">
        ❌ Error: {{ error.message }}
      </div>
    </div>

    <button @click="logout" class="simple-btn">
      Logout
    </button>

  </div>
</template>

<style scoped>
/* --- СТИЛИ --- */
.clean-workspace {
  padding-top: 20px;
  max-width: 400px;
}

.api-box {
  background: #111;
  border: 1px solid #333;
  padding: 20px;
  border-radius: 8px;
  font-family: monospace; /* Шрифт как в коде */
  margin-bottom: 20px;
}

h3 {
  margin-top: 0;
  color: #888;
  font-size: 14px;
  text-transform: uppercase;
  border-bottom: 1px solid #333;
  padding-bottom: 10px;
  margin-bottom: 15px;
}

.status-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  color: #ccc;
}

.value {
  font-weight: bold;
  color: #fff;
}

.error-msg {
  color: #ff5555;
  margin-top: 10px;
  font-size: 12px;
}

.simple-btn {
  background: #222;
  color: #ccc;
  border: 1px solid #444;
  padding: 8px 16px;
  cursor: pointer;
  border-radius: 4px;
}

.simple-btn:hover {
  background: #333;
  color: white;
}
</style>