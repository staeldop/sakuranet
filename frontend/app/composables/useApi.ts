import { useAuthStore } from '~/stores/auth'

// Переименовали $api -> useApi, чтобы совпадало с импортом в компонентах
export const useApi = async <T>(request: string, options: any = {}) => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()

  // 🔥 Собираем полный URL
  const url = request.startsWith('http') 
    ? request 
    : `${config.public.apiBase}${request}`
  
  return await $fetch<T>(url, {
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

// useApiFetch оставляем как есть, он для GET запросов при загрузке страницы
export const useApiFetch = <T>(request: string, options: any = {}) => {
  const config = useRuntimeConfig()
  const auth = useAuthStore()

  const url = request.startsWith('http') 
    ? request 
    : `${config.public.apiBase}${request}`

  return useFetch<T>(url, {
    ...options,
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