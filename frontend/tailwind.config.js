/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        rf: {
          page: 'var(--rf-page)',
          panel: 'var(--rf-panel)',
          sidebar: 'var(--rf-sidebar)',
          muted: 'var(--rf-muted)',
          elevated: 'var(--rf-elevated)',
          well: 'var(--rf-well)',
          wellHover: 'var(--rf-well-hover)',
          border: 'var(--rf-border)',
          text: 'var(--rf-text)',
          mutedFg: 'var(--rf-text-muted)',
          input: 'var(--rf-input-bg)',
          inputBorder: 'var(--rf-input-border)',
          meter: 'var(--rf-meter)',
          carousel: 'var(--rf-carousel)',
          orDivider: 'var(--rf-or-divider)',
        },
        brand: {
          50:  '#fdf4ff',
          100: '#fae8ff',
          200: '#f5d0fe',
          300: '#f0abfc',
          400: '#e879f9',
          500: '#d946ef',
          600: '#c026d3',
          700: '#a21caf',
          800: '#86198f',
          900: '#701a75',
          950: '#4a044e',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      animation: {
        'spin-slow': 'spin 3s linear infinite',
        'pulse-fast': 'pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite',
      },
    },
  },
  plugins: [],
}
