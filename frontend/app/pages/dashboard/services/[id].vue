<script setup lang="ts">
import { ref, onMounted } from 'vue' // Убрал лишние импорты для 3D
import { useRoute, useRouter } from 'vue-router'
import { $api } from '~/composables/useApi'
import ModalConfirm from '~/components/ModalConfirm.vue'

// Иконки
import IconArrow from '~/assets/icons/arrow-right.svg?component'
import IconTrash from '~/assets/icons/trash.svg?component'
import IconCpu from '~/assets/icons/cpu.svg?component'
import IconRam from '~/assets/icons/ram.svg?component'
import IconDisk from '~/assets/icons/disk.svg?component'
import IconExternal from '~/assets/icons/gamepad.svg?component'

definePageMeta({ layout: 'dashboard' })

// --- ТИПИЗАЦИЯ ---
interface Service {
  id: number
  identifier: string
  name: string
  status: 'active' | 'stopped' | 'suspended'
  core: number
  price_monthly: string | number
  created_at: string
  expires_at: string
  auto_renew: boolean
  product?: { name: string }
}

const route = useRoute()
const router = useRouter()
const service = ref<Service | null>(null)
const isLoading = ref(true)
const showPassword = ref(false)
const isDeleteModalOpen = ref(false)
const isDeleting = ref(false)
const isRenewLoading = ref(false)
const copiedField = ref<string | null>(null)

// --- УДАЛИЛ ЛОГИКУ 3D КАРТОЧКИ (она больше не нужна) ---

// --- API ACTIONS ---
const fetchService = async () => {
  if (!route.params.id) return router.push('/dashboard/services')
  isLoading.value = true
  try {
    const response = await $api<any>(`/api/services/${route.params.id}`)
    service.value = response
    if (service.value && service.value.auto_renew === undefined) service.value.auto_renew = false 
  } catch (e) { console.error(e) } finally { isLoading.value = false }
}

const toggleAutoRenew = async () => {
  if (!service.value) return
  const oldState = service.value.auto_renew
  service.value.auto_renew = !oldState
  isRenewLoading.value = true
  try {
    await $api(`/api/services/${service.value.id}/toggle-renew`, { method: 'POST', body: { auto_renew: !oldState } })
  } catch (e) {
    service.value.auto_renew = oldState
  } finally { isRenewLoading.value = false }
}

const requestDelete = () => { isDeleteModalOpen.value = true }
const confirmDelete = async () => {
  isDeleting.value = true
  try {
    if(service.value) await $api(`/api/services/${service.value.id}`, { method: 'DELETE' })
    router.push('/dashboard/services')
  } catch (e) { isDeleteModalOpen.value = false } finally { isDeleting.value = false }
}

const copyToClipboard = (text: string, field: string) => { 
  if (!text) return
  navigator.clipboard.writeText(text)
  copiedField.value = field
  setTimeout(() => { copiedField.value = null }, 2000)
}

const formatDateFull = (dateStr?: string) => {
  if (!dateStr) return '...'
  return new Date(dateStr).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' })
}
const goToPanel = () => { window.open('https://panel.sakuranet.space', '_blank') }

onMounted(() => { fetchService() })
</script>

<template>
  <div class="cyber-page">
    
    <ModalConfirm 
      :is-open="isDeleteModalOpen"
      title="Уничтожить сервер?"
      message="Внимание! Это действие удалит все данные безвозвратно."
      :loading="isDeleting"
      @close="isDeleteModalOpen = false"
      @confirm="confirmDelete"
    />

    <transition name="fade" mode="out-in">
      
      <div v-if="isLoading" class="loading-state" key="loading">
        <div class="loader-ring">
          <div></div><div></div><div></div><div></div>
        </div>
        <p class="blink-text">УСТАНОВКА СОЕДИНЕНИЯ...</p>
      </div>

      <div v-else-if="service" class="content-wrapper" key="content">
        
        <div class="page-header slide-in-top">
          <button @click="router.push('/dashboard/services')" class="back-btn">
            <IconArrow class="icon-back" /> <span>НАЗАД К СПИСКУ</span>
          </button>
          
          <div class="header-content">
            <div class="header-left">
              <h1 class="page-title">{{ service.name }}</h1>
              <div class="id-badge">
                <span class="id-label">ID СЕРВЕРА:</span>
                <span class="id-val">{{ service.identifier }}</span>
              </div>
            </div>
            
            <button class="cyber-action-btn" @click="goToPanel">
              <span class="btn-text">ОТКРЫТЬ ТЕРМИНАЛ</span>
              <IconExternal class="btn-icon" />
              <div class="btn-bg-glitch"></div>
            </button>
          </div>
        </div>

        <div class="grid-layout">
          
          <div class="left-col">
            
            <div class="server-status-panel stagger-1">
              <div class="status-glow-bg" :class="service.status"></div>
              
              <div class="panel-content">
                <div class="status-header">
                  <span class="panel-label">ТЕКУЩЕЕ СОСТОЯНИЕ</span>
                  <div class="live-indicator" :class="service.status">
                    <span class="dot"></span>
                    <span class="ping-ring"></span>
                  </div>
                </div>

                <div class="status-main-text" :class="service.status">
                  {{ service.status === 'active' ? 'ONLINE' : 'OFFLINE' }}
                </div>

                <div class="status-footer">
                   <div class="footer-item">
                     <span class="f-label">АПТАЙМ</span>
                     <span class="f-val">99.9%</span>
                   </div>
                   <div class="vertical-line"></div>
                   <div class="footer-item">
                     <span class="f-label">РЕГИОН</span>
                     <span class="f-val">MSK-1</span>
                   </div>
                   <div class="vertical-line"></div>
                   <div class="footer-item">
                     <span class="f-label">СИСТЕМА</span>
                     <span class="f-val ok" v-if="service.status === 'active'">НОРМА</span>
                     <span class="f-val err" v-else>СТОП</span>
                   </div>
                </div>
              </div>
            </div>
            <div class="glass-panel hud-panel stagger-2">
              <div class="hud-corner top-left"></div>
              <div class="hud-corner top-right"></div>
              <div class="hud-corner bottom-left"></div>
              <div class="hud-corner bottom-right"></div>

              <h2 class="panel-title">Протокол доступа (SFTP)</h2>
              
              <div class="fields-grid">
                <div class="form-group">
                  <label>ПОЛЬЗОВАТЕЛЬ</label>
                  <div class="input-wrapper">
                    <input readonly :value="`user_${service.id}`" class="glass-input mono" />
                    <button class="copy-btn" @click="copyToClipboard(`user_${service.id}`, 'user')">
                      <svg v-if="copiedField === 'user'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="icon-success"><polyline points="20 6 9 17 4 12"></polyline></svg>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    </button>
                  </div>
                </div>

                <div class="form-group">
                  <label>ПАРОЛЬ</label>
                  <div class="input-wrapper">
                    <input readonly :type="showPassword ? 'text' : 'password'" value="*******" class="glass-input mono" />
                    <button class="copy-btn text-btn" @click="showPassword = !showPassword">
                      {{ showPassword ? 'СКРЫТЬ' : 'ПОКАЗАТЬ' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="glass-panel mt-6 stagger-3">
               <h2 class="panel-title">Выделенные ресурсы</h2>
               <div class="specs-grid">
                  <div class="spec-item">
                    <IconCpu class="spec-icon" />
                    <div class="spec-info">
                      <span class="s-label">ЯДРА (vCPU)</span>
                      <span class="s-val">{{ service.core }}</span>
                    </div>
                  </div>
                  <div class="spec-item">
                    <IconRam class="spec-icon" />
                    <div class="spec-info">
                      <span class="s-label">ТАРИФ</span>
                      <span class="s-val">{{ service.product?.name || 'CUSTOM' }}</span>
                    </div>
                  </div>
                  <div class="spec-item">
                    <IconDisk class="spec-icon" />
                    <div class="spec-info">
                      <span class="s-label">ЦЕНА В МЕСЯЦ</span>
                      <span class="s-val">{{ Number(service.price_monthly).toFixed(0) }} ₽</span>
                    </div>
                  </div>
               </div>
            </div>

          </div>

          <div class="right-col">
            
            <div class="glass-panel billing-panel stagger-4">
              <div class="glow-blue-bottom"></div>
              <h2 class="panel-title">Биллинг</h2>
              
              <div class="billing-info">
                <div class="b-row">
                  <span>СОЗДАН</span>
                  <span class="mono">{{ formatDateFull(service.created_at) }}</span>
                </div>
                <div class="b-row highlight">
                  <span>ИСТЕКАЕТ</span>
                  <span class="mono text-white">{{ formatDateFull(service.expires_at) }}</span>
                </div>
              </div>

              <div class="divider"></div>

              <div class="auto-renew-row" @click="toggleAutoRenew">
                <div class="ar-info">
                  <span class="ar-title">Автопродление</span>
                  <span class="ar-desc">Автоматическое списание средств</span>
                </div>
                <div class="cyber-toggle" :class="{ active: service.auto_renew }">
                  <div class="toggle-track"></div>
                  <div class="toggle-thumb"></div>
                </div>
              </div>

              <button class="cosmic-btn w-full mt-6">
                <span class="relative z-10">ПРОДЛИТЬ ВРУЧНУЮ</span>
                <div class="btn-glow"></div>
              </button>
            </div>

            <div class="danger-panel mt-6 stagger-5">
              <div class="hazard-stripes"></div>
              <h3 class="danger-title">ОПАСНАЯ ЗОНА</h3>
              <p class="danger-desc">Удаление сервиса приведет к безвозвратной потере всех данных.</p>
              
              <button class="danger-btn" @click="requestDelete">
                <IconTrash class="d-icon" /> УДАЛИТЬ СЕРВИС
              </button>
            </div>

          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
/* --- СТИЛИ ДЛЯ НОВОЙ СТАТУС-КАРТОЧКИ --- */
.server-status-panel {
  position: relative;
  width: 100%;
  border-radius: 12px;
  background: rgba(15, 15, 20, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  overflow: hidden;
  margin-bottom: 30px;
  backdrop-filter: blur(10px);
}

.status-glow-bg {
  position: absolute;
  top: -50px;
  right: -50px;
  width: 200px;
  height: 200px;
  filter: blur(80px);
  opacity: 0.25;
  transition: 0.5s;
  border-radius: 50%;
}
.status-glow-bg.active { background: #22c55e; }
.status-glow-bg.stopped { background: #ef4444; }

.panel-content {
  position: relative;
  z-index: 2;
  padding: 24px 30px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.status-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.panel-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1px;
  color: #555;
}

.live-indicator {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 12px;
  height: 12px;
}
.live-indicator .dot {
  width: 8px;
  height: 8px;
  background: #444;
  border-radius: 50%;
  z-index: 2;
}
.live-indicator .ping-ring {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  opacity: 0;
  z-index: 1;
}

/* Анимация статуса */
.live-indicator.active .dot { background: #22c55e; box-shadow: 0 0 10px rgba(34, 197, 94, 0.6); }
.live-indicator.active .ping-ring {
  background: #22c55e;
  animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
}
.live-indicator.stopped .dot { background: #ef4444; box-shadow: 0 0 10px rgba(239, 68, 68, 0.6); }

@keyframes ping {
  75%, 100% { transform: scale(2.5); opacity: 0; }
}

.status-main-text {
  font-family: 'JetBrains Mono', monospace;
  font-size: 36px;
  font-weight: 800;
  letter-spacing: -1px;
  color: #333;
  transition: 0.3s;
}
.status-main-text.active {
  color: #fff;
  text-shadow: 0 0 30px rgba(34, 197, 94, 0.3);
}
.status-main-text.stopped {
  color: #ef4444;
  text-shadow: 0 0 30px rgba(239, 68, 68, 0.3);
}

.status-footer {
  display: flex;
  align-items: center;
  gap: 16px;
  padding-top: 16px;
  border-top: 1px solid rgba(255,255,255,0.05);
}

.footer-item { display: flex; flex-direction: column; gap: 2px; }
.f-label { font-size: 10px; color: #555; font-weight: 600; letter-spacing: 0.5px; }
.f-val { font-size: 13px; color: #bbb; font-weight: 500; font-family: 'JetBrains Mono', monospace; }
.f-val.ok { color: #22c55e; }
.f-val.err { color: #ef4444; }

.vertical-line {
  width: 1px;
  height: 20px;
  background: rgba(255,255,255,0.05);
}

/* --- ОСТАЛЬНЫЕ СТИЛИ (ТВОИ СТАРЫЕ) --- */
.cyber-page { 
  position: relative; 
  width: 100%; 
  max-width: 1350px; 
  margin: 0; 
  padding-bottom: 100px; 
  font-family: 'Inter', sans-serif; 
}

.content-wrapper { position: relative; z-index: 10; padding: 0 0; }
.mono { font-family: 'JetBrains Mono', monospace; }
.mt-6 { margin-top: 24px; }
.w-full { width: 100%; }

/* Grid */
.grid-layout { display: grid; grid-template-columns: 1fr; gap: 30px; }
@media(min-width: 1024px) { .grid-layout { grid-template-columns: 1.3fr 0.7fr; gap: 40px; } }

/* FADE TRANSITION */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Animations */
@keyframes slideUpFade {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.slide-in-top { animation: slideUpFade 0.6s ease-out forwards; }
.stagger-1 { animation: slideUpFade 0.6s ease-out 0.1s forwards; opacity: 0; }
.stagger-2 { animation: slideUpFade 0.6s ease-out 0.2s forwards; opacity: 0; }
.stagger-3 { animation: slideUpFade 0.6s ease-out 0.3s forwards; opacity: 0; }
.stagger-4 { animation: slideUpFade 0.6s ease-out 0.4s forwards; opacity: 0; }
.stagger-5 { animation: slideUpFade 0.6s ease-out 0.5s forwards; opacity: 0; }

/* HEADER */
.page-header { margin-bottom: 40px; }
.back-btn { 
  background: none; border: none; color: #666; display: flex; align-items: center; gap: 8px; 
  font-size: 11px; font-weight: 700; cursor: pointer; letter-spacing: 1.5px; transition: 0.3s; margin-bottom: 20px; 
}
.back-btn:hover { color: #a855f7; gap: 12px; }
.icon-back { width: 12px; transform: rotate(180deg); }

.header-content { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px; }

.id-badge { 
  display: flex; align-items: center; gap: 8px; 
  font-family: 'JetBrains Mono', monospace; font-size: 12px; margin-top: 8px; 
}
.id-label { color: #555; }
.id-val { color: #888; background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px; }

.page-title { 
  font-size: 42px; font-weight: 900; color: white; margin: 0; letter-spacing: -1px; text-transform: uppercase; line-height: 1;
}

.cyber-action-btn {
  background: rgba(0,0,0,0.6); border: 1px solid #a855f7; color: #a855f7;
  border-radius: 4px; padding: 0 28px; height: 48px; display: flex; align-items: center; gap: 10px;
  cursor: pointer; position: relative; overflow: hidden; transition: all 0.3s ease;
}
.cyber-action-btn:hover { background: #a855f7; color: white; box-shadow: 0 0 30px rgba(168, 85, 247, 0.4); }
.btn-text { font-weight: 800; font-size: 12px; letter-spacing: 1px; z-index: 2; }
.btn-icon { width: 16px; height: 16px; z-index: 2; }

/* PANELS */
.glass-panel {
  background: rgba(10, 10, 10, 0.6); border: 1px solid rgba(255, 255, 255, 0.06);
  backdrop-filter: blur(16px); padding: 25px; border-radius: 1px; position: relative;
}
.hud-panel { border: 1px solid rgba(255,255,255,0.03); border-radius: 0; }
.hud-corner { position: absolute; width: 10px; height: 10px; border-color: rgba(255,255,255,0.2); border-style: solid; transition: 0.3s; }
.top-left { top: -1px; left: -1px; border-width: 2px 0 0 2px; }
.top-right { top: -1px; right: -1px; border-width: 2px 2px 0 0; }
.bottom-left { bottom: -1px; left: -1px; border-width: 0 0 2px 2px; }
.bottom-right { bottom: -1px; right: -1px; border-width: 0 2px 2px 0; }
.glass-panel:hover .hud-corner { border-color: #a855f7; width: 20px; height: 20px; }

.panel-title { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 24px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
.panel-title::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.05); }

/* INPUTS */
.fields-grid { display: flex; flex-direction: column; gap: 20px; }
.form-group label { display: block; font-size: 10px; color: #888; margin-bottom: 8px; font-weight: 600; letter-spacing: 1px; }
.input-wrapper { display: flex; gap: 0; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; overflow: hidden; transition: 0.3s; }
.input-wrapper:focus-within { border-color: #a855f7; box-shadow: 0 0 15px rgba(168, 85, 247, 0.1); }
.glass-input { flex: 1; background: transparent; border: none; padding: 14px; color: #ddd; font-size: 13px; outline: none; }
.copy-btn { background: rgba(255,255,255,0.03); border: none; border-left: 1px solid rgba(255,255,255,0.1); color: #777; cursor: pointer; padding: 0 16px; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
.copy-btn:hover { background: rgba(255,255,255,0.1); color: white; }
.text-btn { font-size: 10px; font-weight: 700; width: 60px; }
.icon-success { color: #22c55e; }

/* SPECS */
.specs-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.spec-item { background: linear-gradient(180deg, rgba(255,255,255,0.03) 0%, transparent 100%); border: 1px solid rgba(255,255,255,0.05); padding: 16px; border-radius: 8px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; transition: 0.3s; }
.spec-item:hover { border-color: rgba(255,255,255,0.2); transform: translateY(-2px); }
.spec-icon { width: 24px; height: 24px; color: #555; }
.s-label { font-size: 9px; color: #666; letter-spacing: 1px; margin-top: 4px; display: block; }
.s-val { font-size: 14px; color: white; font-weight: 700; font-family: 'JetBrains Mono', monospace; }

/* BILLING */
.billing-info { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; }
.b-row { display: flex; justify-content: space-between; font-size: 12px; color: #777; letter-spacing: 0.5px; }
.divider { height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent); margin: 24px 0; }

.auto-renew-row { display: flex; align-items: center; justify-content: space-between; cursor: pointer; padding: 10px; margin: -10px; border-radius: 8px; transition: 0.2s; }
.auto-renew-row:hover { background: rgba(255,255,255,0.02); }
.ar-title { display: block; font-size: 13px; font-weight: 600; color: #ddd; }
.ar-desc { font-size: 11px; color: #555; }

.cyber-toggle { width: 44px; height: 24px; position: relative; }
.toggle-track { width: 100%; height: 100%; background: #222; border-radius: 20px; border: 1px solid #333; transition: 0.3s; }
.toggle-thumb { width: 18px; height: 18px; background: #555; border-radius: 50%; position: absolute; top: 3px; left: 3px; transition: 0.3s cubic-bezier(0.5, 1.5, 0.5, 1); display: flex; align-items: center; justify-content: center; }
.cyber-toggle.active .toggle-track { border-color: #a855f7; background: rgba(168, 85, 247, 0.1); }
.cyber-toggle.active .toggle-thumb { transform: translateX(20px); background: #a855f7; box-shadow: 0 0 10px #a855f7; }

/* BUTTONS */
.cosmic-btn { position: relative; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); color: white; font-weight: 700; font-size: 12px; padding: 14px; border-radius: 6px; cursor: pointer; overflow: hidden; letter-spacing: 1px; transition: 0.3s; }
.cosmic-btn:hover { border-color: #a855f7; background: rgba(168, 85, 247, 0.05); }
.btn-glow { position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); transform: skewX(-20deg); transition: 0.5s; }
.cosmic-btn:hover .btn-glow { left: 150%; transition: 0.7s; }

.danger-panel { background: rgba(20, 5, 5, 0.4); border: 1px solid rgba(239, 68, 68, 0.15); border-radius: 8px; padding: 20px; text-align: center; position: relative; overflow: hidden; }
.hazard-stripes { position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: repeating-linear-gradient(45deg, #ef4444, #ef4444 10px, transparent 10px, transparent 20px); opacity: 0.6; }
.danger-title { color: #ef4444; font-size: 12px; font-weight: 800; letter-spacing: 2px; margin-bottom: 6px; }
.danger-desc { color: #777; font-size: 11px; margin-bottom: 16px; }
.danger-btn { background: transparent; border: 1px solid #ef4444; color: #ef4444; width: 100%; padding: 12px; border-radius: 4px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s; }
.danger-btn:hover { background: #ef4444; color: white; box-shadow: 0 0 15px rgba(239, 68, 68, 0.3); }
.d-icon { width: 16px; height: 16px; min-width: 16px; flex-shrink: 0; margin-right: 8px; }

.loading-state { height: 60vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 20px; }
.loader-ring { display: inline-block; position: relative; width: 64px; height: 64px; }
.loader-ring div { box-sizing: border-box; display: block; position: absolute; width: 50px; height: 50px; margin: 8px; border: 3px solid #a855f7; border-radius: 50%; animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite; border-color: #a855f7 transparent transparent transparent; }
.loader-ring div:nth-child(1) { animation-delay: -0.45s; }
.loader-ring div:nth-child(2) { animation-delay: -0.3s; }
.loader-ring div:nth-child(3) { animation-delay: -0.15s; }
@keyframes lds-ring { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.blink-text { color: #555; font-family: monospace; letter-spacing: 2px; animation: blink 2s infinite; font-size: 12px; }
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
</style>