<script setup lang="ts">
definePageMeta({
  layout: 'default'
})
</script>

<template>
  <div class="legal-page-root">
    
    <div class="legal-wrapper">
      <div class="legal-header">
        <h1 class="main-title">Публичная оферта</h1>
        <p class="update-date">Последнее обновление: {{ new Date().toLocaleDateString() }}</p>
      </div>

      <div class="legal-document">
        <section>
          <h2>1. Общие положения</h2>
          <p>1.1. Настоящий документ является публичным предложением (офертой) сервиса <strong>SakuraNet</strong> (далее — Исполнитель) к заключению договора на оказание услуг хостинга и предоставления доступа к программному обеспечению.</p>
          <p>1.2. Акцептом (принятием) оферты признается регистрация Пользователя на сайте sakuranet.space или оплата услуг.</p>
        </section>

        <section>
          <h2>2. Предмет договора</h2>
          <p>2.1. Исполнитель обязуется предоставлять Пользователю вычислительные мощности (виртуальные серверы), игровые хостинг-услуги и иные сопутствующие услуги, а Пользователь обязуется оплачивать их согласно тарифам.</p>
        </section>

        <section>
          <h2>3. Оплата и возврат средств</h2>
          <p>3.1. Услуги оплачиваются путем пополнения внутреннего баланса в личном кабинете.</p>
          <p>3.2. Возврат средств возможен только при наличии технической неисправности со стороны Исполнителя, которая делает невозможным использование услуги более 24 часов.</p>
          <p>3.3. При блокировке аккаунта за нарушение правил (см. п. 4) средства не возвращаются.</p>
        </section>

        <section>
          <h2>4. Запрещенные действия</h2>
          <p>4.1. Пользователю запрещено использовать услуги для:</p>
          <ul>
            <li>Организации DDoS-атак и сканирования портов;</li>
            <li>Размещения фишинговых сайтов, вредоносного ПО;</li>
            <li>Распространения материалов, нарушающих авторские права или законодательство РФ.</li>
          </ul>
        </section>

        <section>
          <h2>5. Ограничение ответственности</h2>
          <p>5.1. Услуги предоставляются «как есть» (AS IS). Исполнитель не гарантирует, что услуги будут соответствовать всем ожиданиям Пользователя.</p>
          <p>5.2. Исполнитель не несет ответственности за убытки Пользователя, возникшие в результате использования или невозможности использования сервиса.</p>
        </section>

        <section>
          <h2>6. Контакты</h2>
          <p>По всем вопросам можно обращаться в поддержку через систему тикетов или по электронной почте: <a href="mailto:support@sakuranet.space">support@sakuranet.space</a></p>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* --- ИСПРАВЛЕНИЕ ОТСТУПОВ --- */

.legal-page-root {
  /* Важно: block позволяет скроллить контент, если он не влезает */
  display: block !important;
  width: 100%;
  min-height: 100vh;
  position: relative;
  z-index: 5;
  
  /* Убираем лишний отступ сверху, так как он есть в layout */
  padding-top: 0 !important; 
  
  /* Отступы по бокам и снизу */
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

/* --- Оформление --- */
.legal-header {
  text-align: center;
  margin-bottom: 30px;
  /* Небольшой отступ сверху для красоты */
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

a { color: #ffb7b2; text-decoration: none; border-bottom: 1px dashed rgba(255, 183, 178, 0.4); transition: all 0.2s; }
a:hover { color: #fff; border-bottom-color: #fff; }

@media (max-width: 768px) {
  .legal-document { padding: 30px 20px; }
  .main-title { font-size: 28px; }
}
</style>