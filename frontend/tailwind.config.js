/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        maroon: {
          50: '#fdf2f4',
          100: '#fce7eb',
          200: '#f9d0d8',
          300: '#f4a9b8',
          400: '#ec7894',
          500: '#e04d72',
          600: '#cc2e58',
          700: '#ab2249',
          800: '#8f1f42',
          900: '#7a1e3d',
          950: '#430a1d',
        },
        gold: {
          50: '#fffef2',
          100: '#fffbe0',
          200: '#fff5b8',
          300: '#ffea80',
          400: '#ffd843',
          500: '#ffc520',
          600: '#e8a400',
          700: '#c07d00',
          800: '#9c6100',
          900: '#7e4f00',
          950: '#4a2b00',
        },
      },
      fontFamily: {
        serif: ['Playfair Display', 'Georgia', 'serif'],
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      boxShadow: {
        'gold': '0 0 20px rgba(255, 197, 32, 0.3)',
        'gold-lg': '0 0 40px rgba(255, 197, 32, 0.4)',
        'maroon': '0 10px 30px rgba(122, 30, 61, 0.3)',
      },
      backgroundImage: {
        'gold-gradient': 'linear-gradient(135deg, #ffd843 0%, #ffc520 50%, #e8a400 100%)',
        'maroon-gradient': 'linear-gradient(135deg, #7a1e3d 0%, #430a1d 100%)',
      },
    },
  },
  plugins: [],
}