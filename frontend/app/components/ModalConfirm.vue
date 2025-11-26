<script setup lang="ts">
defineProps<{
  isOpen: boolean
  title?: string
  message?: string
  loading?: boolean
}>()

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
  <Transition name="modal-fade">
    <div v-if="isOpen" class="modal-backdrop" @click.self="emit('close')">
      
      <div class="modal-card">
        <div class="icon-wrapper">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
        </div>

        <h3 class="modal-title">{{ title || 'Вы уверены?' }}</h3>
        <p class="modal-text">{{ message || 'Это действие нельзя будет отменить.' }}</p>

        <div class="modal-actions">
          <button class="btn-cancel" @click="emit('close')" :disabled="loading">
            Отмена
          </button>
          
          <button class="btn-confirm" @click="emit('confirm')" :disabled="loading">
            <span v-if="loading" class="spinner-mini"></span>
            <span v-else>Удалить</span>
          </button>
        </div>
      </div>

    </div>
  </Transition>
</template>

<style scoped>
.modal-backdrop {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
}

.modal-card {
  background: #111;
  border: 1px solid #333;
  width: 100%; max-width: 400px;
  border-radius: 20px;
  padding: 30px;
  text-align: center;
  box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.5);
  transform: translateY(0);
}

.icon-wrapper {
  width: 60px; height: 60px; margin: 0 auto 20px;
  background: rgba(239, 68, 68, 0.1);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.modal-title { font-size: 20px; font-weight: 700; color: white; margin-bottom: 8px; }
.modal-text { font-size: 14px; color: #888; margin-bottom: 24px; line-height: 1.5; }

.modal-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

.btn-cancel {
  background: transparent; border: 1px solid #333; color: #ccc;
  padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s;
}
.btn-cancel:hover { background: #222; color: white; }

.btn-confirm {
  background: #ef4444; border: none; color: white;
  padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
}
.btn-confirm:hover { background: #dc2626; box-shadow: 0 0 25px rgba(239, 68, 68, 0.6); }
.btn-confirm:disabled { opacity: 0.7; cursor: not-allowed; }

/* Анимации */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-fade-enter-active .modal-card, .modal-fade-leave-active .modal-card { transition: transform 0.2s cubic-bezier(0.2, 0.8, 0.2, 1); }
.modal-fade-enter-from .modal-card, .modal-fade-leave-to .modal-card { transform: scale(0.95) translateY(10px); }

.spinner-mini {
  width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white; border-radius: 50%; animation: spin 1s infinite linear;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>