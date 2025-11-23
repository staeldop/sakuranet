// app/composables/useApi.ts
import { useAuthStore } from '~/stores/auth'

// 1. $api — для вызова по КЛИКУ (POST, PUT, DELETE)
// Используй это в handleSubmit, deleteProduct и т.д.
export const $api = async <T>(request: string, options: any = {}) => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()
  
  return await $fetch<T>(request, {
    baseURL: config.public.apiBase,
    ...options,
    headers: {
      ...(options.headers || {}),
      ...(auth.token ? { Authorization: `Bearer ${auth.token}` } : {}),
    },
  })
}

// 2. useApiFetch — ТОЛЬКО для загрузки данных при старте (GET)
// Используй это внутри onMounted или просто в script setup
export const useApiFetch = <T>(url: string, options: any = {}) => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()

  return useFetch<T>(url, {
    baseURL: config.public.apiBase,
    ...options,
    headers: {
      ...(options.headers || {}),
      ...(auth.token ? { Authorization: `Bearer ${auth.token}` } : {}),
    },
  })
}