<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useApiFetch } from '~/composables/useApi'

// 🔥 ВАЖНО: Используем админский лейаут, чтобы был правильный сайдбар
definePageMeta({ layout: 'admin' })

const router = useRouter()
const { data: ticketsData, pending, refresh } = await useApiFetch<any[]>('/api/admin/tickets')

// Данные с пагинацией (Laravel paginate возвращает объект { data: [...] })
const tickets = computed(() => ticketsData.value?.data || ticketsData.value || [])

const formatDate = (iso: string) => new Date(iso).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })

const getStatusColor = (status: string) => {
  if (status === 'open') return 'open'
  if (status === 'answered') return 'answered'
  return 'closed'
}

const openTicket = (id: number) => router.push(`/dashboard/admin/tickets/${id}`)
</script>

<template>
  <div class="admin-shell">
    <div class="glow glow-1" />
    
    <div class="content-wrapper">
      <div class="page-header">
        <div class="title-group">
          <div class="auth-badge mb-2">
            <span class="badge-dot" />
            <span class="badge-text">SUPPORT CENTER</span>
          </div>
          <h1 class="title">Тикеты поддержки</h1>
          <div class="subtitle">Обращения пользователей</div>
        </div>
        
        <div class="header-actions">
          <button class="btn-refresh" @click="refresh">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Обновить
          </button>
        </div>
      </div>

      <div class="glass-card table-container">
        <div class="card-glow-top" />
        
        <table class="data-table">
          <thead>
            <tr>
              <th width="60">ID</th>
              <th>Пользователь</th>
              <th>Тема</th>
              <th>Отдел</th>
              <th>Статус</th>
              <th>Обновлено</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="pending"><td colspan="6" class="state-cell">Загрузка...</td></tr>
            <tr v-else-if="tickets.length === 0"><td colspan="6" class="state-cell">Нет тикетов</td></tr>
            
            <tr 
              v-else 
              v-for="ticket in tickets" 
              :key="ticket.id" 
              @click="openTicket(ticket.id)"
              class="data-row clickable"
            >
              <td class="id-cell">#{{ ticket.id }}</td>
              <td class="user-cell">
                <div class="user-avatar">{{ ticket.user?.name ? ticket.user.name[0].toUpperCase() : 'U' }}</div>
                <div class="user-meta">
                  <span class="u-name">{{ ticket.user?.name || 'Unknown' }}</span>
                  <span class="u-email">{{ ticket.user?.email || 'No Email' }}</span>
                </div>
              </td>
              <td class="subject-cell">{{ ticket.subject }}</td>
              <td><span class="dept-badge">{{ ticket.department }}</span></td>
              <td>
                <span class="status-badge" :class="getStatusColor(ticket.status)">
                  {{ ticket.status.toUpperCase() }}
                </span>
              </td>
              <td class="date-cell">{{ formatDate(ticket.updated_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Стили адаптированы под твой admin-shell */
.admin-shell { position: relative; min-height: 100%; width: 100%; overflow: hidden; padding-bottom: 40px; }
.content-wrapper { position: relative; z-index: 10; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.glow { position: absolute; width: 600px; height: 600px; border-radius: 50%; filter: blur(100px); opacity: 0.15; pointer-events: none; z-index: 0; }
.glow-1 { top: -10%; left: -10%; background: radial-gradient(circle, #3b82f6, transparent 70%); }

.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin: 30px 0; }
.title { font-size: 28px; font-weight: 700; color: #fff; margin: 0; }
.subtitle { color: #888; font-size: 14px; margin-top: 4px; }
.auth-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 100px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); width: fit-content; }
.badge-dot { width: 6px; height: 6px; background: #3b82f6; border-radius: 50%; box-shadow: 0 0 8px #3b82f6; }
.badge-text { font-size: 10px; font-weight: 700; letter-spacing: 1px; color: #aaa; }

.btn-refresh { display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #ccc; padding: 10px 16px; border-radius: 12px; cursor: pointer; transition: 0.2s; font-size: 13px; font-weight: 600; }
.btn-refresh:hover { background: rgba(255,255,255,0.1); color: white; }

.glass-card { position: relative; background: rgba(20, 20, 20, 0.6); border: 1px solid rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border-radius: 20px; overflow: hidden; }
.card-glow-top { position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); }

.data-table { width: 100%; border-collapse: collapse; text-align: left; }
.data-table th { padding: 18px 24px; font-size: 11px; color: #666; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.05); }
.data-table td { padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px; color: #ccc; vertical-align: middle; }
.data-row.clickable { cursor: pointer; transition: background 0.2s; }
.data-row.clickable:hover { background: rgba(255,255,255,0.02); }

.id-cell { color: #444; font-family: monospace; font-size: 13px; }
.user-cell { display: flex; align-items: center; gap: 12px; }
.user-avatar { width: 32px; height: 32px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; color: white; }
.user-meta { display: flex; flex-direction: column; line-height: 1.2; }
.u-name { font-weight: 600; color: #fff; font-size: 13px; }
.u-email { font-size: 11px; color: #666; }

.dept-badge { font-size: 11px; color: #888; background: rgba(255,255,255,0.05); padding: 3px 8px; border-radius: 6px; }

.status-badge { padding: 4px 10px; border-radius: 100px; font-size: 10px; font-weight: 700; border: 1px solid transparent; }
.status-badge.open { background: rgba(59, 130, 246, 0.1); color: #60a5fa; border-color: rgba(59, 130, 246, 0.2); }
.status-badge.answered { background: rgba(34, 197, 94, 0.1); color: #4ade80; border-color: rgba(34, 197, 94, 0.2); }
.status-badge.closed { background: rgba(255, 255, 255, 0.05); color: #888; border-color: rgba(255, 255, 255, 0.1); }

.state-cell { text-align: center; padding: 40px; color: #555; }
</style>