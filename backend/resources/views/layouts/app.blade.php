<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Clínica Vida Salud — Excelencia Médica & Hospitalidad Terapéutica')</title>
    
    <!-- Tipografías de Alta Gama (Google Fonts) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (CDN oficial ultraligero para utilidades Blade y diseño responsivo) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0284C7',
                        'primary-hover': '#0369A1',
                        secondary: '#0D9488',
                    },
                    fontFamily: {
                        display: ['Cormorant Garamond', 'Georgia', 'serif'],
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>

    <!-- Estilos Globales y Tokens Clínicos (Cero Lag) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    
    <!-- Alpine.js para Interactividad Reactiva Ligera -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    @stack('styles')
    
    <!-- Motor de Animación e Interacciones -->
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body class="selection:bg-sky-100 selection:text-sky-900 antialiased">
    <!-- Capa de Fondo Fijo Acelerada por GPU (60fps garantizados) -->
    <div class="bg-ambient-glow" aria-hidden="true"></div>

    <a class="skip-link" href="#contenido">Ir al contenido</a>

    <!-- Header Flotante / Sticky Editorial y Totalmente Responsivo -->
    <header class="site-header">
        <div class="container site-header-inner">
            <a class="brand" href="{{ route('inicio') }}" aria-label="Inicio Clínica Vida Salud">
                <span>Clínica Vida Salud</span>
                <span class="brand-dot" aria-hidden="true"></span>
            </a>

            <!-- Navegación Principal y Módulos Clínicos Organizados -->
            <nav class="hidden lg:flex items-center gap-6" aria-label="Navegación principal">
                <a href="{{ route('inicio') }}" class="nav-item">Inicio</a>
                <a href="{{ url('/#capitulo-01') }}" class="nav-item">El Complejo</a>
                <a href="{{ url('/#capitulo-02') }}" class="nav-item">Unidades & Suites</a>
                <a href="{{ url('/#capitulo-03') }}" class="nav-item">Especialidades</a>

                <!-- Desplegable Elegante de Casos de Uso / Módulos Clínicos -->
                <div class="nav-dropdown" x-data="{ open: false }">
                    <button 
                        type="button" 
                        class="nav-item nav-dropdown-trigger flex items-center gap-1.5 focus:outline-none"
                        @click="open = !open" 
                        @click.outside="open = false"
                        aria-expanded="false"
                    >
                        <span>Módulos Clínicos</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div 
                        x-show="open" 
                        x-cloak 
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-2"
                        class="nav-dropdown-menu"
                    >
                        <div class="dropdown-grid">
                            <!-- Columna 1: Consultas y Atención Médica -->
                            <div class="dropdown-col">
                                <span class="dropdown-heading">Atención Médica</span>
                                <a href="{{ url('/citas') }}" class="dropdown-link">
                                    <span class="cu-badge">CU11</span>
                                    <div>
                                        <div class="dropdown-title">Citas Médicas</div>
                                        <div class="dropdown-desc">Reserva y programación de consultas</div>
                                    </div>
                                </a>
                                <a href="{{ url('/agenda-medica') }}" class="dropdown-link">
                                    <span class="cu-badge">CU16</span>
                                    <div>
                                        <div class="dropdown-title">Agenda Médica</div>
                                        <div class="dropdown-desc">Horarios y disponibilidad facultativa</div>
                                    </div>
                                </a>
                                <a href="{{ url('/control-atencion') }}" class="dropdown-link">
                                    <span class="cu-badge">CU14</span>
                                    <div>
                                        <div class="dropdown-title">Control de Atención</div>
                                        <div class="dropdown-desc">Monitoreo de flujo y sala de espera</div>
                                    </div>
                                </a>
                                <a href="{{ url('/atencion-medica') }}" class="dropdown-link">
                                    <span class="cu-badge">CU15</span>
                                    <div>
                                        <div class="dropdown-title">Consultas Clínicas</div>
                                        <div class="dropdown-desc">Evolución, diagnóstico y prescripción</div>
                                    </div>
                                </a>
                                <a href="{{ url('/historial-paciente') }}" class="dropdown-link">
                                    <span class="cu-badge">CU17</span>
                                    <div>
                                        <div class="dropdown-title">Historial Clínico</div>
                                        <div class="dropdown-desc">Expediente integral del paciente</div>
                                    </div>
                                </a>
                            </div>

                            <!-- Columna 2: Portal Paciente y Administración -->
                            <div class="dropdown-col">
                                <span class="dropdown-heading">Portal Paciente</span>
                                <a href="{{ url('/mis-citas') }}" class="dropdown-link">
                                    <span class="cu-badge">CU18</span>
                                    <div>
                                        <div class="dropdown-title">Mis Citas</div>
                                        <div class="dropdown-desc">Gestión autónoma de citas médicas</div>
                                    </div>
                                </a>
                                <a href="{{ url('/perfil-paciente') }}" class="dropdown-link">
                                    <span class="cu-badge">CU08</span>
                                    <div>
                                        <div class="dropdown-title">Perfil Paciente</div>
                                        <div class="dropdown-desc">Filiación y datos personales</div>
                                    </div>
                                </a>

                                <span class="dropdown-heading mt-4">Administración</span>
                                <a href="{{ url('/reportes-administrativos') }}" class="dropdown-link">
                                    <span class="cu-badge">CU19</span>
                                    <div>
                                        <div class="dropdown-title">Reportes</div>
                                        <div class="dropdown-desc">Métricas operativas y estadísticas</div>
                                    </div>
                                </a>
                                <a href="{{ url('/usuarios') }}" class="dropdown-link">
                                    <span class="cu-badge">CU03</span>
                                    <div>
                                        <div class="dropdown-title">Usuarios & Roles</div>
                                        <div class="dropdown-desc">Control de accesos y seguridad</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Acciones Rápidas y Accesibilidad -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Control para Pausar / Reducir Animaciones -->
                <button 
                    id="toggle-motion-btn"
                    type="button" 
                    class="btn-minimal text-xs hidden md:inline-flex"
                    aria-pressed="false"
                    title="Alternar animaciones y transiciones visuales"
                >
                    <span>Ⅱ</span> Pausar Animaciones
                </button>

                <!-- CTA Principal: Coordinación y Citas -->
                <a href="{{ url('/citas') }}" class="btn-primary">
                    <span class="hidden sm:inline">Agendar Cita</span>
                    <span class="sm:hidden">Cita</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>

                <!-- Botón Hamburguesa Móvil -->
                <button 
                    id="mobile-menu-btn"
                    type="button"
                    class="lg:hidden w-9 h-9 rounded-full border border-slate-200 flex items-center justify-center text-slate-700 hover:bg-slate-100 transition-colors"
                    aria-label="Abrir menú de navegación"
                    aria-expanded="false"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Menú Móvil Desplegable (Estrictamente Oculto por Defecto en Desktop y Móvil hasta Abrir) -->
        <div id="mobile-menu" class="hidden lg:hidden border-t border-slate-200 bg-white/95 backdrop-blur-md px-6 py-5 shadow-luxury">
            <nav class="flex flex-col gap-2" aria-label="Menú móvil">
                <span class="text-[11px] font-mono uppercase tracking-widest text-slate-400 font-semibold mb-1">Navegación General</span>
                <a href="{{ route('inicio') }}" class="py-2 text-sm font-medium text-slate-700 hover:text-primary flex items-center justify-between border-b border-slate-100">
                    <span>Inicio</span>
                    <span class="text-xs text-slate-400">→</span>
                </a>
                <a href="{{ url('/#capitulo-01') }}" class="py-2 text-sm font-medium text-slate-700 hover:text-primary flex items-center justify-between border-b border-slate-100">
                    <span>01 El Complejo Hospitalario</span>
                    <span class="text-xs text-slate-400">→</span>
                </a>
                <a href="{{ url('/#capitulo-02') }}" class="py-2 text-sm font-medium text-slate-700 hover:text-primary flex items-center justify-between border-b border-slate-100">
                    <span>02 Unidades Clínicas & Suites</span>
                    <span class="text-xs text-slate-400">→</span>
                </a>
                <a href="{{ url('/#capitulo-03') }}" class="py-2 text-sm font-medium text-slate-700 hover:text-primary flex items-center justify-between border-b border-slate-100">
                    <span>03 Vocación Médica & Protocolos</span>
                    <span class="text-xs text-slate-400">→</span>
                </a>
                
                <span class="text-[11px] font-mono uppercase tracking-widest text-slate-400 font-semibold mt-3 mb-1">Módulos Clínicos</span>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <a href="{{ url('/citas') }}" class="p-2 rounded bg-slate-50 border border-slate-100 font-medium text-slate-700 hover:text-primary hover:border-sky-200">
                        <span class="font-mono text-[10px] text-sky-600 font-bold block">CU11</span> Citas Médicas
                    </a>
                    <a href="{{ url('/agenda-medica') }}" class="p-2 rounded bg-slate-50 border border-slate-100 font-medium text-slate-700 hover:text-primary hover:border-sky-200">
                        <span class="font-mono text-[10px] text-sky-600 font-bold block">CU16</span> Agenda Médica
                    </a>
                    <a href="{{ url('/control-atencion') }}" class="p-2 rounded bg-slate-50 border border-slate-100 font-medium text-slate-700 hover:text-primary hover:border-sky-200">
                        <span class="font-mono text-[10px] text-sky-600 font-bold block">CU14</span> Control Atención
                    </a>
                    <a href="{{ url('/atencion-medica') }}" class="p-2 rounded bg-slate-50 border border-slate-100 font-medium text-slate-700 hover:text-primary hover:border-sky-200">
                        <span class="font-mono text-[10px] text-sky-600 font-bold block">CU15</span> Consultas
                    </a>
                    <a href="{{ url('/historial-paciente') }}" class="p-2 rounded bg-slate-50 border border-slate-100 font-medium text-slate-700 hover:text-primary hover:border-sky-200">
                        <span class="font-mono text-[10px] text-sky-600 font-bold block">CU17</span> Historial
                    </a>
                    <a href="{{ url('/mis-citas') }}" class="p-2 rounded bg-slate-50 border border-slate-100 font-medium text-slate-700 hover:text-primary hover:border-sky-200">
                        <span class="font-mono text-[10px] text-sky-600 font-bold block">CU18</span> Mis Citas
                    </a>
                    <a href="{{ url('/perfil-paciente') }}" class="p-2 rounded bg-slate-50 border border-slate-100 font-medium text-slate-700 hover:text-primary hover:border-sky-200">
                        <span class="font-mono text-[10px] text-sky-600 font-bold block">CU08</span> Mi Perfil
                    </a>
                    <a href="{{ url('/reportes-administrativos') }}" class="p-2 rounded bg-slate-50 border border-slate-100 font-medium text-slate-700 hover:text-primary hover:border-sky-200">
                        <span class="font-mono text-[10px] text-sky-600 font-bold block">CU19</span> Reportes
                    </a>
                    <a href="{{ url('/usuarios') }}" class="p-2 rounded bg-slate-50 border border-slate-100 font-medium text-slate-700 hover:text-primary hover:border-sky-200 col-span-2">
                        <span class="font-mono text-[10px] text-sky-600 font-bold block">CU03</span> Gestión de Usuarios
                    </a>
                </div>

                <a href="{{ url('/citas') }}" class="mt-3 py-2.5 px-4 text-center text-xs font-semibold text-white bg-primary rounded-full shadow-sm hover:bg-primary-hover">
                    Programar Cita Médica →
                </a>
            </nav>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main id="contenido" class="main-content focus:outline-none" tabindex="-1">
        @include('components.alerts')
        @yield('content')
    </main>

    <!-- Modal Global de Inspección de Instalaciones -->
    <x-gallery-modal />

    @stack('scripts')
</body>
</html>
