import { useAuthStore } from '~/stores/auth'

export default defineNuxtRouteMiddleware((to) => {
  const auth = useAuthStore()
  const token = useCookie('auth_token') // Читаем куку напрямую для надежности

  // Список публичных страниц
  const publicPages = ['/login', '/register']
  
  // Является ли страница публичной?
  const isPublic = publicPages.includes(to.path)

  // 1. Если токена НЕТ и страница НЕ публичная -> на вход
  if (!token.value && !isPublic) {
    return navigateTo('/login')
  }

  // 2. Если токен ЕСТЬ и мы идем на вход -> в дашборд
  if (token.value && isPublic) {
    return navigateTo('/dashboard') // Или '/'
  }

  // 3. ЗАЩИТА АДМИНКИ
  // Если идем в админку, но роль не admin -> выкидываем на дашборд
  if (to.path.startsWith('/dashboard/admin')) {
    // Если юзер еще не загрузился, но токен есть — пропускаем (плагин загрузит его)
    // Но если юзер уже загружен и он не админ — блокируем
    if (auth.user && auth.user.role !== 'admin') {
      return navigateTo('/dashboard')
    }
  }
})