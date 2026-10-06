/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        maroon: { 950: '#430a1d', 900: '#7a1e3d', 800: '#8f1f42', 700: '#ab2249' },
        gold:   { 400: '#ffd843', 500: '#ffc520', 600: '#e8a400' },
      },
      fontFamily: {
        serif: ['Playfair Display', 'Georgia', 'serif'],
        sans:  ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
