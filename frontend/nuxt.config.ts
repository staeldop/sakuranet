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
      // 👇 ВРЕМЕННО ставим http (пока не настроим SSL)
      apiBase: "https://billing.sakuranet.space", 
    },
  },

  app: {
    pageTransition: { name: 'page', mode: 'out-in' },
    layoutTransition: { name: 'layout', mode: 'out-in' }
  },

  experimental: {
    viewTransition: false
  },

  vite: {
    plugins: [
      svgLoader()
    ],
    server: {
      allowedHosts: ['billing.sakuranet.space', 'www.billing.sakuranet.space']
    }
  },

  devtools: { enabled: true },

  nitro: {
    devProxy: {
      '/api': {
        // 👇 Исправили порт 8000 на 80 (Nginx)
        target: 'http://127.0.0.1/api', 
        changeOrigin: true,
        prependPath: false,
      }
    }
  },

  routeRules: {
    '/api/**': { cors: true },
  }
})
