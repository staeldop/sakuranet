<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useApiFetch, $api } from '~/composables/useApi'

import IconEdit from '~/assets/icons/ticket.svg?component'
import IconTrash from '~/assets/icons/trash.svg?component'

definePageMeta({ layout: 'admin' })

const products = ref([])
const isEditing = ref(false)
const editId = ref<number | null>(null)

// 🔥 ПЕРЕИМЕНОВАЛИ attributes -> specs
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

const fetchProducts = async () => {
  const { data } = await useApiFetch<any[]>('/api/products')
  if (data.value) products.value = data.value
}

const resetForm = () => {
  form.value = JSON.parse(JSON.stringify(defaultForm))
  isEditing.value = false
  editId.value = null
}

const startEdit = (product: any) => {
  form.value = {
    name: product.name,
    category: product.category,
    country: product.country || 'RU',
    gameType: product.game_type || 'gaming',
    price: product.price,
    // 🔥 БЕРЕМ specs
    specs: product.specs ? JSON.parse(JSON.stringify(product.specs)) : []
  }
  isEditing.value = true
  editId.value = product.id
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleSubmit = async () => {
  try {
    const payload = {
      name: form.value.name,
      category: form.value.category,
      country: form.value.category === 'gaming' ? form.value.country : null,
      game_type: form.value.category === 'gaming' ? form.value.gameType : null,
      price: Number(form.value.price),
      specs: form.value.specs // 🔥 ОТПРАВЛЯЕМ specs
    }

    if (isEditing.value && editId.value) {
      await $api(`/api/admin/products/${editId.value}`, { method: 'PUT', body: payload })
      alert('Товар обновлен!')
    } else {
      await $api('/api/admin/products', { method: 'POST', body: payload })
      alert('Товар создан!')
    }

    resetForm()
    fetchProducts()
  } catch (e: any) {
    console.error(e)
    const msg = e.response?._data?.message || e.message || 'Ошибка'
    alert('Ошибка сервера: ' + msg)
  }
}

const deleteProduct = async (id: number) => {
  if(!confirm('Удалить?')) return
  try {
    await $api(`/api/admin/products/${id}`, { method: 'DELETE' })
    fetchProducts()
  } catch (e) { alert('Ошибка удаления') }
}

// 🔥 specs вместо attributes
const addAttr = () => form.value.specs.push({ key: '', value: '', icon: 'cpu' })
const removeAttr = (idx: number) => form.value.specs.splice(idx, 1)

onMounted(fetchProducts)
</script>

<template>
  <div class="p-8 text-white max-w-5xl mx-auto">
    <div class="flex justify-between items-center mb-6">
       <h1 class="text-2xl font-bold">Управление товарами</h1>
       <button v-if="isEditing" @click="resetForm" class="text-sm text-gray-400 underline">Сбросить</button>
    </div>

    <div class="p-6 rounded-xl border mb-10 transition-colors duration-300"
      :class="isEditing ? 'bg-indigo-900/20 border-indigo-500/50' : 'bg-neutral-900 border-neutral-800'">
      
      <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
        <span v-if="isEditing">✏️ Редактирование #{{ editId }}</span>
        <span v-else>➕ Новый товар</span>
      </h2>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <input v-model="form.name" placeholder="Название" class="input-dark" required />
          <input v-model="form.price" type="number" placeholder="Цена" class="input-dark" required />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <select v-model="form.category" class="input-dark">
             <option value="gaming">Gaming</option>
             <option value="virtual">Virtual</option>
             <option value="dedicated">Dedicated</option>
          </select>
          <select v-model="form.country" class="input-dark" :disabled="form.category !== 'gaming'">
             <option value="RU">Россия</option>
             <option value="DE">Германия</option>
             <option value="FI">Финляндия</option>
          </select>
          <select v-model="form.gameType" class="input-dark" :disabled="form.category !== 'gaming'">
             <option value="gaming">Game Server</option>
             <option value="coding">Coding</option>
          </select>
        </div>

        <div class="bg-black/30 p-4 rounded border border-white/5">
          <div class="flex justify-between mb-3 items-center">
            <span class="text-sm font-semibold text-gray-300">Характеристики (Specs)</span>
            <button type="button" @click="addAttr" class="text-green-400 text-xs font-bold uppercase">+ Добавить</button>
          </div>
          
          <div v-for="(attr, idx) in form.specs" :key="idx" class="flex gap-2 mb-2 items-center">
             <div class="w-8 text-center text-xs text-gray-600">{{ idx + 1 }}</div>
             <input v-model="attr.key" placeholder="CPU/RAM" class="input-small w-1/4" />
             <input v-model="attr.value" placeholder="Value" class="input-small flex-grow" />
             <select v-model="attr.icon" class="input-small w-24">
               <option value="cpu">CPU</option>
               <option value="ram">RAM</option>
               <option value="disk">Disk</option>
               <option value="code">Code</option>
             </select>
             <button type="button" @click="removeAttr(idx)" class="text-red-500 px-2">✕</button>
          </div>
        </div>

        <button type="submit" class="w-full py-3 rounded font-bold shadow-lg"
          :class="isEditing ? 'bg-indigo-600 hover:bg-indigo-500' : 'bg-green-600 hover:bg-green-500'">
          {{ isEditing ? 'Сохранить' : 'Создать' }}
        </button>
      </form>
    </div>

    <div class="grid gap-3">
      <div v-for="p in products" :key="p.id" class="bg-neutral-900 p-4 rounded-lg border border-neutral-800 flex justify-between items-center">
        <div class="flex items-center gap-4">
          <div class="w-10 h-10 rounded bg-neutral-800 flex items-center justify-center font-bold text-gray-500">{{ p.id }}</div>
          <div>
            <div class="font-bold text-lg text-white">{{ p.name }}</div>
            <div class="text-xs text-gray-500">{{ p.category }} | {{ Number(p.price) }} ₽</div>
          </div>
        </div>
        <div class="flex gap-2">
          <button @click="startEdit(p)" class="p-2 rounded bg-indigo-500/10 text-indigo-500"><IconEdit class="w-5 h-5"/></button>
          <button @click="deleteProduct(p.id)" class="p-2 rounded bg-red-500/10 text-red-500"><IconTrash class="w-5 h-5"/></button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.input-dark { background: #0a0a0a; border: 1px solid #333; color: white; padding: 10px; border-radius: 8px; width: 100%; outline: none; }
.input-small { background: #0a0a0a; border: 1px solid #333; color: white; padding: 6px; border-radius: 6px; font-size: 13px; outline: none; }
</style>