import type { Config } from 'tailwindcss'
import defaultTheme from 'tailwindcss/defaultTheme'

export default <Config>{
  content: [
    "./components/**/*.{js,vue,ts}",
    "./layouts/**/*.vue",
    "./pages/**/*.vue",
    "./plugins/**/*.{js,ts}",
    "./app.vue",
    "./error.vue",
  ],
  theme: {
    extend: {
      fontFamily: {
        // Ставим Nunito главным, затем системные (как на сайте-доноре)
        sans: ['Nunito', 'ui-sans-serif', 'system-ui', 'sans-serif', ...defaultTheme.fontFamily.sans],
      },
    },
  },
  corePlugins: {
    preflight: false, // Оставляем выключенным, чтобы не ломать старую верстку
  },
  plugins: [],
}