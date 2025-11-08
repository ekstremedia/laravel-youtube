/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.js",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        youtube: {
          red: '#FF0000',
          dark: '#282828',
        },
        purple: {
          950: '#1a0033',
          900: '#2d0052',
          850: '#3d0066',
          800: '#4c0080',
          750: '#5c0099',
          700: '#6b00b3',
          650: '#7a00cc',
          600: '#8a00e6',
          550: '#9900ff',
          500: '#a31aff',
          450: '#ad33ff',
          400: '#b84dff',
          350: '#c266ff',
          300: '#cc80ff',
          250: '#d699ff',
          200: '#e0b3ff',
          150: '#ebccff',
          100: '#f5e6ff',
          50: '#faf5ff',
        },
        dark: {
          bg: '#0a0014',
          card: '#140a1f',
          hover: '#1f0f2e',
          border: '#2d1a40',
          text: {
            primary: '#f5e6ff',
            secondary: '#d699ff',
            muted: '#9966cc',
          }
        }
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
      },
      animation: {
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'fade-in': 'fadeIn 0.5s ease-in-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'glow': 'glow 2s ease-in-out infinite alternate',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        glow: {
          '0%': { boxShadow: '0 0 5px rgba(153, 0, 255, 0.5)' },
          '100%': { boxShadow: '0 0 20px rgba(153, 0, 255, 0.8), 0 0 30px rgba(153, 0, 255, 0.4)' },
        }
      },
      backdropBlur: {
        xs: '2px',
      },
      backgroundImage: {
        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
        'gradient-purple': 'linear-gradient(135deg, #2d0052 0%, #6b00b3 100%)',
        'gradient-dark': 'linear-gradient(135deg, #0a0014 0%, #1f0f2e 100%)',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}