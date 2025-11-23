<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { $api } from '~/composables/useApi'

// Подключаем layout админки
definePageMeta({ layout: 'admin' })

// --- СОСТОЯНИЕ ---
const products = ref([])
const searchQuery = ref('')
const isLoading = ref(true)

// Модалка
const isModalOpen = ref(false)
const isEditing = ref(false)
const editId = ref<number | null>(null)

// Данные формы по умолчанию
const defaultForm = {
  name: '',
  category: 'gaming',
  country: 'RU',
  gameType: 'gaming',
  price: '',
  specs: [
    { key: 'CPU', value: 'Ryzen 5', icon: 'cpu' },
    { key: 'RAM', value: '8 GB', icon: 'ram' },
    { key: 'Disk', value: '50 GB NVMe', icon: 'disk' }
  ]
}

const form = ref(JSON.parse(JSON.stringify(defaultForm)))

// --- ЗАГРУЗКА ---
const fetchProducts = async () => {
  isLoading.value = true
  try {
    const data = await $api<any[]>('/api/products')
    products.value = data
  } catch (e) {
    console.error('Ошибка загрузки:', e)
  } finally {
    isLoading.value = false
  }
}

// --- ПОИСК ---
const filteredProducts = computed(() => {
  if (!products.value) return []
  if (!searchQuery.value) return products.value
  
  const query = searchQuery.value.toLowerCase()
  return products.value.filter((p: any) => 
    p.name?.toLowerCase().includes(query) || 
    p.category?.toLowerCase().includes(query) ||
    String(p.id).includes(query)
  )
})

// --- МОДАЛКА И ФОРМА ---
const openCreateModal = () => {
  form.value = JSON.parse(JSON.stringify(defaultForm))
  isEditing.value = false
  editId.value = null
  isModalOpen.value = true
}

const openEditModal = (product: any) => {
  form.value = {
    name: product.name,
    category: product.category,
    country: product.country || 'RU',
    gameType: product.game_type || 'gaming',
    price: product.price,
    // Загружаем specs или пустой массив
    specs: product.specs ? JSON.parse(JSON.stringify(product.specs)) : []
  }
  isEditing.value = true
  editId.value = product.id
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

const handleSubmit = async () => {
  try {
    const payload = {
      name: form.value.name,
      category: form.value.category,
      country: form.value.category === 'gaming' ? form.value.country : null,
      game_type: form.value.category === 'gaming' ? form.value.gameType : null,
      price: Number(form.value.price),
      specs: form.value.specs
    }

    if (isEditing.value && editId.value) {
      await $api(`/api/admin/products/${editId.value}`, { method: 'PUT', body: payload })
    } else {
      await $api('/api/admin/products', { method: 'POST', body: payload })
    }

    closeModal()
    await fetchProducts()
  } catch (e: any) {
    console.error(e)
    const msg = e.response?._data?.message || e.message || 'Ошибка'
    alert('Ошибка сервера: ' + msg)
  }
}

const deleteProduct = async (id: number) => {
  if(!confirm('Удалить этот товар?')) return
  try {
    await $api(`/api/admin/products/${id}`, { method: 'DELETE' })
    await fetchProducts()
  } catch (e) { 
    alert('Ошибка удаления') 
  }
}

// Хелперы для specs
const addAttr = () => form.value.specs.push({ key: '', value: '', icon: 'cpu' })
const removeAttr = (idx: number) => form.value.specs.splice(idx, 1)

onMounted(fetchProducts)
</script>

<template>
  <div class="admin-shell">
    <!-- Фоны и подсветка -->
    <div class="glow glow-1" />
    <div class="glow glow-2" />
    
    <div class="content-wrapper">
      <!-- HEADER -->
      <div class="page-header">
        <div class="title-group">
          <div class="auth-badge mb-2">
            <span class="badge-dot" />
            <span class="badge-text">ADMIN PANEL</span>
          </div>
          <h1 class="title">База товаров</h1>
          <div class="subtitle">Управление тарифами и услугами</div>
        </div>
        
        <div class="header-actions">
          <!-- Поиск -->
          <div class="search-wrapper">
            <div class="search-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </div>
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Поиск товара..." 
              class="search-input"
            >
          </div>
          <!-- Кнопка Добавить -->
          <button @click="openCreateModal" class="btn-add">
            <span>+ Новый товар</span>
          </button>
        </div>
      </div>

      <!-- TABLE CARD -->
      <div class="glass-card table-container">
        <div class="card-glow-top" />
        
        <table class="data-table">
          <thead>
            <tr>
              <th width="60">ID</th>
              <th>Название</th>
              <th>Категория</th>
              <th>Цена</th>
              <th>Детали</th>
              <th width="100" class="text-right">Действия</th>
            </tr>
          </thead>
          <tbody>
            <!-- 1. ЗАГРУЗКА -->
            <tr v-if="isLoading">
              <td colspan="6" class="state-cell">
                <div class="loader"></div> Загрузка...
              </td>
            </tr>

            <!-- 2. ПУСТО -->
            <tr v-else-if="filteredProducts.length === 0">
              <td colspan="6" class="state-cell empty-cell">
                Товары не найдены
              </td>
            </tr>

            <!-- 3. СПИСОК -->
            <tr v-else v-for="p in filteredProducts" :key="p.id" class="data-row">
              <td class="id-cell">#{{ p.id }}</td>
              <td>
                <div class="item-info">
                  <span class="item-name">{{ p.name }}</span>
                </div>
              </td>
              <td>
                <span class="category-badge" :class="p.category">
                  {{ p.category.toUpperCase() }}
                </span>
              </td>
              <td class="price-cell">
                {{ new Intl.NumberFormat('ru-RU').format(p.price) }} ₽
              </td>
              <td class="details-cell">
                <div class="tags">
                  <span v-if="p.country" class="mini-tag">{{ p.country }}</span>
                  <span v-if="p.game_type" class="mini-tag">{{ p.game_type }}</span>
                  <span class="mini-tag specs-count">{{ p.specs?.length || 0 }} specs</span>
                </div>
              </td>
              <td class="text-right">
                <div class="actions">
                  <button @click="openEditModal(p)" class="btn-icon edit" title="Редактировать">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </button>
                  <button @click="deleteProduct(p.id)" class="btn-icon delete" title="Удалить">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- МОДАЛКА (СОЗДАНИЕ/РЕДАКТИРОВАНИЕ) -->
      <Transition name="modal-fade">
        <div v-if="isModalOpen" class="modal-overlay" @click.self="closeModal">
          <div class="glass-card modal-card">
            <div class="card-glow-top" />
            
            <h2 class="modal-title">
              {{ isEditing ? `Редактирование #${editId}` : 'Новый товар' }}
            </h2>
            
            <form @submit.prevent="handleSubmit" class="modal-form">
              <!-- Основные поля -->
              <div class="form-row">
                <div class="form-group half">
                  <label class="field-label">Название</label>
                  <input v-model="form.name" class="glass-input" placeholder="Minecraft Start" required>
                </div>
                <div class="form-group half">
                  <label class="field-label">Цена (₽)</label>
                  <input v-model="form.price" type="number" class="glass-input" placeholder="499" required>
                </div>
              </div>

              <!-- Категории -->
              <div class="form-row">
                <div class="form-group third">
                  <label class="field-label">Категория</label>
                  <select v-model="form.category" class="glass-input">
                    <option value="gaming">Gaming</option>
                    <option value="virtual">Virtual</option>
                    <option value="dedicated">Dedicated</option>
                  </select>
                </div>
                <div class="form-group third">
                  <label class="field-label">Страна</label>
                  <select v-model="form.country" class="glass-input" :disabled="form.category !== 'gaming'">
                    <option value="RU">Россия</option>
                    <option value="DE">Германия</option>
                    <option value="FI">Финляндия</option>
                  </select>
                </div>
                <div class="form-group third">
                  <label class="field-label">Тип</label>
                  <select v-model="form.gameType" class="glass-input" :disabled="form.category !== 'gaming'">
                    <option value="gaming">Game</option>
                    <option value="coding">Code</option>
                  </select>
                </div>
              </div>

              <!-- Характеристики -->
              <div class="specs-section">
                <div class="specs-header">
                  <span class="specs-title">Характеристики</span>
                  <button type="button" @click="addAttr" class="btn-mini-add">+ Добавить</button>
                </div>
                
                <div class="specs-list-scroll">
                  <div v-for="(attr, idx) in form.specs" :key="idx" class="spec-row">
                    <input v-model="attr.key" placeholder="CPU" class="glass-input spec-key" />
                    <input v-model="attr.value" placeholder="Core i9" class="glass-input spec-val" />
                    <select v-model="attr.icon" class="glass-input spec-icon">
                      <option value="cpu">CPU</option>
                      <option value="ram">RAM</option>
                      <option value="disk">Disk</option>
                      <option value="code">Code</option>
                    </select>
                    <button type="button" @click="removeAttr(idx)" class="btn-icon delete small">✕</button>
                  </div>
                  <div v-if="form.specs.length === 0" class="no-specs">Нет характеристик</div>
                </div>
              </div>
              
              <!-- Кнопки -->
              <div class="modal-actions">
                <button type="button" @click="closeModal" class="ghost-btn">Отмена</button>
                <button type="submit" class="primary-btn">
                  {{ isEditing ? 'Сохранить' : 'Создать' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>

    </div>
  </div>
</template>

<style scoped>
/* === ОБЩИЙ ЛЕЙАУТ (Копия из users.vue) === */
.admin-shell {
  position: relative; min-height: 100%; width: 100%;
  overflow: hidden; font-family: 'Inter', sans-serif; padding-bottom: 40px;
}
.content-wrapper {
  position: relative; z-index: 10; max-width: 1200px; margin: 0 auto; padding: 0 20px;
}
.glow {
  position: absolute; width: 600px; height: 600px; border-radius: 50%;
  filter: blur(100px); opacity: 0.15; pointer-events: none; z-index: 0;
}
.glow-1 { top: -10%; left: -10%; background: radial-gradient(circle, #ff0055, transparent 70%); }
.glow-2 { bottom: -10%; right: 20%; background: radial-gradient(circle, #0055ff, transparent 70%); }

/* === HEADER === */
.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin: 30px 0; flex-wrap: wrap; gap: 20px; }
.title { font-size: 28px; font-weight: 700; color: #fff; margin: 0; letter-spacing: -0.5px; }
.subtitle { color: #888; font-size: 14px; margin-top: 4px; }
.auth-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 100px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); width: fit-content; }
.badge-dot { width: 6px; height: 6px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 8px #22c55e; }
.badge-text { font-size: 10px; font-weight: 700; letter-spacing: 1px; color: #aaa; }

.header-actions { display: flex; gap: 12px; }
.btn-add { display: flex; align-items: center; background: #fff; color: #000; border: none; padding: 0 20px; border-radius: 12px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.2s; height: 42px; }
.btn-add:hover { background: #e5e5e5; transform: translateY(-1px); }

/* === SEARCH === */
.search-wrapper { position: relative; width: 260px; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #666; pointer-events: none; }
.search-input { width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 11px 12px 11px 38px; border-radius: 12px; color: #fff; outline: none; transition: 0.2s; font-size: 14px; box-sizing: border-box; height: 42px; }
.search-input:focus { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.05); }

/* === CARD & TABLE === */
.glass-card { position: relative; background: rgba(20, 20, 20, 0.6); border: 1px solid rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border-radius: 20px; overflow: hidden; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3); }
.card-glow-top { position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); }

.data-table { width: 100%; border-collapse: collapse; text-align: left; }
.data-table th { padding: 18px 24px; font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.05); }
.data-table td { padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px; color: #ccc; vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-row { transition: background 0.2s; }
.data-row:hover { background: rgba(255,255,255,0.02); }

.id-cell { color: #444; font-family: monospace; font-size: 13px; }
.item-name { font-weight: 600; color: #fff; }
.price-cell { font-family: monospace; font-weight: 600; color: #4ade80; }
.text-right { text-align: right; }

.category-badge { padding: 4px 10px; border-radius: 100px; font-size: 10px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; display: inline-block; border: 1px solid rgba(255,255,255,0.1); }
.category-badge.gaming { background: rgba(168, 85, 247, 0.1); color: #d8b4fe; border-color: rgba(168, 85, 247, 0.2); }
.category-badge.virtual { background: rgba(59, 130, 246, 0.1); color: #93c5fd; border-color: rgba(59, 130, 246, 0.2); }
.category-badge.dedicated { background: rgba(234, 179, 8, 0.1); color: #fde047; border-color: rgba(234, 179, 8, 0.2); }

.tags { display: flex; gap: 6px; flex-wrap: wrap; }
.mini-tag { font-size: 10px; background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px; color: #888; }
.specs-count { color: #555; }

/* ACTIONS */
.actions { display: flex; gap: 8px; justify-content: flex-end; }
.btn-icon { width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; background: transparent; color: #555; }
.btn-icon:hover { background: rgba(255,255,255,0.05); color: #fff; }
.btn-icon.edit:hover { color: #60a5fa; background: rgba(96, 165, 250, 0.1); }
.btn-icon.delete:hover { color: #f87171; background: rgba(248, 113, 113, 0.1); }
.btn-icon.small { width: 24px; height: 24px; }

/* MODAL */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 2000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
.modal-card { width: 100%; max-width: 550px; padding: 30px; background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); max-height: 90vh; overflow-y: auto; }
.modal-title { margin: 0 0 25px 0; color: #fff; font-size: 20px; font-weight: 700; }

.form-row { display: flex; gap: 16px; margin-bottom: 16px; }
.form-group.half { flex: 1; }
.form-group.third { flex: 1; }
.field-label { display: block; font-size: 11px; color: #888; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; }

.glass-input { width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 10px 14px; border-radius: 10px; color: #fff; outline: none; transition: 0.2s; font-size: 14px; box-sizing: border-box; }
.glass-input:focus { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.05); }
select.glass-input { appearance: none; }

/* SPECS EDITOR */
.specs-section { background: rgba(255,255,255,0.02); border-radius: 12px; padding: 15px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 20px; }
.specs-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.specs-title { font-size: 12px; font-weight: 600; color: #ccc; }
.btn-mini-add { background: none; border: none; color: #4ade80; font-size: 11px; font-weight: 700; cursor: pointer; text-transform: uppercase; }
.specs-list-scroll { max-height: 150px; overflow-y: auto; padding-right: 5px; }
.spec-row { display: flex; gap: 8px; margin-bottom: 8px; align-items: center; }
.spec-key { width: 30%; }
.spec-val { flex-grow: 1; }
.spec-icon { width: 80px; }
.no-specs { font-size: 12px; color: #555; text-align: center; padding: 10px; }

.modal-actions { display: flex; justify-content: flex-end; gap: 12px; }
.primary-btn { background: #fff; color: #000; padding: 10px 24px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; }
.primary-btn:hover { background: #e5e5e5; }
.ghost-btn { background: transparent; color: #888; padding: 10px 20px; border-radius: 10px; font-size: 13px; cursor: pointer; border: none; transition: 0.2s; }
.ghost-btn:hover { color: #fff; }

.state-cell { text-align: center; padding: 60px !important; color: #666; }
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.3s, transform 0.3s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(0.95); }
</style>