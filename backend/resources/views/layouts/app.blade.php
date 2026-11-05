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

    <!-- Header Flotante Editorial y Totalmente Responsivo -->
    <header class="site-header">
        <div class="container site-header-inner">
            <a class="brand" href="{{ route('inicio') }}" aria-label="Inicio Clínica Vida Salud">
                <span>Clínica Vida Salud</span>
                <span class="brand-dot" aria-hidden="true"></span>
            </a>

            <!-- Navegación por Módulos y Casos de Uso Laravel Blade -->
            <nav class="hidden lg:flex items-center" aria-label="Navegación clínica">
                <ul class="nav-links">
                    <li>
                        <a href="{{ url('/citas') }}" class="nav-item">
                            <span class="nav-num">CU11</span>Citas
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/agenda-medica') }}" class="nav-item">
                            <span class="nav-num">CU16</span>Agenda
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/control-atencion') }}" class="nav-item">
                            <span class="nav-num">CU14</span>Control
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/atencion-medica') }}" class="nav-item">
                            <span class="nav-num">CU15</span>Consultas
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/historial-paciente') }}" class="nav-item">
                            <span class="nav-num">CU17</span>Historial
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/mis-citas') }}" class="nav-item">
                            <span class="nav-num">CU18</span>Mis Citas
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/perfil-paciente') }}" class="nav-item">
                            <span class="nav-num">CU08</span>Perfil
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/reportes-administrativos') }}" class="nav-item">
                            <span class="nav-num">CU19</span>Reportes
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/usuarios') }}" class="nav-item">
                            <span class="nav-num">CU03</span>Usuarios
                        </a>
                    </li>
                </ul>
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
                <a href="#admisiones" class="btn-primary">
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

        <!-- Menú Móvil Desplegable -->
        <div id="mobile-menu" class="hidden lg:hidden border-t border-slate-200 bg-white/95 backdrop-blur-md px-6 py-4 shadow-luxury">
            <nav class="flex flex-col gap-3" aria-label="Menú móvil">
                <a href="#capitulo-01" class="py-2 text-sm font-medium text-slate-700 hover:text-primary flex items-center justify-between border-b border-slate-100">
                    <span>01 El Complejo Hospitalario</span>
                    <span class="text-xs text-slate-400">→</span>
                </a>
                <a href="#capitulo-02" class="py-2 text-sm font-medium text-slate-700 hover:text-primary flex items-center justify-between border-b border-slate-100">
                    <span>02 Unidades Clínicas & Suites</span>
                    <span class="text-xs text-slate-400">→</span>
                </a>
                <a href="#capitulo-03" class="py-2 text-sm font-medium text-slate-700 hover:text-primary flex items-center justify-between border-b border-slate-100">
                    <span>03 Vocación Médica & Protocolos</span>
                    <span class="text-xs text-slate-400">→</span>
                </a>
                <a href="#capitulo-04" class="py-2 text-sm font-medium text-slate-700 hover:text-primary flex items-center justify-between border-b border-slate-100">
                    <span>04 Explorador de Instalaciones</span>
                    <span class="text-xs text-slate-400">→</span>
                </a>
                <a href="#admisiones" class="py-2 text-sm font-semibold text-primary flex items-center justify-between">
                    <span>Admisiones y Coordinación</span>
                    <span>→</span>
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
