<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { $api, useApiFetch } from '~/composables/useApi'

// Иконки
import IconBox from '~/assets/icons/box.svg?component'
import IconEdit from '~/assets/icons/ticket.svg?component'
import IconTrash from '~/assets/icons/trash.svg?component'

definePageMeta({ layout: 'admin' })

interface Product {
  id: number
  name: string
  category: string
  price: string
  specs: { key: string, value: string }[]
  // Pterodactyl Fields
  ptero_nest_id?: number
  ptero_egg_id?: number
  ptero_docker_image?: string
  ptero_startup?: string
  memory_mb?: number
  disk_mb?: number
  cpu_limit?: number
  databases?: number
  backups?: number
}

const products = ref<Product[]>([])
const isLoading = ref(true)
const isModalOpen = ref(false)
const isSubmitting = ref(false)

// Форма
const form = ref<any>({
  name: '',
  category: 'gaming',
  price: 0,
  specs: [],
  // Defaults
  ptero_nest_id: 1, // Minecraft по умолчанию
  ptero_egg_id: 5,  // Paper по умолчанию
  ptero_docker_image: 'ghcr.io/pterodactyl/yolks:java_17',
  ptero_startup: 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar server.jar',
  memory_mb: 1024,
  disk_mb: 5120,
  cpu_limit: 100,
  databases: 0,
  backups: 0
})

const editingId = ref<number | null>(null)
const currentTab = ref<'main' | 'ptero'>('main') // Табы в модалке

// --- ЗАГРУЗКА ---
const fetchProducts = async () => {
  isLoading.value = true
  try {
    const { data } = await useApiFetch<Product[]>('/api/products')
    if (data.value) products.value = data.value
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

// --- УПРАВЛЕНИЕ СПЕКАМИ (Характеристики) ---
const addSpec = () => {
  if (!form.value.specs) form.value.specs = []
  form.value.specs.push({ key: '', value: '' })
}
const removeSpec = (index: number) => {
  form.value.specs.splice(index, 1)
}

// --- МОДАЛКА ---
const openCreate = () => {
  editingId.value = null
  currentTab.value = 'main'
  form.value = {
    name: '', category: 'gaming', price: 0, specs: [
      { key: 'CPU', value: '1 vCore' },
      { key: 'RAM', value: '2 GB' },
      { key: 'Disk', value: '20 GB NVMe' }
    ],
    ptero_nest_id: 1, ptero_egg_id: 5,
    ptero_docker_image: 'ghcr.io/pterodactyl/yolks:java_17',
    ptero_startup: 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar server.jar',
    memory_mb: 2048, disk_mb: 10240, cpu_limit: 100, databases: 0, backups: 0
  }
  isModalOpen.value = true
}

const openEdit = (prod: Product) => {
  editingId.value = prod.id
  currentTab.value = 'main'
  // Клонируем объект, чтобы не менять напрямую в списке
  form.value = JSON.parse(JSON.stringify(prod))
  // Убедимся, что specs это массив
  if (!Array.isArray(form.value.specs)) form.value.specs = []
  isModalOpen.value = true
}

const saveProduct = async () => {
  isSubmitting.value = true
  try {
    const url = editingId.value ? `/api/products/${editingId.value}` : '/api/products'
    const method = editingId.value ? 'PUT' : 'POST'
    
    await $api(url, { method, body: form.value })
    await fetchProducts()
    isModalOpen.value = false
  } catch (e) {
    alert('Ошибка сохранения')
  } finally {
    isSubmitting.value = false
  }
}

const deleteProduct = async (id: number) => {
  if (!confirm('Удалить товар?')) return
  await $api(`/api/products/${id}`, { method: 'DELETE' })
  products.value = products.value.filter(p => p.id !== id)
}

onMounted(fetchProducts)
</script>

<template>
  <div class="admin-shell">
    <div class="glow glow-blue" />
    
    <div class="content-wrapper">
      <div class="header-section">
        <h1 class="title">Товары (Тарифы)</h1>
        <button class="create-btn" @click="openCreate">+ Добавить тариф</button>
      </div>

      <div class="glass-card">
        <div class="card-glow-top" />
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Название</th>
              <th>Цена</th>
              <th>Категория</th>
              <th>Ptero Config</th>
              <th class="text-right">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="prod in products" :key="prod.id" class="data-row">
              <td class="id-cell">#{{ prod.id }}</td>
              <td class="font-bold text-white">{{ prod.name }}</td>
              <td class="text-green-400 font-mono">{{ prod.price }} ₽</td>
              <td><span class="badge">{{ prod.category }}</span></td>
              
              <td>
                <span v-if="prod.ptero_nest_id" class="text-xs text-blue-400">
                  Nest: {{ prod.ptero_nest_id }} | Egg: {{ prod.ptero_egg_id }}
                </span>
                <span v-else class="text-xs text-gray-600">Не настроено</span>
              </td>

              <td class="text-right">
                <div class="actions">
                  <button @click="openEdit(prod)" class="btn-icon edit"><IconEdit /></button>
                  <button @click="deleteProduct(prod.id)" class="btn-icon delete"><IconTrash /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="isModalOpen" class="modal-overlay" @click.self="isModalOpen = false">
      <div class="glass-card modal-card">
        <h2 class="modal-title">{{ editingId ? 'Редактировать' : 'Создать' }} тариф</h2>

        <div class="modal-tabs">
          <button @click="currentTab = 'main'" :class="{ active: currentTab === 'main' }">Основное</button>
          <button @click="currentTab = 'ptero'" :class="{ active: currentTab === 'ptero' }">Pterodactyl</button>
        </div>

        <div class="modal-body custom-scrollbar">
          
          <div v-if="currentTab === 'main'" class="tab-content">
            <div class="form-group">
              <label>Название</label>
              <input v-model="form.name" class="glass-input" placeholder="Например: Minecraft Start">
            </div>
            <div class="form-row">
              <div class="form-group half">
                <label>Цена (₽/мес)</label>
                <input v-model.number="form.price" type="number" class="glass-input">
              </div>
              <div class="form-group half">
                <label>Категория</label>
                <select v-model="form.category" class="glass-input">
                  <option value="gaming">Gaming</option>
                  <option value="virtual">VPS</option>
                  <option value="dedicated">Dedicated</option>
                </select>
              </div>
            </div>

            <div class="specs-section">
              <label>Характеристики (для отображения на сайте)</label>
              <div v-for="(spec, idx) in form.specs" :key="idx" class="spec-row">
                <input v-model="spec.key" placeholder="CPU" class="glass-input sm">
                <input v-model="spec.value" placeholder="4 Core" class="glass-input">
                <button @click="removeSpec(idx)" class="remove-btn">✕</button>
              </div>
              <button @click="addSpec" class="add-spec-btn">+ Добавить параметр</button>
            </div>
          </div>

          <div v-if="currentTab === 'ptero'" class="tab-content">
            <div class="info-box">
              Эти ID можно найти в админке Pterodactyl (Nests & Eggs).
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Nest ID (Гнездо)</label>
                <input v-model.number="form.ptero_nest_id" type="number" class="glass-input" placeholder="1 (Minecraft)">
              </div>
              <div class="form-group half">
                <label>Egg ID (Яйцо по умолчанию)</label>
                <input v-model.number="form.ptero_egg_id" type="number" class="glass-input" placeholder="5 (Paper)">
              </div>
            </div>

            <div class="form-group">
              <label>Docker Image</label>
              <input v-model="form.ptero_docker_image" class="glass-input text-xs font-mono">
            </div>
            <div class="form-group">
              <label>Startup Command</label>
              <input v-model="form.ptero_startup" class="glass-input text-xs font-mono">
            </div>

            <h3 class="sub-title">Лимиты ресурсов</h3>
            <div class="form-row">
              <div class="form-group third">
                <label>RAM (MB)</label>
                <input v-model.number="form.memory_mb" type="number" class="glass-input">
              </div>
              <div class="form-group third">
                <label>Disk (MB)</label>
                <input v-model.number="form.disk_mb" type="number" class="glass-input">
              </div>
              <div class="form-group third">
                <label>CPU (%)</label>
                <input v-model.number="form.cpu_limit" type="number" class="glass-input">
              </div>
            </div>
            <div class="form-row">
               <div class="form-group half">
                <label>Databases</label>
                <input v-model.number="form.databases" type="number" class="glass-input">
              </div>
              <div class="form-group half">
                <label>Backups</label>
                <input v-model.number="form.backups" type="number" class="glass-input">
              </div>
            </div>
          </div>

        </div>

        <div class="modal-actions">
          <button class="ghost-btn" @click="isModalOpen = false">Отмена</button>
          <button class="primary-btn" @click="saveProduct" :disabled="isSubmitting">
            {{ isSubmitting ? 'Сохраняем...' : 'Сохранить' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Базовые стили из users.vue + доп. стили для табов и форм */
.admin-shell { position: relative; min-height: 100vh; width: 100%; padding-bottom: 40px; }
.content-wrapper { max-width: 1200px; margin: 0 auto; padding: 20px; position: relative; z-index: 10; }
.glow { position: absolute; width: 600px; height: 600px; filter: blur(100px); opacity: 0.15; pointer-events: none; }
.glow-blue { top: -10%; left: 20%; background: radial-gradient(circle, #3b82f6, transparent 70%); }

.header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.title { color: #fff; font-size: 28px; font-weight: 700; margin: 0; }
.create-btn { background: #fff; color: #000; border: none; padding: 10px 20px; border-radius: 100px; font-weight: 600; cursor: pointer; transition: 0.2s; }
.create-btn:hover { transform: translateY(-2px); box-shadow: 0 0 15px rgba(255,255,255,0.3); }

.glass-card { background: rgba(20, 20, 20, 0.6); border: 1px solid rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border-radius: 16px; overflow: hidden; }
.card-glow-top { height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); }

.data-table { width: 100%; border-collapse: collapse; text-align: left; color: #ccc; font-size: 14px; }
.data-table th { padding: 16px; font-size: 11px; text-transform: uppercase; color: #666; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.05); }
.data-table td { padding: 16px; border-bottom: 1px solid rgba(255,255,255,0.03); vertical-align: middle; }
.id-cell { font-family: monospace; color: #555; }
.badge { background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 6px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }

.actions { display: flex; gap: 8px; justify-content: flex-end; }
.btn-icon { width: 32px; height: 32px; background: transparent; border: none; color: #555; cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; }
.btn-icon:hover { background: rgba(255,255,255,0.05); color: #fff; }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 100; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
.modal-card { width: 100%; max-width: 600px; padding: 30px; background: #0a0a0a; border: 1px solid rgba(255,255,255,0.1); max-height: 90vh; display: flex; flex-direction: column; }
.modal-title { margin: 0 0 20px 0; color: #fff; }

.modal-tabs { display: flex; gap: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
.modal-tabs button { background: none; border: none; color: #666; padding-bottom: 10px; cursor: pointer; font-weight: 600; border-bottom: 2px solid transparent; transition: 0.2s; }
.modal-tabs button.active { color: #fff; border-color: #fff; }
.modal-tabs button:hover { color: #aaa; }

.modal-body { overflow-y: auto; padding-right: 5px; margin-bottom: 20px; }
.form-group { margin-bottom: 15px; }
.form-row { display: flex; gap: 15px; }
.half { flex: 1; } .third { flex: 1; }

label { display: block; font-size: 11px; color: #666; margin-bottom: 6px; text-transform: uppercase; font-weight: 700; }
.glass-input { width: 100%; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 12px; border-radius: 10px; color: #fff; outline: none; transition: 0.2s; box-sizing: border-box; }
.glass-input:focus { border-color: #555; background: rgba(255,255,255,0.05); }

.specs-section { background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; margin-bottom: 20px; }
.spec-row { display: flex; gap: 10px; margin-bottom: 10px; }
.spec-row .sm { width: 80px; }
.remove-btn { background: none; border: none; color: #666; cursor: pointer; font-size: 16px; }
.remove-btn:hover { color: #ef4444; }
.add-spec-btn { width: 100%; padding: 8px; border: 1px dashed rgba(255,255,255,0.2); background: none; color: #666; border-radius: 8px; cursor: pointer; font-size: 12px; }
.add-spec-btn:hover { border-color: #666; color: #ccc; }

.info-box { background: rgba(59, 130, 246, 0.1); color: #60a5fa; padding: 10px; border-radius: 8px; font-size: 12px; margin-bottom: 20px; border: 1px solid rgba(59, 130, 246, 0.2); }
.sub-title { color: #fff; font-size: 14px; margin: 20px 0 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px; }

.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: auto; }
.primary-btn { background: #fff; color: #000; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.ghost-btn { background: none; color: #888; border: none; padding: 10px 20px; cursor: pointer; }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #333; border-radius: 2px; }
</style>