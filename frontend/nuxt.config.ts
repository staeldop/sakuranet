import { defineNuxtConfig } from "nuxt/config"
import svgLoader from 'vite-svg-loader'

export default defineNuxtConfig({
  css: ["./app/global.css"],

  // 👇 ДОБАВИЛИ PINIA СЮДА
  modules: [
    '@pinia/nuxt',
    '@nuxtjs/tailwindcss',
  ],

  runtimeConfig: {
    public: {
      apiBase: "http://127.0.0.1:8000",
    },
  },

  // Настройки для всего приложения
  app: {
    // Твоя анимация переходов
    pageTransition: { name: 'diag', mode: 'out-in' }
  },

  // Настройки Vite для работы с SVG
  vite: {
    plugins: [
      svgLoader() // Включаем загрузчик SVG
    ]
  },

  devtools: { enabled: true },
})