<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

// Подключаем layout админки
definePageMeta({
  layout: 'admin'
})

interface User {
  id: number
  name: string
  email: string
  balance: number
  role: 'user' | 'admin'
}

const config = useRuntimeConfig()
const auth = useAuthStore()

const users = ref<User[]>([])
const searchQuery = ref('')
const isLoading = ref(true)
const errorMessage = ref('')

// Модалка
const isEditModalOpen = ref(false)
const editingUser = ref<User | null>(null)
const form = ref({ name: '', email: '', balance: 0, role: 'user' as 'user' | 'admin' })

// --- ЗАГРУЗКА ПОЛЬЗОВАТЕЛЕЙ ---
const fetchUsers = async () => {
  isLoading.value = true
  errorMessage.value = ''
  
  try {
    const { data, error } = await useFetch<any>('/api/admin/users', {
      baseURL: config.public.apiBase,
      headers: { 
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json'
      }
    })

    if (error.value) {
      console.error('API Error:', error.value)
      errorMessage.value = `Ошибка API: ${error.value.statusMessage || error.value.statusCode}`
      return
    }

    if (data.value) {
      // Обработка разных форматов ответа Laravel (прямой массив или { data: [...] })
      users.value = Array.isArray(data.value) ? data.value : (data.value.data || [])
    } else {
      errorMessage.value = 'Пустой ответ от сервера'
    }

  } catch (e: any) {
    console.error('Fetch error:', e)
    errorMessage.value = 'Ошибка соединения'
  } finally {
    isLoading.value = false
  }
}

// --- ПОИСК ---
const filteredUsers = computed(() => {
  if (!users.value) return []
  if (!searchQuery.value) return users.value
  
  const query = searchQuery.value.toLowerCase()
  return users.value.filter(u => 
    u.name?.toLowerCase().includes(query) || 
    u.email?.toLowerCase().includes(query) ||
    String(u.id).includes(query)
  )
})

// --- РЕДАКТИРОВАНИЕ ---
const openEditModal = (user: User) => {
  editingUser.value = user // Сохраняем ссылку на объект
  // Копируем данные в форму
  form.value = { 
    name: user.name, 
    email: user.email, 
    balance: Number(user.balance), // Гарантируем число
    role: user.role 
  }
  isEditModalOpen.value = true
}

const saveUser = async () => {
  if (!editingUser.value) return
  
  const userId = editingUser.value.id

  try {
    // Отправляем запрос на обновление
    await $fetch(`/api/admin/users/${userId}`, {
      baseURL: config.public.apiBase,
      method: 'PUT',
      body: form.value,
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    
    // 🔥 ИСПРАВЛЕНИЕ: Находим пользователя в локальном массиве и обновляем его
    const index = users.value.findIndex(u => u.id === userId)
    if (index !== -1) {
      // Создаем новый объект, чтобы Vue увидел изменения
      users.value[index] = { 
        ...users.value[index], 
        ...form.value,
        balance: Number(form.value.balance) // Убедимся, что баланс число
      }
    }
    
    isEditModalOpen.value = false
  } catch (e) {
    console.error(e)
    alert('Ошибка при сохранении. Проверьте консоль.')
  }
}

const deleteUser = async (id: number) => {
  if (!confirm('Удалить пользователя?')) return
  try {
    await $fetch(`/api/admin/users/${id}`, {
      baseURL: config.public.apiBase,
      method: 'DELETE',
      headers: { Authorization: `Bearer ${auth.token}` }
    })
    users.value = users.value.filter(u => u.id !== id)
  } catch (e) {
    alert('Ошибка при удалении')
  }
}

onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <div class="admin-page">
    
    <!-- HEADER -->
    <div class="page-header">
      <div class="title-group">
        <h1 class="title">База пользователей</h1>
        <div class="subtitle">Управление аккаунтами клиентов</div>
      </div>
      <div class="search-wrapper">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Поиск (ID, Email, Имя)..." 
          class="search-input"
        >
      </div>
    </div>

    <!-- TABLE -->
    <div class="table-container">
      <table class="users-table">
        <thead>
          <tr>
            <th width="60">ID</th>
            <th>Пользователь</th>
            <th>Баланс</th>
            <th>Роль</th>
            <th width="100" class="text-right">Действия</th>
          </tr>
        </thead>
        <tbody>
          <!-- 1. ЗАГРУЗКА -->
          <tr v-if="isLoading">
            <td colspan="5" class="state-cell">
              <div class="loader"></div> Загрузка базы...
            </td>
          </tr>

          <!-- 2. ОШИБКА -->
          <tr v-else-if="errorMessage">
            <td colspan="5" class="state-cell error-cell">
              <span>⚠️ {{ errorMessage }}</span>
              <button @click="fetchUsers" class="retry-btn">Повторить</button>
            </td>
          </tr>

          <!-- 3. ПУСТО -->
          <tr v-else-if="filteredUsers.length === 0">
            <td colspan="5" class="state-cell empty-cell">
              Пользователи не найдены
            </td>
          </tr>

          <!-- 4. СПИСОК -->
          <tr v-else v-for="user in filteredUsers" :key="user.id">
            <td class="id-cell">#{{ user.id }}</td>
            <td>
              <div class="user-info">
                <span class="u-name">{{ user.name }}</span>
                <span class="u-email">{{ user.email }}</span>
              </div>
            </td>
            <td class="balance-cell" :class="{ 'text-green': user.balance > 0 }">
              {{ user.balance }} ₽
            </td>
            <td>
              <span class="role-badge" :class="user.role">
                {{ user.role === 'admin' ? 'ADMIN' : 'USER' }}
              </span>
            </td>
            <td class="text-right">
              <div class="actions">
                <button @click="openEditModal(user)" class="btn-icon edit" title="Редактировать">✎</button>
                <button @click="deleteUser(user.id)" class="btn-icon delete" title="Удалить">✕</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- МОДАЛКА -->
    <div v-if="isEditModalOpen" class="modal-overlay" @click.self="isEditModalOpen = false">
      <div class="modal">
        <h2>Редактирование #{{ editingUser?.id }}</h2>
        <div class="form-group">
          <label>Имя</label><input v-model="form.name">
        </div>
        <div class="form-group">
          <label>Email</label><input v-model="form.email">
        </div>
        <div class="form-row">
          <div class="form-group half">
            <label>Баланс</label><input v-model.number="form.balance" type="number">
          </div>
          <div class="form-group half">
            <label>Роль</label>
            <select v-model="form.role">
              <option value="user">Клиент</option>
              <option value="admin">Администратор</option>
            </select>
          </div>
        </div>
        <div class="modal-actions">
          <button @click="isEditModalOpen = false" class="btn-cancel">Отмена</button>
          <button @click="saveUser" class="btn-save">Сохранить</button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.admin-page { width: 100%; max-width: 1200px; margin: 0 auto; }

/* Header */
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
.title { font-size: 24px; font-weight: 700; color: #fff; margin: 0; }
.subtitle { color: #666; font-size: 13px; margin-top: 4px; }

.search-input {
  background: #0f0f0f; border: 1px solid #222;
  padding: 10px 15px; border-radius: 8px; color: #fff; width: 300px; outline: none; transition: 0.2s;
}
.search-input:focus { border-color: #444; background: #141414; }

/* Table */
.table-container { background: #0a0a0a; border-radius: 12px; border: 1px solid #222; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
.users-table { width: 100%; border-collapse: collapse; text-align: left; }
.users-table th { background: #111; padding: 16px 24px; font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; border-bottom: 1px solid #222; }
.users-table td { padding: 16px 24px; border-bottom: 1px solid #1a1a1a; font-size: 14px; color: #ccc; vertical-align: middle; }
.users-table tr:last-child td { border-bottom: none; }
.users-table tr:hover td { background: rgba(255,255,255,0.01); }

/* Cells */
.id-cell { color: #444; font-family: monospace; font-size: 13px; }
.user-info { display: flex; flex-direction: column; }
.u-name { font-weight: 600; color: #fff; }
.u-email { font-size: 12px; color: #666; margin-top: 2px; }
.balance-cell { font-family: monospace; font-weight: 600; color: #888; }
.balance-cell.text-green { color: #4ade80; }
.text-right { text-align: right; }

/* Badges */
.role-badge { padding: 4px 8px; border-radius: 6px; font-size: 10px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; display: inline-block; }
.role-badge.user { background: rgba(255,255,255,0.05); color: #777; }
.role-badge.admin { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }

/* Actions */
.actions { display: flex; gap: 8px; justify-content: flex-end; }
.btn-icon { width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.btn-icon.edit { background: rgba(59, 130, 246, 0.1); color: #60a5fa; }
.btn-icon.edit:hover { background: rgba(59, 130, 246, 0.2); transform: translateY(-1px); }
.btn-icon.delete { background: rgba(239, 68, 68, 0.1); color: #f87171; }
.btn-icon.delete:hover { background: rgba(239, 68, 68, 0.2); transform: translateY(-1px); }

/* States */
.state-cell { text-align: center; padding: 60px !important; color: #666; }
.error-cell { color: #f87171; }
.retry-btn { margin-left: 10px; padding: 4px 12px; background: #333; border: none; color: white; border-radius: 4px; cursor: pointer; font-size: 12px; }
.retry-btn:hover { background: #444; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
.modal { background: #111; border: 1px solid #333; padding: 30px; width: 400px; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); animation: modalFade 0.3s ease; }
.modal h2 { margin-top: 0; color: #fff; font-size: 20px; margin-bottom: 25px; font-weight: 700; }
.form-group { margin-bottom: 15px; }
.form-row { display: flex; gap: 15px; }
.form-group.half { flex: 1; }
.form-group label { display: block; font-size: 11px; color: #666; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; }
.form-group input, .form-group select { width: 100%; box-sizing: border-box; background: #0a0a0a; border: 1px solid #222; color: #fff; padding: 12px; border-radius: 8px; outline: none; transition: 0.2s; font-size: 14px; }
.form-group input:focus, .form-group select:focus { border-color: #555; background: #111; }
.modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 30px; }
.btn-cancel { background: transparent; color: #888; border: none; cursor: pointer; font-size: 13px; transition: 0.2s; }
.btn-cancel:hover { color: #fff; }
.btn-save { background: #fff; color: #000; border: none; padding: 10px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; transition: 0.2s; }
.btn-save:hover { background: #e5e5e5; transform: translateY(-1px); }

@keyframes modalFade { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>