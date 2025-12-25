<script setup lang="ts">
import { ref, computed } from 'vue'

// SVG иконки
const icons = {
  folder: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
  file: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  star: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'
}

const props = defineProps<{
  node: any,
  selectedId: number | null,
  depth?: number
}>()

const emit = defineEmits(['select'])

const isOpen = ref(false)
const isFolder = computed(() => props.node.type === 'folder')
const isPriority = computed(() => props.node.isPriority)
const currentDepth = props.depth || 0

// Авто-раскрытие, если внутри что-то выбрано (например, при поиске)
if (isFolder.value && props.node.containsSelected) {
  isOpen.value = true
}

const toggle = () => {
  if (isFolder.value) isOpen.value = !isOpen.value
}

const selectEgg = () => {
  if (!isFolder.value) emit('select', props.node.data)
}

// Сортировка: Приоритет -> Папки -> Файлы -> Алфавит
const sortedChildren = computed(() => {
  if (!isFolder.value || !props.node.children) return []
  return Object.values(props.node.children).sort((a: any, b: any) => {
    if (a.isPriority && !b.isPriority) return -1
    if (!a.isPriority && b.isPriority) return 1
    if (a.type === 'folder' && b.type !== 'folder') return -1
    if (a.type !== 'folder' && b.type === 'folder') return 1
    return a.name.localeCompare(b.name)
  })
})

// --- JS Анимация Аккордеона (Slide Down) ---
const enter = (element: Element) => {
  const el = element as HTMLElement
  el.style.height = '0'
  el.style.opacity = '0'
  // Force reflow
  void el.offsetHeight
  el.style.height = `${el.scrollHeight}px`
  el.style.opacity = '1'
}
const afterEnter = (element: Element) => {
  (element as HTMLElement).style.height = 'auto'
}
const leave = (element: Element) => {
  const el = element as HTMLElement
  el.style.height = `${el.scrollHeight}px`
  el.style.opacity = '1'
  void el.offsetHeight
  el.style.height = '0'
  el.style.opacity = '0'
}
</script>

<template>
  <div class="explorer-item">
    
    <div v-if="isFolder" class="folder-block">
      <div 
        class="notify-card folder-header" 
        :class="{ 'active': isOpen, 'priority-item': isPriority }"
        @click="toggle" 
        :style="{ marginLeft: `${currentDepth * 12}px` }"
      >
        <div class="ambient-glow" :class="isPriority ? 'gold' : 'blue'"></div>
        
        <div class="icon-box" :class="isPriority ? 'warning' : 'info'">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" :d="isPriority ? icons.star : icons.folder" />
          </svg>
        </div>

        <div class="card-content">
          <span class="card-title" :class="{ 'text-gold': isPriority }">{{ node.name }}</span>
          <span class="card-desc" v-if="currentDepth < 2">{{ Object.keys(node.children).length }} версий</span>
        </div>

        <div class="chevron" :class="{ rotated: isOpen }">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </div>

      <Transition name="accordion" @enter="enter" @after-enter="afterEnter" @leave="leave">
        <div v-if="isOpen" class="folder-content">
          <FileExplorer 
            v-for="child in sortedChildren" 
            :key="child.name" 
            :node="child" 
            :selectedId="selectedId"
            :depth="currentDepth + 1"
            @select="emit('select', $event)" 
          />
        </div>
      </Transition>
    </div>

    <div 
      v-else 
      class="notify-card file-header" 
      :class="{ 'is-selected': selectedId === node.data.attributes.id }"
      @click="selectEgg"
      :style="{ marginLeft: `${currentDepth * 12}px` }"
    >
      <div class="ambient-glow green"></div>

      <div class="icon-box" :class="selectedId === node.data.attributes.id ? 'success' : 'default'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" :d="icons.file" />
        </svg>
      </div>

      <div class="card-content">
        <span class="card-title" :class="{ 'text-white': selectedId === node.data.attributes.id }">
          {{ node.name }}
        </span>
        <span class="card-desc" v-if="node.data.attributes.docker_image">
          Docker: {{ node.data.attributes.docker_image.split('/').pop().split(':')[0] }}
        </span>
      </div>

      <div class="radio-indicator">
        <div class="dot" v-if="selectedId === node.data.attributes.id"></div>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* Base Card Style (Как в уведомлениях) */
.notify-card {
  position: relative;
  display: flex; align-items: center; gap: 12px;
  background: rgba(255, 255, 255, 0.02); 
  border: 1px solid rgba(255, 255, 255, 0.05);
  padding: 10px 14px; /* Чуть компактнее */
  margin-bottom: 6px;
  border-radius: 12px;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.2s ease;
}

.notify-card:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.1);
  transform: translateY(-1px);
}

.notify-card.is-selected {
  background: rgba(34, 197, 94, 0.05);
  border-color: rgba(34, 197, 94, 0.3);
}

/* Priority */
.notify-card.priority-item {
  border-color: rgba(234, 179, 8, 0.3);
  background: linear-gradient(90deg, rgba(234, 179, 8, 0.05), transparent);
}
.text-gold { color: #facc15; font-weight: 600; }

/* Glows */
.ambient-glow {
  position: absolute; left: 0; top: 0; bottom: 0; width: 60px;
  opacity: 0.15; pointer-events: none; transition: opacity 0.3s;
}
.ambient-glow.blue { background: radial-gradient(circle at left center, #3b82f6, transparent 70%); }
.ambient-glow.green { background: radial-gradient(circle at left center, #22c55e, transparent 70%); }
.ambient-glow.gold { background: radial-gradient(circle at left center, #eab308, transparent 70%); opacity: 0.25; }

.notify-card:hover .ambient-glow { opacity: 0.25; }

/* Icons */
.icon-box {
  width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,0.05); color: #666;
  transition: 0.3s; z-index: 2;
}
.icon-box svg { width: 16px; height: 16px; }
.icon-box.info { color: #3b82f6; background: rgba(59, 130, 246, 0.1); }
.icon-box.success { color: #22c55e; background: rgba(34, 197, 94, 0.1); }
.icon-box.warning { color: #eab308; background: rgba(234, 179, 8, 0.15); }
.icon-box.default { color: #888; }

.folder-header.active .icon-box.info { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }

/* Content */
.card-content { flex-grow: 1; z-index: 2; display: flex; flex-direction: column; overflow: hidden; }
.card-title { color: #fff; font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.card-desc { color: #666; font-size: 11px; margin-top: 1px; }
.text-white { color: #fff; }

.chevron { color: #555; transition: transform 0.3s; }
.chevron.rotated { transform: rotate(180deg); color: #fff; }

.radio-indicator { width: 16px; height: 16px; border-radius: 50%; border: 2px solid #444; display: flex; align-items: center; justify-content: center; }
.is-selected .radio-indicator { border-color: #22c55e; background: rgba(34, 197, 94, 0.1); }
.dot { width: 6px; height: 6px; border-radius: 50%; background: #22c55e; }

/* Transitions */
.accordion-enter-active, .accordion-leave-active { transition: all 0.3s ease-in-out; overflow: hidden; }
.accordion-enter-from, .accordion-leave-to { height: 0; opacity: 0; }
</style>