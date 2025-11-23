<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useApiFetch } from '~/composables/useApi'

definePageMeta({ layout: 'admin' })

// Состояние формы
const form = ref({
  name: '',
  category: 'gaming',
  country: 'RU',
  gameType: 'gaming',
  price: '',
  // Характеристики по умолчанию
  attributes: [
    { key: 'CPU', value: 'Ryzen 5', icon: 'cpu' },
    { key: 'RAM', value: '8 GB', icon: 'ram' },
    { key: 'Disk', value: '50 GB NVMe', icon: 'disk' }
  ]
})

const products = ref([])

// Загрузка товаров
const fetchProducts = async () => {
  const { data } = await useApiFetch('/api/products')
  if (data.value) products.value = data.value
}

// Добавление товара
const createProduct = async () => {
  try {
    await useApiFetch('/api/admin/products', {
      method: 'POST',
      body: {
        name: form.value.name,
        category: form.value.category,
        country: form.value.category === 'gaming' ? form.value.country : null,
        game_type: form.value.category === 'gaming' ? form.value.gameType : null,
        price: Number(form.value.price),
        attributes: form.value.attributes
      }
    })
    alert('Товар создан!')
    fetchProducts() // Обновить список
  } catch (e) {
    alert('Ошибка создания')
  }
}

// Удаление
const deleteProduct = async (id: number) => {
  if(!confirm('Удалить?')) return
  await useApiFetch(`/api/admin/products/${id}`, { method: 'DELETE' })
  fetchProducts()
}

// Хелперы для формы характеристик
const addAttr = () => form.value.attributes.push({ key: 'New', value: '', icon: 'cpu' })
const removeAttr = (idx: number) => form.value.attributes.splice(idx, 1)

onMounted(fetchProducts)
</script>

<template>
  <div class="p-8 text-white">
    <h1 class="text-2xl font-bold mb-6">Управление товарами</h1>

    <div class="bg-neutral-900 p-6 rounded-xl border border-neutral-800 mb-10">
      <h2 class="text-lg font-bold mb-4">Добавить новый товар</h2>
      <form @submit.prevent="createProduct" class="space-y-4">
        
        <div class="grid grid-cols-2 gap-4">
          <input v-model="form.name" placeholder="Название (напр. Minecraft Start)" class="input-dark" required />
          <input v-model="form.price" type="number" placeholder="Цена (RUB)" class="input-dark" required />
        </div>

        <div class="grid grid-cols-3 gap-4">
          <select v-model="form.category" class="input-dark">
            <option value="gaming">Gaming</option>
            <option value="virtual">Virtual (VDS)</option>
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

        <div class="bg-black/30 p-4 rounded border border-neutral-800">
          <div class="flex justify-between mb-2">
            <span class="text-sm text-gray-400">Характеристики</span>
            <button type="button" @click="addAttr" class="text-green-500 text-xs">+ Добавить</button>
          </div>
          <div v-for="(attr, idx) in form.attributes" :key="idx" class="flex gap-2 mb-2">
             <input v-model="attr.key" placeholder="Label (CPU)" class="input-small" />
             <input v-model="attr.value" placeholder="Value (Core i9)" class="input-small flex-grow" />
             <select v-model="attr.icon" class="input-small w-24">
               <option value="cpu">CPU</option>
               <option value="ram">RAM</option>
               <option value="disk">Disk</option>
               <option value="code">Code</option>
             </select>
             <button type="button" @click="removeAttr(idx)" class="text-red-500">x</button>
          </div>
        </div>

        <button type="submit" class="bg-green-600 px-6 py-2 rounded hover:bg-green-500">Создать</button>
      </form>
    </div>

    <div class="grid gap-4">
      <div v-for="p in products" :key="p.id" class="bg-neutral-900 p-4 rounded border border-neutral-800 flex justify-between items-center">
        <div>
          <div class="font-bold text-lg">{{ p.name }}</div>
          <div class="text-sm text-gray-500">{{ p.category }} | {{ p.country }} | {{ p.price }}₽</div>
        </div>
        <button @click="deleteProduct(p.id)" class="text-red-500 hover:text-red-400">Удалить</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.input-dark { background: #111; border: 1px solid #333; color: white; padding: 10px; borderRadius: 8px; width: 100%; }
.input-small { background: #111; border: 1px solid #333; color: white; padding: 6px; borderRadius: 4px; font-size: 13px; }
</style>