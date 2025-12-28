<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'dashboard' 
})

const auth = useAuthStore()

// Массив уведомлений (сейчас пустой)
const importantNotifications = ref([])
</script>

<template>
  <div class="dashboard-page">
    <div class="content-wrapper">
      
      <header class="welcome-section">
        <h1 class="welcome-text">
          Добро пожаловать, <span class="username">{{ auth.user?.name || 'User' }}</span>! 👋
        </h1>
      </header>

      <div class="notifications-section">
        <h2 class="section-title">Важные уведомления</h2>
        
        <div v-if="importantNotifications.length === 0" class="empty-notices">
          <div class="empty-icon-wrapper">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="empty-svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M9.29664 4.72727V5.25342C6.60683 6.35644 4.7276 9.97935 4.79579 13.1192L4.79577 14.8631C3.4188 16.6333 3.49982 19.2727 6.93518 19.2727H9.29664C9.29664 19.996 9.57852 20.6897 10.0803 21.2012C10.582 21.7127 11.2625 22 11.9721 22C12.6817 22 13.3622 21.7127 13.8639 21.2012C14.3656 20.6897 14.6475 19.996 14.6475 19.2727H17.0155C20.4443 19.2727 20.494 16.6278 19.1172 14.8576L19.1555 13.1216C19.2248 9.97811 17.3419 6.35194 14.6475 5.25049V4.72727C14.6475 4.00395 14.3656 3.31026 13.8639 2.7988C13.3622 2.28734 12.6817 2 11.9721 2C11.2625 2 10.582 2.28734 10.0803 2.7988C9.57852 3.31026 9.29664 4.00395 9.29664 4.72727ZM12.8639 4.72727C12.8639 4.72727 12.8633 4.76414 12.8622 4.78246C12.5718 4.74603 12.2759 4.72727 11.9757 4.72727C11.673 4.72727 11.3747 4.74634 11.082 4.78335C11.0808 4.76474 11.0803 4.74603 11.0803 4.72727C11.0803 4.48617 11.1742 4.25494 11.3415 4.08445C11.5087 3.91396 11.7356 3.81818 11.9721 3.81818C12.2086 3.81818 12.4354 3.91396 12.6027 4.08445C12.7699 4.25494 12.8639 4.48617 12.8639 4.72727ZM11.0803 19.2727C11.0803 19.5138 11.1742 19.7451 11.3415 19.9156C11.5087 20.086 11.7356 20.1818 11.9721 20.1818C12.2086 20.1818 12.4354 20.086 12.6027 19.9156C12.7699 19.7451 12.8639 19.5138 12.8639 19.2727H11.0803ZM17.0155 17.4545C17.7774 17.4545 18.1884 16.5435 17.6926 15.9538C17.4516 15.6673 17.3233 15.3028 17.3316 14.9286L17.3723 13.0808C17.4404 9.99416 15.0044 6.54545 11.9757 6.54545C8.94765 6.54545 6.51196 9.99301 6.57898 13.0789L6.61916 14.9289C6.62729 15.303 6.49893 15.6674 6.25806 15.9538C5.76221 16.5435 6.17325 17.4545 6.93518 17.4545H17.0155Z" fill="currentColor" />
            </svg>
          </div>
          <p class="empty-subtitle">На данный момент важных уведомлений не поступало.</p>
        </div>

        <div v-else class="notices-list">
           </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.dashboard-page {
  width: 100%;
  background-color: #000000;
  padding-bottom: 60px;
}

.content-wrapper {
  max-width: 800px;
}

/* === ПРИВЕТСТВИЕ === */
.welcome-section {
  margin-bottom: 60px;
}

.welcome-text {
  font-size: 36px;
  font-weight: 800;
  color: white;
  letter-spacing: -0.02em;
}

.username {
  background: linear-gradient(90deg, #a855f7, #6366f1);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

/* === СЕКЦИЯ УВЕДОМЛЕНИЙ === */
.section-title {
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 2px;
  color: #444;
  margin-bottom: 24px;
  font-weight: 800;
}

/* === ПУСТОЕ СОСТОЯНИЕ === */
.empty-notices {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  background: rgba(255, 255, 255, 0.015);
  border: 1px dashed rgba(255, 255, 255, 0.06);
  border-radius: 32px;
  text-align: center;
}

.empty-icon-wrapper {
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  color: #333;
}

.empty-svg {
  width: 36px;
  height: 36px;
  opacity: 0.6;
}

.empty-title {
  font-size: 18px;
  font-weight: 700;
  color: #fff;
  margin: 0 0 6px 0;
}

.empty-subtitle {
  font-size: 14px;
  color: #555;
  max-width: 320px;
  line-height: 1.5;
}

@media (max-width: 600px) {
  .welcome-text { font-size: 28px; }
  .empty-notices { padding: 40px 20px; }
}
</style>