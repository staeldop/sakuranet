<template>
  <div class="auth-shell">
    <div class="glow glow-1" />
    <div class="glow glow-2" />
    <div class="card-glow" />

    <div class="auth-card">
      <header class="auth-header">
        <div class="auth-badge">
          <span class="badge-dot" />
          <span class="badge-text">RECOVERY</span>
        </div>
        <h1 class="auth-title">Восстановление</h1>
        <p class="auth-subtitle">
          {{ step === 1 ? 'Введите Email для поиска аккаунта.' : 'Придумайте новый пароль.' }}
        </p>
      </header>

      <form class="auth-form" @submit.prevent="onSubmit">
        
        <div v-if="step === 1" class="step-container">
          <label class="field">
            <span class="field-label">Ваш Email</span>
            <input
              v-model="form.email"
              type="email"
              placeholder="user@example.com"
              required
              autofocus
            />
          </label>
        </div>

        <div v-else class="step-container">
          <div class="info-alert">
            Код отправлен на {{ form.email }}
          </div>

          <label class="field">
            <span class="field-label">Код из письма</span>
            <input
              v-model="form.code"
              type="text"
              placeholder="000000"
              class="code-input"
              maxlength="6"
              required
            />
          </label>

          <label class="field">
            <span class="field-label">Новый пароль</span>
            <div class="password-wrapper">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Новый пароль"
                required
              />
              <button type="button" class="eye-btn" @click="showPassword = !showPassword" tabindex="-1">
                <svg v-if="!showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </label>

          <label class="field">
            <span class="field-label">Повторите пароль</span>
            <input
              v-model="form.password_confirmation"
              type="password"
              placeholder="Подтверждение"
              required
            />
          </label>
        </div>

        <button class="primary-btn" type="submit" :disabled="pending">
          <span v-if="pending">Обработка...</span>
          <span v-else>{{ step === 1 ? "Отправить код" : "Сменить пароль" }}</span>
        </button>
        
        <template v-if="!successMessage">
            <div class="separator">
            <span>или</span>
            </div>

            <button
            class="ghost-btn"
            type="button"
            @click="navigateTo('/login')"
            >
            Вернуться ко входу
            </button>
        </template>

        <p v-if="errorMessage" class="error-text">{{ errorMessage }}</p>
        <p v-if="successMessage" class="success-text">{{ successMessage }}</p>

      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'

definePageMeta({
  layout: 'empty'
})

const config = useRuntimeConfig()

// Состояния
const step = ref(1) // 1 = ввод email, 2 = ввод кода и пароля
const pending = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const showPassword = ref(false)

const form = reactive({
  email: '',
  code: '',
  password: '',
  password_confirmation: ''
})

const onSubmit = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  pending.value = true

  try {
    if (step.value === 1) {
      // ШАГ 1: ОТПРАВЛЯЕМ КОД
      await $fetch('/api/forgot-password', {
        baseURL: config.public.apiBase,
        method: 'POST',
        body: { email: form.email }
      })
      step.value = 2 // Переходим ко второму шагу
    } else {
      // ШАГ 2: СБРАСЫВАЕМ ПАРОЛЬ
      await $fetch('/api/reset-password', {
        baseURL: config.public.apiBase,
        method: 'POST',
        body: form
      })
      
      successMessage.value = 'Пароль успешно изменен! Перенаправление...'
      setTimeout(() => navigateTo('/login'), 2000)
    }
  } catch (e: any) {
    errorMessage.value = e.response?._data?.message || 'Произошла ошибка'
  } finally {
    pending.value = false
  }
}
</script>

<style>
html, body { margin: 0; padding: 0; background: #09090b; }
</style>

<style scoped>
/* Вставляем те же стили, что и в login.vue */
.auth-shell { position: relative; width: 100%; height: 100vh; max-height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; box-sizing: border-box; padding: 16px; }
.glow { position: absolute; filter: blur(90px); opacity: 0.8; pointer-events: none; }
.glow-1 { width: 420px; height: 420px; background: radial-gradient(circle at center, #ffffff, rgba(255, 255, 255, 0)); top: -80px; left: -40px; opacity: 0.11; animation: floatGlow1 22s linear infinite; }
.glow-2 { width: 520px; height: 520px; background: radial-gradient( circle at center, #ffffff, rgba(255, 255, 255, 0) ); bottom: -120px; right: -20px; opacity: 0.07; animation: floatGlow2 26s linear infinite; }
.card-glow { position: absolute; width: 520px; height: 320px; border-radius: 999px; background: radial-gradient( circle at center, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0) ); filter: blur(40px); opacity: 0.18; pointer-events: none; }
.auth-card { position: relative; width: 100%; max-width: 520px; padding: 26px 28px 22px; margin: 0; border-radius: 26px; background: rgba(8, 8, 8, 0.78); border: 1px solid rgba(255, 255, 255, 0.07); box-shadow: 0 30px 70px rgba(0, 0, 0, 0.95), 0 0 0 1px rgba(255, 255, 255, 0.03); backdrop-filter: blur(22px); -webkit-backdrop-filter: blur(22px); box-sizing: border-box; transform: translateY(12px) scale(0.985); opacity: 0; animation: cardEnter 0.6s cubic-bezier(0.22, 0.61, 0.36, 1) forwards; z-index: 10; }
.auth-header { margin-bottom: 18px; }
.auth-badge { display: inline-flex; align-items: center; gap: 8px; padding: 4px 11px 4px 9px; border-radius: 999px; border: 1px solid rgba(255, 255, 255, 0.16); background: radial-gradient( circle at left, rgba(255, 255, 255, 0.12), transparent 55% ), rgba(0, 0, 0, 0.6); font-size: 10px; letter-spacing: 0.23em; text-transform: uppercase; opacity: 0.9; transition: all 0.5s cubic-bezier(0.22, 0.61, 0.36, 1); }
.badge-dot, .badge-text { transition: transform 0.5s cubic-bezier(0.22, 0.61, 0.36, 1); }
.badge-dot { width: 8px; height: 8px; border-radius: 999px; background: radial-gradient(circle at center, #ffffff, #b0b0b0); }
.badge-text { transform: translateY(0.5px); }
.auth-card:hover .auth-badge { transform: translateY(-2px) scale(1.08); box-shadow: 0 0 30px rgba(255, 255, 255, 0.4); border-color: rgba(255, 255, 255, 0.6); background: radial-gradient( circle at left, rgba(255, 255, 255, 0.25), transparent 60% ), rgba(0, 0, 0, 0.85); }
.auth-title { margin-top: 12px; font-size: 24px; color: white; }
.auth-card:hover .badge-dot { transform: scale(0.925); }
.auth-card:hover .badge-text { transform: translateY(0.5px) scale(0.925); }
.auth-subtitle { margin-top: 6px; font-size: 12px; opacity: 0.72; color: #ccc; }
.auth-form { display: flex; flex-direction: column; gap: 14px; }
.step-container { display: flex; flex-direction: column; gap: 14px; }
.field { display: flex; flex-direction: column; gap: 4px; }
.field-label { font-size: 12px; opacity: 0.78; color: #ccc; }
.password-wrapper { position: relative; width: 100%; }
.field input { width: 100%; box-sizing: border-box; border-radius: 999px; border: 1px solid rgba(255, 255, 255, 0.12); background: rgba(5, 5, 5, 0.96); padding: 9px 14px; padding-right: 40px; color: #f5f5f5; font-size: 13px; outline: none; transition: all 0.2s ease; }
.field input::placeholder { opacity: 0.42; }
.field input:focus { border-color: rgba(255, 255, 255, 0.85); background: rgba(8, 8, 8, 0.98); box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.14); }
.code-input { text-align: center; letter-spacing: 4px; font-weight: 700; font-size: 16px; padding-right: 14px !important; }
.info-alert { background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.3); color: #e9d5ff; padding: 12px; border-radius: 12px; font-size: 13px; line-height: 1.4; text-align: center; margin-bottom: 5px; }
.eye-btn { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 4px; cursor: pointer; color: rgba(255,255,255,0.4); display: flex; align-items: center; justify-content: center; transition: color 0.2s ease; }
.eye-btn:hover { color: rgba(255,255,255,0.9); }
.error-text { margin-top: 8px; font-size: 12px; color: #ff7b7b; text-align: center; }
.success-text { margin-top: 8px; font-size: 13px; color: #4ade80; text-align: center; font-weight: bold; }
.separator { display: flex; align-items: center; text-align: center; margin: 4px 0; color: rgba(255,255,255,0.4); font-size: 11px; text-transform: uppercase; }
.separator::before, .separator::after { content: ''; flex: 1; border-bottom: 1px solid rgba(255,255,255,0.1); }
.separator span { padding: 0 10px; margin-top: -1px; }
.primary-btn, .ghost-btn { width: 100%; border-radius: 999px; border: 1px solid transparent; padding: 10px 16px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
.primary-btn { margin-top: 10px; background: #ffffff; color: #000000; border-color: rgba(255, 255, 255, 0); box-shadow: 0 10px 30px rgba(255, 255, 255, 0.1); transform: translateY(-2px); }
.primary-btn:hover:not(:disabled) { transform: translateY(0px); box-shadow: 0 5px 15px rgba(255, 255, 255, 0.2); background: #f2f2f2; }
.primary-btn:active:not(:disabled) { transform: translateY(1px); }
.primary-btn:disabled { opacity: 0.6; cursor: default; transform: none; }
.ghost-btn { background: transparent; color: #f0f0f0; border-color: rgba(255, 255, 255, 0.26); transform: translateY(-1px); }
.ghost-btn:hover { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.5); transform: translateY(0px); }
.ghost-btn:active { background: rgba(255, 255, 255, 0.08); transform: translateY(1px); }
@keyframes cardEnter { from { opacity: 0; transform: translateY(18px) scale(0.975); } to { opacity: 1; transform: translateY(0) scale(1); } }
@keyframes floatGlow1 { 0% { transform: translate(0, 0); } 50% { transform: translate(16px, 10px); } 100% { transform: translate(0, 0); } }
@keyframes floatGlow2 { 0% { transform: translate(0, 0); } 50% { transform: translate(-18px, -14px); } 100% { transform: translate(0, 0); } }
</style>