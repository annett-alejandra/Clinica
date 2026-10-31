/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        // Base y Superficies (Layout)
        base: '#F8FAFC',
        surface: '#FFFFFF',
        contrast: '#F1F5F9',
        borderfine: '#E2E8F0',
        
        // Tipografía y Textos
        textPrimary: '#1E293B',
        textSecondary: '#64748B',
        textMuted: '#94A3B8',
        
        // Identidad y Acciones
        primary: {
          DEFAULT: '#0284C7',
          hover: '#0369A1',
        },
        secondary: '#0D9488',
        accentSoft: '#E0F2FE',
        
        // Estados Clínicos (Semánticos)
        clinical: {
          critical: {
            DEFAULT: '#DC2626',
            light: '#FEE2E2',
            text: '#991B1B',
          },
          caution: {
            DEFAULT: '#D97706',
            light: '#FEF3C7',
            text: '#92400E',
          },
          stable: {
            DEFAULT: '#16A34A',
            light: '#DCFCE7',
            text: '#166534',
          },
          info: {
            DEFAULT: '#2563EB',
            light: '#DBEAFE',
            text: '#1E40AF',
          },
        },
      },
      fontFamily: {
        serif: ['"Cormorant Garamond"', '"Instrument Serif"', 'Georgia', 'serif'],
        sans: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'monospace'],
      },
      boxShadow: {
        'luxury': '0 20px 40px -15px rgba(30, 41, 59, 0.05)',
        'luxury-hover': '0 25px 50px -12px rgba(30, 41, 59, 0.08)',
        'glass': '0 8px 32px 0 rgba(31, 38, 135, 0.05)',
      },
      letterSpacing: {
        'capitular': '0.2em',
        'loose-wider': '0.25em',
      },
      animation: {
        'kenburns': 'kenburns 20s ease-out infinite alternate',
      },
      keyframes: {
        kenburns: {
          '0%': { transform: 'scale(1)' },
          '100%': { transform: 'scale(1.08)' },
        },
      },
    },
  },
  plugins: [],
}

