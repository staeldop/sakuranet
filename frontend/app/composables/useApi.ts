// app/composables/useApi.ts
import { useAuthStore } from '~/stores/auth'

// 1. $api — для вызова внутри функций (POST, PUT, DELETE)
export const $api = async <T>(request: string, options: any = {}) => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()

  // 🔥 ФИКС: Собираем полный URL вручную, чтобы Nuxt не думал, что это страница
  const url = request.startsWith('http') 
    ? request 
    : `${config.public.apiBase}${request}`
  
  return await $fetch<T>(url, {
    ...options,
    // baseURL здесь больше не нужен, мы склеили URL выше
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

// 2. useApiFetch — для загрузки данных при инициализации (GET)
export const useApiFetch = <T>(request: string, options: any = {}) => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()

  // 🔥 ФИКС: То же самое — жесткая привязка URL
  const url = request.startsWith('http') 
    ? request 
    : `${config.public.apiBase}${request}`

  return useFetch<T>(url, {
    ...options,
    // Добавляем уникальный ключ, чтобы Nuxt не путался при SSR
    key: url, 
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