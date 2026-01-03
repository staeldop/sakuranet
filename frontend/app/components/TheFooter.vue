<script setup lang="ts">
import LogoImage from '~/assets/logo/logo-1.png'
import IconDiscord from '~/assets/icons/discord.svg?component'
import IconTelegram from '~/assets/icons/telegram.svg?component'

const currentYear = new Date().getFullYear()

// Плавный скролл наверх
const scrollToTop = () => {
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}
</script>

<template>
  <footer class="site-footer">
    <div class="footer-container">
      
      <div class="footer-top">
        <div class="brand-area">
          <img :src="LogoImage" alt="SakuraNet" class="footer-logo" @click="scrollToTop" />
          <p class="footer-desc">Удобный хостинг серверов.</p>
        </div>

        <div class="social-row">
          <a href="#" class="social-btn discord" aria-label="Discord" target="_blank">
            <IconDiscord class="social-icon" />
          </a>
          <a href="#" class="social-btn telegram" aria-label="Telegram" target="_blank">
            <IconTelegram class="social-icon" />
          </a>
        </div>
      </div>

      <div class="footer-divider"></div>

      <div class="footer-bottom">
        <nav class="footer-nav">
          <NuxtLink to="/">Главная</NuxtLink>
          <NuxtLink to="/dashboard/services">Услуги</NuxtLink>
          <NuxtLink to="/dashboard/tickets">Поддержка</NuxtLink>
          <a href="https://wiki.sakuranet.space" target="_blank">Wiki</a>
          
          <span class="dot">•</span>
          
          <NuxtLink to="/offer">Оферта</NuxtLink>
          <NuxtLink to="/privacy">Конфиденциальность</NuxtLink>
        </nav>

        <div class="copyright-text">
          &copy; {{ currentYear }} SakuraNet. All rights reserved.
        </div>
      </div>

    </div>
  </footer>
</template>

<style scoped>
.site-footer {
  width: 100%;
  background: #000000;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding: 30px 40px; 
  margin-top: auto;
  position: relative;
  z-index: 10;
  box-sizing: border-box;
}

.footer-container {
  max-width: 1200px; 
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* --- ВЕРХНЯЯ ЧАСТЬ --- */
.footer-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.brand-area {
  display: flex;
  align-items: center;
  gap: 15px;
}

.footer-logo {
  height: 32px; 
  width: auto;
  cursor: pointer;
  transition: opacity 0.2s;
}
.footer-logo:hover { opacity: 0.8; }

.footer-desc {
  color: #555;
  font-size: 13px;
  margin: 0;
  padding-left: 15px;
  border-left: 1px solid rgba(255,255,255,0.1);
}

/* --- СОЦСЕТИ --- */
.social-row { display: flex; gap: 10px; }

.social-btn {
  width: 36px; height: 36px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 8px; 
  display: flex;       
  justify-content: center;
  align-items: center;
  padding: 0; margin: 0;
  color: #777; 
  transition: all 0.2s ease;
  text-decoration: none;
}

.social-icon {
  display: block;
  fill: currentColor;
  width: 20px; /* Чуть уменьшил для эстетики внутри квадрата */
  height: 20px;
}

.social-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
.social-btn.discord:hover { color: #5865F2; border-color: rgba(88, 101, 242, 0.3); }
.social-btn.telegram:hover { color: #229ED9; border-color: rgba(34, 158, 217, 0.3); }

/* --- РАЗДЕЛИТЕЛЬ --- */
.footer-divider {
  height: 1px;
  width: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
}

/* --- НИЖНЯЯ ЧАСТЬ --- */
.footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 15px;
}

.footer-nav {
  display: flex;
  align-items: center;
  gap: 20px;
}

.footer-nav a {
  color: #777;
  font-size: 13px;
  text-decoration: none;
  transition: color 0.2s;
  font-weight: 500;
}

.footer-nav a:hover, .footer-nav a.router-link-active {
  color: #fff;
}

/* Ссылка на оферту и правила при наведении чуть розовеет (под стиль сакуры) */
.footer-nav a[href*="offer"]:hover, 
.footer-nav a[href*="privacy"]:hover {
  color: #ffb7b2; 
}

.dot { color: #333; font-size: 10px; }

.copyright-text {
  color: #444;
  font-size: 12px;
}

/* --- АДАПТИВНОСТЬ --- */
@media (max-width: 768px) {
  .site-footer { padding: 30px 20px; }
  
  .footer-top {
    flex-direction: column;
    align-items: flex-start;
    gap: 20px;
  }
  
  .footer-desc { display: none; } 
  
  .footer-bottom {
    flex-direction: column-reverse; 
    align-items: flex-start;
    gap: 20px;
  }
  
  .footer-nav {
    flex-wrap: wrap;
    gap: 15px 20px;
  }
}
</style>