<template>
  <div class="admin-layout">
    <!-- Наш новый компонент сайдбара -->
    <AdminSidebar />

    <!-- Основной контент -->
    <main class="admin-content">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
  width: 100vw;
  background-color: #000; /* Глубокий черный фон для админки */
  color: #fff;
}

.admin-content {
  flex-grow: 1;
  /* Отступ слева = ширине сайдбара (280px) */
  margin-left: 280px; 
  padding: 40px;
  
  /* Чтобы контент не растягивал страницу вширь при анимациях */
  overflow-x: hidden; 
  min-height: 100vh;
}

/* Адаптив для мобилок (если понадобится) */
@media (max-width: 768px) {
  .admin-content {
    margin-left: 0;
    padding: 20px;
    padding-top: 80px; /* Отступ под мобильную шапку, если она будет */
  }
}
</style>