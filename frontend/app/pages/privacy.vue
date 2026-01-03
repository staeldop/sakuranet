<script setup lang="ts">
definePageMeta({
  layout: 'default'
})
</script>

<template>
  <div class="legal-page-root">
    
    <div class="legal-wrapper">
      <div class="legal-header">
        <h1 class="main-title">Политика конфиденциальности</h1>
        <p class="update-date">Последнее обновление: {{ new Date().toLocaleDateString() }}</p>
      </div>

      <div class="legal-document">
        <section>
          <h2>1. Какие данные мы собираем</h2>
          <p>1.1. Мы собираем следующие данные при регистрации и использовании сервиса SakuraNet:</p>
          <ul>
            <li>Адрес электронной почты;</li>
            <li>Логин и хэш пароля;</li>
            <li>IP-адреса для журналов безопасности;</li>
            <li>Данные о платежах (сам процесс оплаты происходит на стороне платежного агрегатора, мы храним только историю транзакций).</li>
          </ul>
        </section>

        <section>
          <h2>2. Цели обработки данных</h2>
          <p>2.1. Данные используются для:</p>
          <ul>
            <li>Предоставления доступа к услугам хостинга;</li>
            <li>Обеспечения безопасности аккаунта;</li>
            <li>Уведомления о важных изменениях в работе сервиса;</li>
            <li>Технической поддержки.</li>
          </ul>
        </section>

        <section>
          <h2>3. Защита данных</h2>
          <p>3.1. Мы принимаем все необходимые технические меры для защиты данных от несанкционированного доступа. Пароли хранятся в зашифрованном виде.</p>
        </section>

        <section>
          <h2>4. Передача третьим лицам</h2>
          <p>4.1. Мы не передаем ваши личные данные третьим лицам, за исключением случаев, предусмотренных законодательством (по запросу правоохранительных органов).</p>
        </section>

        <section>
          <h2>5. Использование Cookies</h2>
          <p>5.1. Сайт использует файлы cookie для сохранения сессии авторизации пользователя и настроек интерфейса.</p>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* --- ИСПРАВЛЕНИЕ СКРОЛЛА И ОТСТУПОВ --- */

.legal-page-root {
  /* Принудительно блочное поведение, чтобы контент скроллился */
  display: block !important;
  width: 100%;
  min-height: 100vh;
  position: relative;
  z-index: 5;
  
  /* Отступ сверху берется из layout, здесь он 0 */
  padding-top: 0 !important; 
  
  padding-left: 20px;
  padding-right: 20px;
  padding-bottom: 80px;
  
  box-sizing: border-box;
}

.legal-wrapper {
  max-width: 900px;
  width: 100%;
  margin: 0 auto; /* Центрируем документ */
}

/* --- ОФОРМЛЕНИЕ (Стиль "Стекло") --- */

.legal-header {
  text-align: center;
  margin-bottom: 30px;
  padding-top: 20px; 
}

.main-title {
  font-size: 36px;
  font-weight: 800;
  margin: 0 0 10px 0;
  background: linear-gradient(135deg, #fff 30%, #ffb7b2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: -0.5px;
}

.update-date {
  font-size: 14px;
  color: #888;
  font-weight: 500;
}

.legal-document {
  background: rgba(15, 15, 15, 0.65); 
  backdrop-filter: blur(20px);         
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  padding: 50px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5); 
}

section { margin-bottom: 40px; }
section:last-child { margin-bottom: 0; }

h2 {
  font-size: 20px;
  font-weight: 700;
  color: #ffb7b2; 
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 10px;
}

h2::before {
  content: '';
  display: block;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: #ffb7b2;
  box-shadow: 0 0 10px #ffb7b2;
}

p { font-size: 16px; line-height: 1.7; color: #d4d4d4; margin-bottom: 12px; }

ul { list-style: none; padding-left: 20px; margin-top: 10px; }

li { position: relative; padding-left: 20px; margin-bottom: 8px; color: #ccc; line-height: 1.6; }
li::before { content: '—'; position: absolute; left: 0; color: #666; }

@media (max-width: 768px) {
  .legal-document { padding: 30px 20px; }
  .main-title { font-size: 28px; }
}
</style>