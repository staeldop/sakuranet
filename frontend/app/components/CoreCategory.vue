<script setup lang="ts">
defineProps<{
  data: any;
  level: number;
  modelValue: string | number | null;
}>();

const emit = defineEmits(['update:modelValue']);
const expanded = ref<Record<string, boolean>>({});

const toggle = (key: string) => {
  expanded.value[key] = !expanded.value[key];
};

const countEggs = (obj: any): number => {
  let count = 0;
  for (const key in obj) {
    if (key === '_eggs') {
      count += obj[key].length;
    } else if (typeof obj[key] === 'object' && key !== '_priorities') {
      count += countEggs(obj[key]);
    }
  }
  return count;
};

const selectCore = (id: string | number) => {
  emit('update:modelValue', id);
};
</script>

<template>
  <div :style="{ marginLeft: `${level * 10}px` }">
    <template v-for="(value, key) in data" :key="key">
      
      <template v-if="key === '_eggs'">
        <label 
          v-for="egg in value" 
          :key="egg.id"
          class="flex items-center gap-2 px-3 py-2 rounded hover:bg-neutral-800/50 cursor-pointer group transition-all duration-200"
          :style="{ marginLeft: `${level * 10}px` }"
        >
          <input 
            type="radio" 
            name="core" 
            :value="egg.id" 
            :checked="modelValue === egg.id"
            @change="selectCore(egg.id)"
            class="text-indigo-600 focus:ring-indigo-500 focus:ring-offset-neutral-900"
          >
          <span class="text-sm text-neutral-400 group-hover:text-white transition-colors">
            {{ egg.name }}
          </span>
        </label>
      </template>

      <template v-else-if="key !== '_priorities' && typeof value === 'object'">
        <div class="border border-neutral-700 rounded-lg overflow-hidden transition-all duration-200 mb-2">
          <button 
            @click="toggle(key)" 
            type="button" 
            class="w-full flex items-center justify-between px-4 py-3 bg-neutral-800/50 hover:bg-neutral-800 transition-all duration-200 text-left"
          >
            <div class="flex items-center gap-2">
              <svg 
                class="icon-svg text-neutral-400 transition-all duration-300" 
                :class="{ 'rotate-90': expanded[key] }" 
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
              <span class="text-white font-medium">{{ key }}</span>
            </div>
            <span class="text-xs text-neutral-500 bg-neutral-700/50 px-2 py-1 rounded">
              {{ countEggs(value) }} ядер
            </span>
          </button>

          <div v-show="expanded[key]" class="bg-neutral-900/30 overflow-hidden p-2">
            <CoreCategory 
              :data="value" 
              :level="level + 1" 
              :modelValue="modelValue"
              @update:modelValue="emit('update:modelValue', $event)"
            />
          </div>
        </div>
      </template>

    </template>
  </div>
</template>

<style scoped>
/* ЖЕСТКИЙ РАЗМЕР ДЛЯ ИКОНОК, КАК В ТВОЕМ РАБОЧЕМ ФАЙЛЕ */
.icon-svg {
  width: 16px;
  height: 16px;
  min-width: 16px;
  display: inline-block;
  flex-shrink: 0;
}
</style>