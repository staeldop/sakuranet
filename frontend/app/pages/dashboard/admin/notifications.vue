<script setup lang="ts">
import { ref } from 'vue'
import { $api } from '~/composables/useApi'

definePageMeta({
  layout: 'admin'
})

// --- ЛОГИКА (Осталась прежней) ---
const form = ref({
  target: 'all' as 'all' | 'single',
  user_id: '',
  title: '',
  message: '',
  type: 'info' as 'info' | 'success' | 'warning' | 'error'
})

const isSubmitting = ref(false)

const types = [
  { value: 'info',    label: 'Информация', color: '#3b82f6', icon: 'i' },
  { value: 'success', label: 'Успех',      color: '#22c55e', icon: '✓' },
  { value: 'warning', label: 'Внимание',   color: '#eab308', icon: '!' },
  { value: 'error',   label: 'Ошибка',     color: '#ef4444', icon: '×' }
]

const sendNotification = async () => {
  if (!form.value.title || !form.value.message) {
    alert('Заполните заголовок и сообщение')
    return
  }
  if (form.value.target === 'single' && !form.value.user_id) {
    alert('Укажите ID пользователя')
    return
  }

  isSubmitting.value = true

  try {
    await $api('/api/admin/notifications/send', {
      method: 'POST',
      body: {
        user_id: form.value.target === 'single' ? form.value.user_id : null,
        title: form.value.title,
        message: form.value.message,
        type: form.value.type
      }
    })
    
    alert('Уведомление успешно отправлено!')
    // Сброс
    form.value.title = ''
    form.value.message = ''
    form.value.user_id = ''
  } catch (e) {
    console.error(e)
    alert('Ошибка отправки')
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="admin-shell">
    <div class="glow glow-purple" />
    
    <div class="content-wrapper">
      
      <div class="page-header">
        <div class="title-group">
          <div class="auth-badge mb-2">
            <span class="badge-dot" />
            <span class="badge-text">SYSTEM ALERTS</span>
          </div>
          <h1 class="title">Рассылка уведомлений</h1>
          <div class="subtitle">Отправка системных сообщений пользователям</div>
        </div>
      </div>

      <div class="glass-card form-container">
        <div class="card-glow-top" />
        
        <form @submit.prevent="sendNotification" class="form-content">
          
          <div class="form-row">
            <label class="field-label">Получатели</label>
            <div class="target-switcher">
              <button 
                type="button" 
                class="switch-btn" 
                :class="{ active: form.target === 'all' }"
                @click="form.target = 'all'"
              >
                <div class="icon-box">📢</div>
                <div class="text-box">
                  <span class="main-text">Всем пользователям</span>
                  <span class="sub-text">Массовая рассылка</span>
                </div>
                <div class="check-mark" v-if="form.target === 'all'">✓</div>
              </button>

              <button 
                type="button" 
                class="switch-btn" 
                :class="{ active: form.target === 'single' }"
                @click="form.target = 'single'"
              >
                <div class="icon-box">👤</div>
                <div class="text-box">
                  <span class="main-text">Личное сообщение</span>
                  <span class="sub-text">По конкретному ID</span>
                </div>
                <div class="check-mark" v-if="form.target === 'single'">✓</div>
              </button>
            </div>
          </div>

          <Transition name="slide-fade">
            <div class="form-row" v-if="form.target === 'single'">
              <label class="field-label">ID Пользователя</label>
              <input 
                v-model="form.user_id" 
                type="number" 
                placeholder="Например: 42" 
                class="modern-input"
              >
            </div>
          </Transition>

          <div class="separator" />

          <div class="form-row">
            <label class="field-label">Тип важности</label>
            <div class="types-grid">
              <div 
                v-for="t in types" 
                :key="t.value"
                class="type-option"
                :class="{ active: form.type === t.value }"
                :style="{ '--accent': t.color }"
                @click="form.type = t.value as any"
              >
                <div class="type-dot"></div>
                <span>{{ t.label }}</span>
              </div>
            </div>
          </div>

          <div class="form-row two-col">
            <div class="col">
              <label class="field-label">Заголовок</label>
              <input 
                v-model="form.title" 
                type="text" 
                placeholder="Краткая тема..." 
                class="modern-input"
              >
            </div>
          </div>

          <div class="form-row">
            <label class="field-label">Текст сообщения</label>
            <textarea 
              v-model="form.message" 
              rows="4" 
              placeholder="Введите текст уведомления..." 
              class="modern-input area"
            ></textarea>
          </div>

          <div class="actions">
            <button type="submit" class="btn-primary" :disabled="isSubmitting">
              <span v-if="!isSubmitting">Отправить уведомление</span>
              <span v-else>Отправка...</span>
              <svg v-if="!isSubmitting" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22L11 13L2 9L22 2Z"/></svg>
            </button>
          </div>

        </form>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* --- BASE SHELL (Из примера) --- */
.admin-shell { position: relative; min-height: 100%; width: 100%; overflow: hidden; padding-bottom: 80px; }
.content-wrapper { position: relative; z-index: 10; max-width: 900px; margin: 0 auto; padding: 0 20px; }
.glow { position: absolute; width: 600px; height: 600px; border-radius: 50%; filter: blur(100px); opacity: 0.15; pointer-events: none; z-index: 0; }
.glow-purple { top: -10%; right: -10%; background: radial-gradient(circle, #8b5cf6, transparent 70%); }

/* HEADER */
.page-header { margin: 40px 0 30px; }
.title { font-size: 32px; font-weight: 700; color: #fff; margin: 0; letter-spacing: -0.5px; }
.subtitle { color: #888; font-size: 15px; margin-top: 6px; }
.auth-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 100px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); width: fit-content; }
.badge-dot { width: 6px; height: 6px; background: #eab308; border-radius: 50%; box-shadow: 0 0 8px #eab308; }
.badge-text { font-size: 10px; font-weight: 700; letter-spacing: 1px; color: #aaa; text-transform: uppercase; }

/* GLASS CARD */
.glass-card { 
  position: relative; 
  background: rgba(20, 20, 20, 0.6); 
  border: 1px solid rgba(255, 255, 255, 0.08); 
  backdrop-filter: blur(20px); 
  border-radius: 24px; 
  overflow: hidden; 
}
.card-glow-top { position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); }
.form-content { padding: 40px; }

/* FORM ELEMENTS */
.form-row { margin-bottom: 28px; }
.field-label { display: block; color: #9ca3af; font-size: 13px; font-weight: 600; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
.separator { height: 1px; background: rgba(255,255,255,0.05); margin: 30px 0; }

/* TARGET SWITCHER */
.target-switcher { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.switch-btn {
  position: relative; display: flex; align-items: center; gap: 15px;
  background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);
  padding: 16px; border-radius: 16px; cursor: pointer; transition: 0.2s;
  text-align: left;
}
.switch-btn:hover { background: rgba(255,255,255,0.04); }
.switch-btn.active { background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.4); }
.icon-box { font-size: 20px; opacity: 0.7; }
.text-box { display: flex; flex-direction: column; }
.main-text { color: #eee; font-weight: 600; font-size: 14px; }
.sub-text { color: #666; font-size: 12px; }
.switch-btn.active .main-text { color: #60a5fa; }
.check-mark { position: absolute; top: 10px; right: 10px; color: #60a5fa; font-weight: bold; font-size: 12px; }

/* TYPES GRID */
.types-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.type-option {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05);
  padding: 12px; border-radius: 12px; cursor: pointer; transition: all 0.2s;
  color: #888; font-size: 13px; font-weight: 500;
}
.type-option .type-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); box-shadow: 0 0 5px var(--accent); opacity: 0.5; transition: 0.2s; }
.type-option:hover { background: rgba(255,255,255,0.05); }
.type-option.active { 
  background: rgba(255,255,255,0.08); 
  border-color: var(--accent); 
  color: #fff; 
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
.type-option.active .type-dot { opacity: 1; transform: scale(1.2); }

/* INPUTS */
.modern-input {
  width: 100%; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.08);
  padding: 16px; border-radius: 14px; color: #fff; font-size: 15px; outline: none;
  transition: all 0.2s; box-sizing: border-box;
}
.modern-input:focus { 
  border-color: #3b82f6; 
  background: rgba(0,0,0,0.4); 
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}
.modern-input::placeholder { color: #555; }
.area { resize: vertical; min-height: 120px; line-height: 1.5; font-family: inherit; }

/* BUTTON */
.btn-primary {
  width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
  background: #3b82f6; color: white; border: none;
  padding: 18px; border-radius: 16px; font-size: 16px; font-weight: 600;
  cursor: pointer; transition: 0.2s;
  box-shadow: 0 4px 20px rgba(59, 130, 246, 0.25);
}
.btn-primary:hover:not(:disabled) { background: #2563eb; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; filter: grayscale(0.5); }

/* ANIMATIONS */
.slide-fade-enter-active, .slide-fade-leave-active { transition: all 0.3s ease; max-height: 100px; opacity: 1; overflow: hidden; }
.slide-fade-enter-from, .slide-fade-leave-to { max-height: 0; opacity: 0; margin-top: -10px; }

@media (max-width: 600px) {
  .target-switcher, .types-grid { grid-template-columns: 1fr; }
  .form-content { padding: 25px; }
  .title { font-size: 24px; }
}
</style>