<template>
  <div class="admin-layout">
    <AdminSidebar />
    <main class="admin-content">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
  background: #050505; /* Темный фон для админки */
  color: #fff;
}

.admin-content {
  flex-grow: 1;
  margin-left: 260px; /* Отступ под ширину сайдбара */
  padding: 40px;
  overflow-y: auto;
}

/* Адаптив для мобилок (если нужно) */
@media (max-width: 768px) {
  .admin-content {
    margin-left: 0;
    padding: 20px;
    padding-top: 80px; /* Если будет мобильный хедер */
  }
  /* Здесь можно добавить стили для скрытия/показа сайдбара на мобильных,
     но для админки это часто не критично, так как работают с ПК */
}
</style>