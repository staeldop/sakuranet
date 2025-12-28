<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
// ИСПРАВЛЕНО: заменено $api на useApi
import { useApi, useApiFetch } from '~/composables/useApi'

// Иконки
import IconSearch from '~/assets/icons/search.svg?component'
import IconEdit from '~/assets/icons/ticket.svg?component'
import IconTrash from '~/assets/icons/trash.svg?component'

definePageMeta({ layout: 'admin' })

interface User {
  id: number
  name: string
  email: string
  balance: number
  role: 'user' | 'admin'
  avatar?: string
}

const auth = useAuthStore()
const users = ref<User[]>([])
const searchQuery = ref('')
const isLoading = ref(true)
const errorMessage = ref('')

// Модалка
const isEditModalOpen = ref(false)
const editingUser = ref<User | null>(null)
const form = ref({ name: '', email: '', balance: 0, role: 'user' as 'user' | 'admin' })

// --- ЗАГРУЗКА ---
const fetchUsers = async () => {
  isLoading.value = true
  errorMessage.value = ''
  
  try {
    const { data, error } = await useApiFetch<any>('/api/admin/users')

    if (error.value) {
      console.error('API Error:', error.value)
      errorMessage.value = `Ошибка: ${error.value.statusCode}`
      return
    }

    if (data.value) {
      users.value = Array.isArray(data.value) ? data.value : (data.value.data || [])
    }
  } catch (e) {
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
  editingUser.value = user
  form.value = { 
    name: user.name, 
    email: user.email, 
    balance: Number(user.balance),
    role: user.role 
  }
  isEditModalOpen.value = true
}

const saveUser = async () => {
  if (!editingUser.value) return
  const userId = editingUser.value.id

  try {
    // ИСПРАВЛЕНО: заменено $api на useApi
    await useApi(`/api/admin/users/${userId}`, {
      method: 'PUT',
      body: form.value
    })
    
    // Локальное обновление
    const index = users.value.findIndex(u => u.id === userId)
    if (index !== -1) {
      users.value[index] = { 
        ...users.value[index], 
        ...form.value,
        balance: Number(form.value.balance)
      }
    }
    isEditModalOpen.value = false
  } catch (e) {
    alert('Ошибка при сохранении')
  }
}

const deleteUser = async (id: number) => {
  if (!confirm('Удалить пользователя?')) return
  try {
    // ИСПРАВЛЕНО: заменено $api на useApi
    await useApi(`/api/admin/users/${id}`, { method: 'DELETE' })
    users.value = users.value.filter(u => u.id !== id)
  } catch (e) {
    alert('Ошибка при удалении')
  }
}

// ... остальной код (хелперы и template) без изменений ...
const getInitials = (name: string) => {
  return name ? name.substring(0, 2).toUpperCase() : '??'
}

const getAvatarColor = (id: number) => {
  const colors = ['bg-blue-500', 'bg-purple-500', 'bg-green-500', 'bg-pink-500', 'bg-yellow-500']
  return colors[id % colors.length]
}

onMounted(fetchUsers)
</script>

<template>
  <div class="admin-shell">
    <!-- Фоны (как на логине) -->
    <div class="glow glow-1" />
    <div class="glow glow-2" />
    
    <div class="content-wrapper">
      
      <!-- ХЕДЕР -->
      <div class="page-header">
        <div class="title-group">
          <div class="auth-badge mb-2">
            <span class="badge-dot" />
            <span class="badge-text">USERS DATABASE</span>
          </div>
          <h1 class="title">Пользователи</h1>
          <div class="subtitle">Управление клиентами и балансом</div>
        </div>
        
        <!-- ПОИСК -->
        <div class="search-wrapper">
          <div class="search-icon">🔍</div>
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Поиск по ID, Имени, Email..." 
            class="search-input"
          >
        </div>
      </div>

      <!-- ТАБЛИЦА -->
      <div class="glass-card table-container">
        <div class="card-glow-top" />
        
        <table class="data-table">
          <thead>
            <tr>
              <th width="80">ID</th>
              <th>Пользователь</th>
              <th>Баланс</th>
              <th>Роль</th>
              <th width="100" class="text-right">Действия</th>
            </tr>
          </thead>
          <tbody>
            <!-- LOADER -->
            <tr v-if="isLoading">
              <td colspan="5" class="state-cell">
                <div class="loader"></div> Загрузка...
              </td>
            </tr>

            <!-- EMPTY -->
            <tr v-else-if="filteredUsers.length === 0">
              <td colspan="5" class="state-cell empty-cell">
                Ничего не найдено
              </td>
            </tr>

            <!-- ROWS -->
            <tr v-else v-for="user in filteredUsers" :key="user.id" class="data-row">
              <td class="id-cell">#{{ user.id }}</td>
              
              <td>
                <div class="user-cell">
                  <!-- Аватар -->
                  <div class="avatar" :class="getAvatarColor(user.id)">
                    {{ getInitials(user.name) }}
                  </div>
                  <div class="user-info">
                    <span class="u-name">{{ user.name }}</span>
                    <span class="u-email">{{ user.email }}</span>
                  </div>
                </div>
              </td>

              <td class="balance-cell">
                <span :class="user.balance > 0 ? 'text-green-400' : 'text-gray-500'">
                  {{ new Intl.NumberFormat('ru-RU').format(user.balance) }} ₽
                </span>
              </td>

              <td>
                <span class="role-badge" :class="user.role">
                  {{ user.role === 'admin' ? 'ADMIN' : 'USER' }}
                </span>
              </td>

              <td class="text-right">
                <div class="actions">
                  <button @click="openEditModal(user)" class="btn-icon edit" title="Редактировать">
                    <IconEdit class="w-4 h-4" />
                  </button>
                  <button @click="deleteUser(user.id)" class="btn-icon delete" title="Удалить">
                    <IconTrash class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- МОДАЛКА -->
      <Transition name="modal-fade">
        <div v-if="isEditModalOpen" class="modal-overlay" @click.self="isEditModalOpen = false">
          <div class="glass-card modal-card">
            <div class="card-glow-top" />
            
            <h2 class="modal-title">Редактирование <span class="text-accent">#{{ editingUser?.id }}</span></h2>
            
            <form @submit.prevent="saveUser" class="modal-form">
              <div class="form-group">
                <label class="field-label">Имя</label>
                <input v-model="form.name" class="glass-input" placeholder="Имя">
              </div>
              
              <div class="form-group">
                <label class="field-label">Email</label>
                <input v-model="form.email" class="glass-input" placeholder="Email">
              </div>
              
              <div class="form-row">
                <div class="form-group half">
                  <label class="field-label">Баланс (₽)</label>
                  <input v-model.number="form.balance" type="number" class="glass-input">
                </div>
                <div class="form-group half">
                  <label class="field-label">Роль</label>
                  <select v-model="form.role" class="glass-input">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                  </select>
                </div>
              </div>
              
              <div class="modal-actions">
                <button type="button" @click="isEditModalOpen = false" class="ghost-btn">Отмена</button>
                <button type="submit" class="primary-btn">Сохранить</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>

    </div>
  </div>
</template>

<style scoped>
/* === ОСНОВА (КАК ВЕЗДЕ) === */
.admin-shell { position: relative; min-height: 100%; width: 100%; overflow: hidden; font-family: 'Inter', sans-serif; padding-bottom: 40px; }
.content-wrapper { position: relative; z-index: 10; max-width: 1200px; margin: 0 auto; padding: 0 20px; }

/* Glow Effects */
.glow { position: absolute; width: 600px; height: 600px; border-radius: 50%; filter: blur(100px); opacity: 0.15; pointer-events: none; z-index: 0; }
.glow-1 { top: -10%; left: -10%; background: radial-gradient(circle, #ff0055, transparent 70%); }
.glow-2 { bottom: -10%; right: 20%; background: radial-gradient(circle, #0055ff, transparent 70%); }

/* === ХЕДЕР === */
.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin: 30px 0; flex-wrap: wrap; gap: 20px; }
.title { font-size: 28px; font-weight: 700; color: #fff; margin: 0; letter-spacing: -0.5px; }
.subtitle { color: #888; font-size: 14px; margin-top: 4px; }

.auth-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 100px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); width: fit-content; }
.badge-dot { width: 6px; height: 6px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 8px #22c55e; }
.badge-text { font-size: 10px; font-weight: 700; letter-spacing: 1px; color: #aaa; }

/* === ПОИСК === */
.search-wrapper { position: relative; width: 300px; }
.search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #666; font-size: 14px; pointer-events: none; }
.search-input { 
  width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1);
  padding: 12px 12px 12px 40px; border-radius: 12px; color: #fff; outline: none; transition: 0.2s; font-size: 14px; box-sizing: border-box; 
}
.search-input:focus { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.05); }

/* === КАРТОЧКА ТАБЛИЦЫ === */
.glass-card { 
  position: relative; background: rgba(20, 20, 20, 0.6); border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(20px); border-radius: 20px; overflow: hidden; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3); 
}
.card-glow-top { position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); }

/* === ТАБЛИЦА === */
.data-table { width: 100%; border-collapse: collapse; text-align: left; }
.data-table th { padding: 18px 24px; font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.05); }
.data-table td { padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px; color: #ccc; vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-row { transition: background 0.2s; }
.data-row:hover { background: rgba(255,255,255,0.03); }

.id-cell { color: #444; font-family: monospace; font-size: 13px; }

/* User Cell (Avatar + Info) */
.user-cell { display: flex; align-items: center; gap: 12px; }
.avatar { 
  width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
  font-weight: 700; color: white; font-size: 12px; text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}
/* Avatar Colors (Tailwind classes simulation) */
.bg-blue-500 { background: #3b82f6; }
.bg-purple-500 { background: #a855f7; }
.bg-green-500 { background: #22c55e; }
.bg-pink-500 { background: #ec4899; }
.bg-yellow-500 { background: #eab308; }

.user-info { display: flex; flex-direction: column; }
.u-name { font-weight: 600; color: #fff; font-size: 14px; }
.u-email { font-size: 12px; color: #666; margin-top: 2px; }

.balance-cell { font-family: monospace; font-weight: 600; }
.text-right { text-align: right; }

/* Badges */
.role-badge { padding: 4px 10px; border-radius: 100px; font-size: 10px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; display: inline-block; }
.role-badge.user { background: rgba(255,255,255,0.05); color: #888; border: 1px solid rgba(255,255,255,0.05); }
.role-badge.admin { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); box-shadow: 0 0 10px rgba(239, 68, 68, 0.1); }

/* Actions */
.actions { display: flex; gap: 8px; justify-content: flex-end; }
.btn-icon { 
  width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; 
  display: flex; align-items: center; justify-content: center; transition: 0.2s; background: transparent; color: #555; 
}
.btn-icon:hover { background: rgba(255,255,255,0.05); color: #fff; }
.btn-icon.edit:hover { color: #60a5fa; background: rgba(96, 165, 250, 0.1); }
.btn-icon.delete:hover { color: #f87171; background: rgba(248, 113, 113, 0.1); }

/* MODAL */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 2000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
.modal-card { width: 100%; max-width: 420px; padding: 30px; background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); }
.modal-title { margin: 0 0 25px 0; color: #fff; font-size: 20px; font-weight: 700; }
.text-accent { color: #22c55e; }

.form-group { margin-bottom: 16px; }
.form-row { display: flex; gap: 16px; }
.form-group.half { flex: 1; }
.field-label { display: block; font-size: 11px; color: #888; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; }

.glass-input { 
  width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1);
  padding: 10px 14px; border-radius: 10px; color: #fff; outline: none; transition: 0.2s; font-size: 14px; box-sizing: border-box;
}
.glass-input:focus { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.05); }
select.glass-input { appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 10px center; background-size: 16px; }

.modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 30px; }
.primary-btn, .ghost-btn { padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; border: none; }
.primary-btn { background: #fff; color: #000; }
.primary-btn:hover { background: #e5e5e5; transform: translateY(-1px); }
.ghost-btn { background: transparent; color: #888; }
.ghost-btn:hover { color: #fff; background: rgba(255,255,255,0.05); }

.state-cell { text-align: center; padding: 60px !important; color: #666; }
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s, transform 0.3s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(0.95); }
</style>