// app/composables/useApi.ts
import { useAuthStore } from '~/stores/auth'

// 1. $api — для вызова внутри функций (POST, PUT, DELETE, или отложенный GET)
// Используй это внутри функций (const fetch = async () => { await $api(...) })
export const $api = async <T>(request: string, options: any = {}) => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()
  
  return await $fetch<T>(request, {
    baseURL: config.public.apiBase,
    ...options,
    headers: {
      'Accept': 'application/json', // Гарантирует, что Laravel вернет JSON при ошибке
      ...(options.headers || {}),
      ...(auth.token ? { Authorization: `Bearer ${auth.token}` } : {}),
    },
    // Если токен невалиден — выкидываем пользователя
    onResponseError({ response }) {
      if (response.status === 401) {
        auth.logout()
      }
    }
  })
}

// 2. useApiFetch — ТОЛЬКО для загрузки данных при старте компонента (Reactive)
// Используй это прямо в setup: const { data } = await useApiFetch(...)
export const useApiFetch = <T>(url: string, options: any = {}) => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()

  return useFetch<T>(url, {
    baseURL: config.public.apiBase,
    ...options,
    headers: {
      'Accept': 'application/json',
      ...(options.headers || {}),
      ...(auth.token ? { Authorization: `Bearer ${auth.token}` } : {}),
    },
    onResponseError({ response }) {
      if (response.status === 401) {
        auth.logout()
      }
    }
  })
}