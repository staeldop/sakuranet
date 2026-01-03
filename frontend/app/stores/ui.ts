// stores/ui.ts
import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', {
  state: () => ({
    isSakuraEnabled: true // По умолчанию включена
  }),
  actions: {
    toggleSakura() {
      this.isSakuraEnabled = !this.isSakuraEnabled
      // Сохраняем выбор пользователя в браузере
      if (import.meta.client) {
        localStorage.setItem('sakura_enabled', String(this.isSakuraEnabled))
      }
    },
    // Эту функцию вызовем при загрузке сайта
    initSettings() {
      if (import.meta.client) {
        const saved = localStorage.getItem('sakura_enabled')
        if (saved !== null) {
          this.isSakuraEnabled = saved === 'true'
        }
      }
    }
  }
})