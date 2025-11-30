import { defineNuxtConfig } from "nuxt/config"
import svgLoader from 'vite-svg-loader'

export default defineNuxtConfig({
  css: ["./app/global.css"],

  modules: [
    '@pinia/nuxt',
    '@nuxtjs/tailwindcss',
  ],

  runtimeConfig: {
    public: {
      apiBase: "http://127.0.0.1:8000",
    },
  },

  app: {
    // 🔥 ВАЖНО: 'page' (как в CSS), а не 'diag'
    pageTransition: { name: 'page', mode: 'out-in' },
    layoutTransition: { name: 'layout', mode: 'out-in' }
  },

  // Отключаем встроенные эксперименты, чтобы не мешали нашей CSS-анимации
  experimental: {
    viewTransition: false
  },

  vite: {
    plugins: [
      svgLoader()
    ]
  },

  devtools: { enabled: true },

  // --- 🔥 ДОБАВЛЯЕМ ВОТ ЭТО 🔥 ---
  // Настройка прокси, чтобы Nuxt знал, где Бэкенд
  nitro: {
    devProxy: {
      '/api': {
        target: 'http://127.0.0.1:8000/api',
        changeOrigin: true,
        prependPath: false,
      }
    }
  },

  // Разрешаем CORS для api маршрутов (на всякий случай)
  routeRules: {
    '/api/**': { cors: true },
  }
})