import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

interface User {
  id: number
  name: string
  email: string
  balance: number
  role: string
  avatar?: string
}

export const useAuthStore = defineStore('auth', () => {
  const config = useRuntimeConfig()
  
  // --- КУКА С ТОКЕНОМ ---
  const token = useCookie<string | null>('auth_token', {
    maxAge: 60 * 60 * 24 * 7,
    watch: true,
    sameSite: 'lax' 
  })

  // 🔥 ФИКС: Проверка на "мусорные" значения в куках
  // Если там записалось "null", "undefined" или "false" строкой — чистим
  if (token.value === 'null' || token.value === 'undefined' || token.value === 'false') {
    token.value = null
  }

  const user = ref<User | null>(null)
  const loading = ref(false)

  // --- GETTERS ---
  // Дополнительная защита: считаем авторизованным только если токен — непустая строка
  const isAuthenticated = computed(() => {
    return typeof token.value === 'string' && token.value.length > 10
  })
  
  const formattedBalance = computed(() => {
    return new Intl.NumberFormat('ru-RU').format(Number(user.value?.balance || 0))
  })

  const isAdmin = computed(() => user.value?.role === 'admin')

  // --- ACTIONS ---

  async function fetchUser() {
    // Если токена нет или он короткий (битый) — выходим
    if (!token.value || token.value.length < 10) {
      token.value = null
      user.value = null
      return
    }

    loading.value = true
    try {
      const response = await $fetch<any>('/api/user', {
        baseURL: config.public.apiBase,
        headers: {
          Authorization: `Bearer ${token.value}`,
          Accept: 'application/json'
        },
        retry: 1 
      })
      
      const userData = response.data || response
      
      if (userData && userData.id) {
        user.value = userData
      } else {
        throw new Error('Invalid user data')
      }

    } catch (error: any) {
      console.error('Fetch user error:', error)
      
      // Если ошибка авторизации или любые проблемы с данными — сбрасываем всё
      token.value = null
      user.value = null
      
      if (process.client) {
        navigateTo('/login')
      }
    } finally {
      loading.value = false
    }
  }

  async function uploadAvatar(file: File) {
    const formData = new FormData()
    formData.append('avatar', file)

    try {
      const response: any = await $fetch('/api/user/avatar', {
        baseURL: config.public.apiBase,
        method: 'POST',
        body: formData,
        headers: { Authorization: `Bearer ${token.value}` }
      })
      const userData = response.data || response.user || response
      if (userData) user.value = userData
    } catch (error) {
      console.error('Avatar upload error:', error)
    }
  }

  async function deleteAvatar() {
    try {
      const response: any = await $fetch('/api/user/avatar', {
        baseURL: config.public.apiBase,
        method: 'DELETE',
        headers: { Authorization: `Bearer ${token.value}` }
      })
      const userData = response.data || response.user || response
      if (userData) user.value = userData
    } catch (error) {
      console.error('Avatar delete error:', error)
    }
  }

  function logout() {
    token.value = null
    user.value = null
    
    if (process.client) {
      // Жесткая очистка куки через JS
      document.cookie = 'auth_token=; Max-Age=0; path=/; domain=' + window.location.hostname
      navigateTo('/login')
    }
  }

  return {
    token,
    user,
    loading,
    isAuthenticated,
    formattedBalance,
    isAdmin,
    fetchUser,
    uploadAvatar,
    deleteAvatar,
    logout
  }
})