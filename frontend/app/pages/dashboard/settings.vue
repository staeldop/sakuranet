<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useAuthStore } from '~/stores/auth'

// Импортируем только то, что осталось нужно (Key и Arrow)
import IconKey from '~/assets/icons/code.svg?component' 
import IconArrow from '~/assets/icons/arrow-right.svg?component'

definePageMeta({
  layout: 'dashboard'
})

const auth = useAuthStore()

// --- СМЕНА ПАРОЛЯ ---
const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
  code: ''
})
const passwordLoading = ref(false)
const passwordMessage = ref('')
const passwordError = ref('')

const emailCodeLoading = ref(false)
const emailCodeSent = ref(false)

const sendEmailCode = async () => {
  emailCodeLoading.value = true
  passwordError.value = ''
  try {
    await useApi('/api/user/send-password-code', { method: 'POST' })
    emailCodeSent.value = true
    alert('Код подтверждения отправлен на вашу почту')
  } catch (e: any) {
    passwordError.value = e.data?.message || 'Ошибка отправки кода'
  } finally {
    emailCodeLoading.value = false
  }
}

const changePassword = async () => {
  if (!passwordForm.code) {
    alert('Сначала запросите код подтверждения на почту')
    return
  }
  
  passwordLoading.value = true
  passwordMessage.value = ''
  passwordError.value = ''

  try {
    await useApi('/api/user/password', {
      method: 'PUT',
      body: passwordForm
    })
    
    passwordMessage.value = 'Пароль успешно обновлен'
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    passwordForm.code = ''
    emailCodeSent.value = false
  } catch (e: any) {
    if (e.data?.errors) {
      const firstKey = Object.keys(e.data.errors)[0]
      passwordError.value = e.data.errors[firstKey][0]
    } else {
      passwordError.value = e.data?.message || 'Ошибка смены пароля'
    }
  } finally {
    passwordLoading.value = false
  }
}

// --- 2FA ---
const twoFaLoading = ref(false)
const qrCodeSvg = ref('')
const recoveryCodes = ref<string[]>([])
const setupKey = ref('') 
const confirmCode = ref('') 
const showSetup = ref(false) 
const disableCode = ref('')
const showDisableInput = ref(false)

const enableTwoFaInit = async () => {
  twoFaLoading.value = true
  try {
    await useApi('/api/user/two-factor-authentication', { method: 'POST' })
    const qrRes: any = await useApi('/api/user/two-factor-qr-code')
    qrCodeSvg.value = qrRes.svg
    const keyRes: any = await useApi('/api/user/two-factor-secret-key')
    setupKey.value = keyRes.secretKey
    showSetup.value = true
  } catch (e: any) {
    console.error(e)
    alert(e.data?.message || 'Ошибка инициализации 2FA')
  } finally {
    twoFaLoading.value = false
  }
}

const confirmTwoFa = async () => {
  if (!confirmCode.value) return
  twoFaLoading.value = true
  try {
    await useApi('/api/user/confirmed-two-factor-authentication', {
      method: 'POST',
      body: { code: confirmCode.value }
    })
    const recoveryRes: any = await useApi('/api/user/two-factor-recovery-codes')
    recoveryCodes.value = recoveryRes
    showSetup.value = false
    confirmCode.value = ''
    await auth.fetchUser() 
    alert('2FA активирована!')
  } catch (e: any) {
    alert(e.data?.message || 'Неверный код')
  } finally {
    twoFaLoading.value = false
  }
}

const disableTwoFa = async () => {
  if (!showDisableInput.value) {
    showDisableInput.value = true
    return
  }
  if (!disableCode.value) {
    alert('Введите код из Google Authenticator')
    return
  }
  if(!confirm('Отключить защиту 2FA?')) return
  
  twoFaLoading.value = true
  try {
    await useApi('/api/user/two-factor-authentication', { 
      method: 'DELETE',
      body: { code: disableCode.value }
    })
    await auth.fetchUser()
    showDisableInput.value = false
    disableCode.value = ''
    recoveryCodes.value = []
    alert('2FA отключена')
  } catch (e: any) {
    alert(e.data?.message || 'Ошибка отключения')
  } finally {
    twoFaLoading.value = false
  }
}
</script>

<template>
  <div class="settings-page">
    <div class="content-wrapper">
      <div class="page-header">
        <h1>Настройки</h1>
        <p>Безопасность и управление доступом</p>
      </div>

      <div class="grid-layout">
        
        <div class="glass-panel">
          <div class="panel-header">
            <div class="icon-box"><IconKey class="icon-svg" /></div>
            <h2 class="panel-title">Смена пароля</h2>
          </div>

          <form @submit.prevent="changePassword" class="settings-form">
            <div class="form-group">
              <label>Текущий пароль</label>
              <input 
                v-model="passwordForm.current_password" 
                type="password" 
                placeholder="••••••••" 
                class="glass-input"
                required
              />
            </div>

            <div class="form-group">
              <label>Код подтверждения (Email)</label>
              <div class="verify-row">
                <input 
                  v-model="passwordForm.code" 
                  type="text" 
                  placeholder="Код из письма" 
                  class="glass-input code-input"
                  maxlength="6"
                  required
                />
                <button 
                  type="button" 
                  @click="sendEmailCode" 
                  class="cosmic-btn small" 
                  :class="{ 'sent': emailCodeSent }"
                  :disabled="emailCodeLoading"
                  title="Отправить код на почту"
                >
                  <span v-if="emailCodeLoading">...</span>
                  
                  <svg v-else class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19.2715 18.2637L20.9996 20M11.5 19H6.2C5.0799 19 4.51984 19 4.09202 18.782C3.71569 18.5903 3.40973 18.2843 3.21799 17.908C3 17.4802 3 16.9201 3 15.8V8.2C3 7.0799 3 6.51984 3.21799 6.09202C3.40973 5.71569 3.71569 5.40973 4.09202 5.21799C4.51984 5 5.0799 5 6.2 5H17.8C18.9201 5 19.4802 5 19.908 5.21799C20.2843 5.40973 20.5903 5.71569 20.782 6.09202C21 6.51984 21 7.0799 21 8.2V11M20.6067 8.26229L15.5499 11.6335C14.2669 12.4888 13.6254 12.9165 12.932 13.0827C12.3192 13.2295 11.6804 13.2295 11.0677 13.0827C10.3743 12.9165 9.73279 12.4888 8.44975 11.6335L3.14746 8.09863M20 16.5C20 17.8807 18.8807 19 17.5 19C16.1193 19 15 17.8807 15 16.5C15 15.1193 16.1193 14 17.5 14C18.8807 14 20 15.1193 20 16.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                  </svg>
                </button>
              </div>
              <small v-if="emailCodeSent" class="hint-text">Код отправлен на {{ auth.user?.email }}</small>
            </div>

            <div class="row">
              <div class="form-group">
                <label>Новый пароль</label>
                <input 
                  v-model="passwordForm.password" 
                  type="password" 
                  placeholder="Мин. 8 символов" 
                  class="glass-input"
                  required
                />
              </div>
              <div class="form-group">
                <label>Повторите</label>
                <input 
                  v-model="passwordForm.password_confirmation" 
                  type="password" 
                  placeholder="Подтверждение" 
                  class="glass-input"
                  required
                />
              </div>
            </div>

            <div v-if="passwordMessage" class="status-msg success">{{ passwordMessage }}</div>
            <div v-if="passwordError" class="status-msg error">{{ passwordError }}</div>

            <div class="action-row">
              <button type="submit" class="cosmic-btn" :disabled="passwordLoading">
                <span>{{ passwordLoading ? 'Сохранение...' : 'Обновить пароль' }}</span>
                <div class="btn-glow"></div>
              </button>
            </div>
          </form>
        </div>

        <div class="glass-panel">
          <div class="panel-header">
            <div class="icon-box blue">
                <svg class="icon-svg" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="11 23.18 9 21.179 7.589 22.589 11 26 17 20 15.59 18.59 11 23.18" fill="currentColor"></polygon> 
                    <path d="M28,30H24V28h4V16H24V8a4.0045,4.0045,0,0,0-4-4V2a6.0067,6.0067,0,0,1,6,6v6h2a2.0021,2.0021,0,0,1,2,2V28A2.0021,2.0021,0,0,1,28,30Z" fill="currentColor"></path> 
                    <path d="M20,14H18V8A6,6,0,0,0,6,8v6H4a2,2,0,0,0-2,2V28a2,2,0,0,0,2,2H20a2,2,0,0,0,2-2V16A2,2,0,0,0,20,14ZM8,8a4,4,0,0,1,8,0v6H8ZM20,28H4V16H20Z" fill="currentColor"></path>
                </svg>
            </div>
            <h2 class="panel-title">Двухфакторная защита (2FA)</h2>
          </div>

          <div class="tfa-content">
            <p class="desc">
              Дополнительный слой защиты. При входе потребуется код из Google Authenticator.
            </p>

            <div v-if="auth.user?.two_factor_confirmed_at" class="active-state">
              <div class="status-badge">
                <span class="dot"></span> Защита активна
              </div>
              
              <div class="disable-block">
                <div v-if="showDisableInput" class="verify-row mb-4">
                   <input 
                    v-model="disableCode" 
                    type="text" 
                    placeholder="Код из Google Auth" 
                    class="glass-input code-input"
                    maxlength="6"
                  />
                  <button @click="showDisableInput = false" class="cosmic-btn small danger" type="button">✕</button>
                </div>

                <div class="action-row">
                  <button @click="disableTwoFa" class="cosmic-btn danger" :disabled="twoFaLoading">
                    <span v-if="!showDisableInput">Отключить 2FA</span>
                    <span v-else>Подтвердить</span>
                    <div class="btn-glow"></div>
                  </button>
                </div>
              </div>

              <div v-if="recoveryCodes.length" class="recovery-block">
                <p class="rec-title">Коды восстановления (сохраните их):</p>
                <div class="code-grid">
                  <span v-for="code in recoveryCodes" :key="code" class="code-item">{{ code }}</span>
                </div>
              </div>
            </div>

            <div v-else class="inactive-state">
              <div v-if="!showSetup" class="action-row">
                <button @click="enableTwoFaInit" class="cosmic-btn" :disabled="twoFaLoading">
                  <span>Настроить защиту</span>
                  <div class="btn-glow"></div>
                </button>
              </div>

              <div v-else class="setup-container">
                <div class="step-box">
                  <span class="step-num">1</span>
                  <p>Сканируйте QR код в приложении</p>
                </div>

                <div class="qr-wrapper" v-html="qrCodeSvg"></div>
                
                <div class="manual-code">
                  <span>Или введите ключ:</span>
                  <code>{{ setupKey }}</code>
                </div>

                <div class="step-box">
                  <span class="step-num">2</span>
                  <p>Введите код из приложения</p>
                </div>

                <div class="verify-row">
                  <input 
                    v-model="confirmCode" 
                    type="text" 
                    placeholder="000 000" 
                    class="glass-input code-input"
                    maxlength="6"
                  />
                  <button @click="confirmTwoFa" class="cosmic-btn small" :disabled="twoFaLoading">
                    <IconArrow class="icon-svg" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.settings-page { position: relative; width: 100%; max-width: 1000px; padding-bottom: 80px; }
.content-wrapper { position: relative; z-index: 10; }
.grid-layout { display: grid; grid-template-columns: 1fr; gap: 40px; margin-top: 30px; }

.page-header { margin-bottom: 30px; }
.page-header h1 { font-size: 32px; font-weight: 700; color: white; margin: 0; }
.page-header p { color: #888; font-size: 14px; margin-top: 6px; }

.glass-panel { 
  background: rgba(12, 12, 12, 0.65); border: 1px solid rgba(255, 255, 255, 0.08); 
  backdrop-filter: blur(20px); padding: 30px; border-radius: 24px; 
  box-shadow: inset 0 0 0 1px rgba(255,255,255,0.03); 
  position: relative; overflow: hidden; 
}

.panel-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; position: relative; z-index: 2; }
.panel-title { font-size: 18px; color: white; font-weight: 600; margin: 0; }
.icon-box { 
  width: 38px; height: 38px; border-radius: 12px; 
  background: rgba(168, 85, 247, 0.1); color: #a855f7; 
  display: flex; align-items: center; justify-content: center; 
  border: 1px solid rgba(168, 85, 247, 0.2);
}
.icon-box.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-color: rgba(59, 130, 246, 0.2); }
.icon-svg { width: 20px; height: 20px; stroke-width: 2; fill: none; stroke: currentColor; }

/* Добавил стиль для иконки внутри кнопки отправки */
.btn-icon { width: 18px; height: 18px; stroke: currentColor; }

.settings-form { position: relative; z-index: 2; }
.form-group { margin-bottom: 20px; flex: 1; }
.form-group label { display: block; color: #888; font-size: 12px; margin-bottom: 8px; font-weight: 500; }
.row { display: flex; gap: 20px; }

.glass-input { 
  width: 100%; box-sizing: border-box; 
  background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); 
  padding: 12px 16px; border-radius: 12px; color: white; font-size: 14px; 
  transition: 0.2s; outline: none; 
}
.glass-input:focus { border-color: rgba(168, 85, 247, 0.5); background: rgba(168, 85, 247, 0.05); }

.action-row { margin-top: 10px; }
.cosmic-btn { 
  position: relative; display: inline-flex; align-items: center; justify-content: center; 
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); 
  border-radius: 12px; color: #fff; font-size: 14px; font-weight: 600; 
  padding: 12px 24px; cursor: pointer; overflow: hidden; transition: all 0.3s ease; 
}
.btn-glow { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(168, 85, 247, 0.4), transparent); transform: translateX(-100%); transition: transform 0.5s ease; opacity: 0.5; }
.cosmic-btn:hover:not(:disabled) { background: rgba(168, 85, 247, 0.15); border-color: rgba(168, 85, 247, 0.5); box-shadow: 0 0 20px rgba(168, 85, 247, 0.2); }
.cosmic-btn:hover:not(:disabled) .btn-glow { transform: translateX(100%); }
.cosmic-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.cosmic-btn.danger:hover { background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.5); box-shadow: 0 0 20px rgba(239, 68, 68, 0.2); color: #fca5a5; }
.cosmic-btn.small { padding: 0; width: 44px; height: 44px; flex-shrink: 0; }
.cosmic-btn.sent { border-color: #4ade80; color: #4ade80; background: rgba(74, 222, 128, 0.1); }

.status-msg { font-size: 13px; padding: 12px; border-radius: 10px; margin-bottom: 20px; }
.status-msg.success { background: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }
.status-msg.error { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }

.desc { color: #888; font-size: 14px; line-height: 1.5; margin-bottom: 25px; }
.active-state { background: rgba(34, 197, 94, 0.05); border-radius: 16px; padding: 20px; border: 1px dashed rgba(34, 197, 94, 0.2); }
.status-badge { display: flex; align-items: center; gap: 8px; color: #4ade80; font-weight: 600; margin-bottom: 15px; }
.dot { width: 8px; height: 8px; background: #4ade80; border-radius: 50%; box-shadow: 0 0 8px #4ade80; }

.setup-container { margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); }
.step-box { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
.step-num { width: 24px; height: 24px; background: #333; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: white; }
.step-box p { color: #ccc; font-size: 14px; margin: 0; }

.qr-wrapper { background: white; padding: 10px; border-radius: 12px; width: fit-content; margin: 0 auto 20px auto; }
.manual-code { background: rgba(0,0,0,0.3); padding: 10px; border-radius: 8px; font-size: 13px; text-align: center; margin-bottom: 20px; color: #888; }
.manual-code code { display: block; color: white; font-family: monospace; font-size: 15px; margin-top: 4px; letter-spacing: 1px; }

.verify-row { display: flex; gap: 10px; max-width: 300px; }
.code-input { letter-spacing: 4px; text-align: center; font-weight: bold; font-size: 18px; }
.mb-4 { margin-bottom: 16px; }
.hint-text { display: block; margin-top: 8px; color: #4ade80; font-size: 12px; }

.recovery-block { margin-top: 20px; }
.rec-title { font-size: 13px; color: #888; margin-bottom: 10px; }
.code-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.code-item { background: rgba(0,0,0,0.4); padding: 8px; text-align: center; border-radius: 8px; font-family: monospace; color: #ccc; border: 1px solid rgba(255,255,255,0.05); }

@media (min-width: 1024px) {
  .grid-layout { grid-template-columns: 1fr 1fr; }
}
</style>