<script setup lang="ts">
import LogoImage from '~/assets/logo/logo-2.png'
</script>

<template>
  <footer class="site-footer">
    <div class="footer-container">
      
      <div class="footer-col brand-col">
        <img :src="LogoImage" alt="SakuraNet" class="footer-logo" />
        <p class="footer-desc">
          Мощный хостинг игровых серверов с защитой от DDoS и удобной панелью управления. Создайте свой мир уже сегодня.
        </p>
        <div class="copyright-text">
          &copy; {{ new Date().getFullYear() }} SakuraNet. Все права защищены.
        </div>
      </div>

      <div class="footer-col">
        <h4 class="col-title">Навигация</h4>
        <ul class="link-list">
          <li><NuxtLink to="/">Главная</NuxtLink></li>
          <li><NuxtLink to="/dashboard/services">Услуги</NuxtLink></li>
          <li><a href="https://wiki.sakuranet.space" target="_blank">База знаний</a></li>
          <li><NuxtLink to="/dashboard/tickets">Поддержка</NuxtLink></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4 class="col-title">Документы</h4>
        <ul class="link-list">
          <li><a href="#">Публичная оферта</a></li>
          <li><a href="#">Политика конфиденциальности</a></li>
          <li><a href="#">Правила сервиса</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4 class="col-title">Мы в соцсетях</h4>
        <div class="social-row">
          <a href="#" class="social-btn discord" aria-label="Discord">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.419 0 1.334-.956 2.419-2.157 2.419zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.419 0 1.334-.946 2.419-2.157 2.419z"/></svg>
          </a>
          <a href="#" class="social-btn telegram" aria-label="Telegram">
             <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 0 0-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .24z"/></svg>
          </a>
          <a href="#" class="social-btn vk" aria-label="VK">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M15.684 13.568c1.076.994 2.164 1.977 2.38 2.234.343.407.394.887.054 1.275-.386.44-1.05.518-1.926.514-1.076-.005-1.936-.532-3.033-1.157-.756-.43-1.288-.236-1.97.447-.293.293-.574.582-.873.86-.34.316-.62.457-1.04.382-2.12-.375-3.692-1.517-5.06-3.09-2.072-2.38-3.906-6.13-3.945-8.877-.004-.303.11-.58.463-.593 1.045-.038 2.05-.028 2.812-.022.56.005.823.238.995.69 1.155 3.037 2.825 5.56 4.37 6.368.625.328.777.172.846-1.134.032-.61.088-1.22.062-1.83-.046-1.144-.352-1.442-1.17-1.56-.27-.04-.216-.402.096-.583.473-.274 1.62-.294 2.338-.28 1.05.02 1.162.535 1.196 1.487.028.784-.012 1.63.36 1.944.205.173.655.066 1.072-.647.962-1.644 1.66-3.79 1.838-5.323.062-.527.39-.75 1.062-.77 1.07-.035 2.158.01 2.857.027.64.016.85.317.653.978-.453 1.514-1.545 3.193-2.477 4.502-.676.95-1.13 1.68-.05 2.766z"/></svg>
          </a>
        </div>
      </div>

    </div>
  </footer>
</template>

<style scoped>
.site-footer {
  /* ГАРАНТИЯ РАСТЯГИВАНИЯ */
  width: 100% !important;
  min-width: 100%;
  flex-shrink: 0; /* Не дает футеру сжиматься, если контент давит */
  box-sizing: border-box;

  background: #050505;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  padding: 60px 0 40px 0;
  margin-top: auto; 
}

.footer-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 40px;
}

/* Бренд */
.footer-logo { height: 26px; margin-bottom: 16px; opacity: 0.9; }
.footer-desc { color: #666; font-size: 13px; line-height: 1.6; max-width: 280px; margin-bottom: 24px; }
.copyright-text { color: #444; font-size: 12px; }

/* Заголовки */
.col-title { color: #fff; font-size: 13px; font-weight: 700; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.5px; }

/* Ссылки */
.link-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px; }
.link-list a { 
  color: #777; font-size: 13px; text-decoration: none; transition: 0.2s; 
  display: inline-block;
}
.link-list a:hover { color: #fff; transform: translateX(2px); }

/* Соцсети */
.social-row { display: flex; gap: 10px; }
.social-btn {
  width: 36px; height: 36px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: #777; transition: all 0.2s ease;
}
.social-btn:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateY(-2px); }

/* Цвета брендов при наведении */
.social-btn.discord:hover { background: #5865F2; border-color: #5865F2; }
.social-btn.telegram:hover { background: #229ED9; border-color: #229ED9; }
.social-btn.vk:hover { background: #0077FF; border-color: #0077FF; }

/* Адаптив */
@media (max-width: 900px) {
  .footer-container { grid-template-columns: 1fr 1fr; gap: 40px; }
  .brand-col { grid-column: 1 / -1; margin-bottom: 10px; }
}
@media (max-width: 500px) {
  .footer-container { grid-template-columns: 1fr; gap: 30px; }
}
</style>