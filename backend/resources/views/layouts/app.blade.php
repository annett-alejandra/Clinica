<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Clínica Vida Salud — Complejo Hospitalario & Cirugía de Alta Complejidad')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..600;1,9..144,300..400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                        serif: ['"Fraunces"', 'Georgia', 'serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script defer src="{{ asset('js/app.js') }}"></script>
</head>
@php
    $enPortada = request()->routeIs('inicio');
    $enPortal = request()->routeIs('portal');
    $rolKey = match ($rolActual ?? 'Médico') {
        'Médico' => 'medico',
        'Recepcionista' => 'enfermeria',
        'Paciente' => 'paciente',
        default => 'administrador',
    };
    $rolEtiqueta = match ($rolKey) {
        'medico' => 'Dr. Matías Lagos (Médico)',
        'enfermeria' => 'Enf. Carla Soto (Gestión)',
        'paciente' => 'Portal Paciente',
        default => 'Dirección General (Admin)',
    };
@endphp
<body class="min-h-screen bg-[#F8FAFC] text-[#1E293B] font-sans selection:bg-[#0284C7] selection:text-white overflow-x-hidden antialiased"
      x-data="{ showRoleSelector: false, showCuMenu: false, mobileMenu: false }">

    <!-- 1. GLOBAL NAVBAR (Landing & Software Portal Switcher) -->
    <nav class="fixed top-0 left-0 w-full z-50 px-4 sm:px-6 md:px-12 py-3.5 flex justify-between items-center transition-all duration-300 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-sm">
        <!-- Brand Logo -->
        <a href="{{ route('inicio') }}" class="flex items-center space-x-3 cursor-pointer group shrink-0">
            <div class="w-10 h-10 rounded-xl bg-[#0284C7] flex items-center justify-center text-white shadow-md shadow-[#0284C7]/20 group-hover:bg-[#0369A1] transition-colors">
                <x-icon name="compass" class="w-5 h-5" />
            </div>
            <div>
                <span class="font-bold tracking-tight text-sm md:text-base block leading-none text-[#0F172A]">
                    CLÍNICA VIDA SALUD
                </span>
                <span class="text-[10px] uppercase tracking-widest text-[#0284C7] font-semibold block mt-0.5">
                    Complejo Hospitalario · JCI
                </span>
            </div>
        </a>

        <!-- Center Navigation Links (Landing vs Portal/CU) -->
        @if ($enPortada)
            <div class="hidden xl:flex space-x-7 text-xs uppercase tracking-wider font-semibold text-slate-600 items-center">
                <a href="#hero" class="hover:text-[#0284C7] transition-colors">Inicio</a>
                <a href="#projeto" class="hover:text-[#0284C7] transition-colors">El Proyecto</a>
                <a href="#ambientes" class="hover:text-[#0284C7] transition-colors">Instalaciones 360°</a>
                <a href="#especialidades" class="hover:text-[#0284C7] transition-colors">Especialidades</a>
                <a href="{{ url('/disponibilidad') }}" class="hover:text-[#0284C7] transition-colors">Disponibilidad (CU10)</a>
            </div>
        @else
            <div class="hidden lg:flex items-center space-x-3 bg-slate-100 px-3.5 py-1.5 rounded-xl border border-slate-200/70">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-semibold text-slate-700">Sistema Clínico en Tiempo Real</span>
                <span class="text-slate-300">|</span>
                <span class="text-xs text-[#0284C7] font-bold">Conexión Cifrada SSL 256-bit</span>
            </div>
        @endif

        <!-- Right Side: Si no ha iniciado sesión solo ve "Iniciar Sesión" y "Agendar Cita". Si ya inició sesión, ve sus módulos y perfil. -->
        <div class="flex items-center space-x-2.5 sm:space-x-3">

            @if ($usuarioActual)
                <!-- Módulos CU (Casos de Uso permitidos para su rol) -->
                <div class="relative" @click.outside="showCuMenu = false">
                    <button type="button"
                            @click="showCuMenu = !showCuMenu; showRoleSelector = false"
                            class="flex items-center space-x-2 bg-slate-100 hover:bg-slate-200/80 text-slate-700 px-3 py-2 rounded-lg text-xs font-semibold transition-all border border-slate-200/80">
                        <x-icon name="layout-grid" class="w-3.5 h-3.5 text-[#0284C7]" />
                        <span class="hidden md:inline">Módulos</span>
                        <x-icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400" />
                    </button>

                    <div x-show="showCuMenu" x-cloak
                         x-transition.opacity.duration.150ms
                         class="absolute right-0 mt-2 w-80 max-h-[75vh] overflow-y-auto bg-white rounded-2xl shadow-2xl border border-slate-200 p-3 z-50">
                        <div class="px-2 py-1.5 border-b border-slate-100 mb-2 flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Casos de Uso Disponibles</span>
                            <span class="text-[10px] font-bold text-[#0284C7] bg-sky-50 px-2 py-0.5 rounded-full">{{ $rolActual }}</span>
                        </div>
                        @foreach ($modulosMenu as $grupo => $items)
                            <div class="mb-2.5 last:mb-0">
                                <p class="px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">{{ $grupo }}</p>
                                <div class="space-y-0.5">
                                    @foreach ($items as $item)
                                        <a href="{{ url($item['url'] ?? $item['ruta'] ?? '#') }}"
                                           class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 hover:bg-sky-50 hover:text-[#0284C7] transition-colors">
                                            <span>{{ $item['titulo'] }}</span>
                                            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">{{ $item['cu'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Perfil del Usuario Autenticado -->
                <div class="relative" @click.outside="showRoleSelector = false">
                    <button type="button"
                            @click="showRoleSelector = !showRoleSelector; showCuMenu = false"
                            class="flex items-center space-x-2 bg-slate-100 hover:bg-slate-200/80 text-slate-700 px-3 py-2 rounded-lg text-xs font-semibold transition-all border border-slate-200/80">
                        <x-icon name="user-check" class="w-3.5 h-3.5 text-[#0284C7]" />
                        <span class="hidden sm:inline">
                            {{ $usuarioActual->nombreVisible() }} ({{ $usuarioActual->nombreRol() }})
                        </span>
                        <x-icon name="chevron-down" class="w-3.5 h-3.5 text-slate-400" />
                    </button>

                    <div x-show="showRoleSelector" x-cloak
                         x-transition.opacity.duration.150ms
                         class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-50">
                        <div class="px-4 py-2 border-b border-slate-100">
                            <p class="text-xs font-bold text-slate-800">{{ $usuarioActual->nombreVisible() }}</p>
                            <p class="text-[11px] text-slate-400 truncate">{{ $usuarioActual->correo }}</p>
                            <span class="mt-1 inline-block px-2 py-0.5 text-[10px] font-bold rounded-md bg-sky-50 text-[#0284C7]">
                                Rol: {{ $usuarioActual->nombreRol() }}
                            </span>
                        </div>
                        <a href="{{ route('portal') }}" class="block px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 font-medium">
                            Ir a mi Consola Operativa
                        </a>
                        <div class="border-t border-slate-100 mt-1 pt-1 px-3">
                            <form method="POST" action="{{ url('/logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left py-1.5 text-xs font-semibold text-red-600 hover:text-red-700 flex items-center space-x-2">
                                    <x-icon name="log-out" class="w-3.5 h-3.5" />
                                    <span>Cerrar sesión (CU02)</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                @if ($enPortada)
                    <a href="{{ route('portal') }}"
                       class="flex items-center space-x-2 bg-[#0284C7] hover:bg-[#0369A1] text-white px-4 py-2.5 rounded-xl text-xs font-semibold transition-all shadow-md shadow-[#0284C7]/20">
                        <x-icon name="layout-grid" class="w-3.5 h-3.5" />
                        <span>Entrar al Portal</span>
                    </a>
                @else
                    <a href="{{ route('inicio') }}"
                       class="flex items-center space-x-2 bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-xl text-xs font-semibold transition-all shadow-sm">
                        <x-icon name="arrow-left" class="w-3.5 h-3.5" />
                        <span>Volver a la Web</span>
                    </a>
                @endif
            @else
                <!-- Visitante público que navega la web institucional -->
                <button type="button"
                        @click="$dispatch('abrir-reserva')"
                        class="hidden sm:flex items-center space-x-1.5 bg-sky-50 hover:bg-sky-100 text-[#0284C7] border border-sky-200 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all">
                    <x-icon name="calendar" class="w-3.5 h-3.5" />
                    <span>Agendar Cita</span>
                </button>

                <a href="{{ route('login') }}"
                   class="flex items-center space-x-2 bg-[#0284C7] hover:bg-[#0369A1] text-white px-5 py-2.5 rounded-xl text-xs font-semibold transition-all shadow-md shadow-[#0284C7]/20">
                    <x-icon name="log-in" class="w-3.5 h-3.5" />
                    <span>Iniciar Sesión</span>
                </a>
            @endif
        </div>
    </nav>

    <!-- Espaciador para la barra fija cuando no estamos en la portada -->
    @unless ($enPortada)
        <div class="pt-20">
            <x-alerts />
        </div>
    @else
        <div class="fixed top-20 left-0 right-0 z-40 px-4 pointer-events-none">
            <div class="pointer-events-auto">
                <x-alerts />
            </div>
        </div>
    @endunless

    <!-- Contenido Principal -->
    <main>
        @yield('content')
    </main>

    <!-- MODAL GLOBAL DE AGENDAMIENTO DE CITAS (Conectado a CU10 Disponibilidad + CU11 Programar Cita) -->
    <div x-data="reserva({ urlDisponibilidad: '{{ url('/api/disponibilidad') }}' })"
         @abrir-reserva.window="abrir($event.detail || {})"
         x-show="abierto"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
        <div class="bg-white border border-slate-200 max-w-lg w-full rounded-2xl p-6 md:p-8 relative shadow-2xl max-h-[90vh] overflow-y-auto"
             @click.outside="cerrar()">
            <button type="button"
                    @click="cerrar()"
                    class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 p-2 rounded-full hover:bg-slate-100 transition-colors">
                <x-icon name="x" class="w-5 h-5" />
            </button>

            <div class="flex items-center space-x-3 mb-5">
                <div class="w-11 h-11 rounded-xl bg-sky-50 text-[#0284C7] flex items-center justify-center border border-sky-100">
                    <x-icon name="calendar" class="w-5 h-5" />
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#0284C7] block">CU10 & CU11 · Reserva en Tiempo Real</span>
                    <h4 class="font-bold text-lg text-[#0F172A]">Agendar Hora Médica</h4>
                </div>
            </div>

            <form method="POST" action="{{ url('/citas') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Paciente Titular</label>
                    <select name="id_paciente" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#0284C7] bg-white">
                        @forelse ($pacientesReserva as $pac)
                            <option value="{{ $pac->id_paciente }}" @selected($usuarioActual?->id_paciente == $pac->id_paciente)>
                                {{ $pac->nombres }} {{ $pac->apellidos }} — CI: {{ $pac->ci }}
                            </option>
                        @empty
                            <option value="1">Paciente Titular Clínica Vida Salud</option>
                        @endforelse
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Especialidad Médica</label>
                        <select name="id_especialidad" x-model="idEspecialidad" @change="consultar()" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-700 focus:outline-none focus:border-[#0284C7] bg-white">
                            <option value="">Seleccione especialidad...</option>
                            @foreach ($especialidadesReserva as $esp)
                                <option value="{{ $esp->id_especialidad }}">{{ $esp->nombre_especialidad }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fecha Preferente</label>
                        <input type="date" name="fecha_cita" x-model="fecha" @change="consultar()" required
                               min="{{ now()->toDateString() }}"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-700 focus:outline-none focus:border-[#0284C7]" />
                    </div>
                </div>

                <!-- Resultados dinámicos de CU10 (/api/disponibilidad) o selección manual de médico/hora -->
                <template x-if="cargando">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-500 flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-[#0284C7] animate-ping"></span>
                        <span>Consultando agendas y bloques horarios disponibles (CU10)...</span>
                    </div>
                </template>

                <template x-if="!cargando && oferta.length > 0">
                    <div class="space-y-2.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Seleccione Médico y Horario Disponible:</p>
                        <template x-for="item in oferta" :key="item.id_medico">
                            <div class="bg-white p-3 rounded-lg border border-slate-200">
                                <p class="text-xs font-bold text-slate-800" x-text="item.nombre_completo"></p>
                                <p class="text-[11px] text-slate-400 mb-2" x-text="'Consultorio: ' + (item.consultorio || 'Pabellón Central')"></p>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="cupo in item.cupos" :key="item.id_medico + '-' + cupo">
                                        <button type="button"
                                                @click="elegirCupo(item, cupo)"
                                                :class="idMedico == item.id_medico && hora === cupo
                                                    ? 'bg-[#0284C7] text-white border-[#0284C7]'
                                                    : 'bg-slate-50 text-slate-700 border-slate-200 hover:border-[#0284C7]'"
                                                class="px-2.5 py-1 rounded-md text-xs font-semibold border transition-colors"
                                                x-text="cupo.substring(0, 5)">
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Médico Especialista</label>
                        <select name="id_medico" x-model="idMedico" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-700 focus:outline-none focus:border-[#0284C7] bg-white">
                            <option value="">Seleccione especialista...</option>
                            @foreach ($medicosReserva as $med)
                                <option value="{{ $med->id_medico }}">Dr(a). {{ $med->nombres }} {{ $med->apellidos }} ({{ $med->consultorio ?? 'Consultorio' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hora (Bloque 30 min)</label>
                        <input type="time" name="hora_cita" x-model="hora" step="1800" required
                               value="09:00"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-700 focus:outline-none focus:border-[#0284C7]" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Motivo Clínico de Consulta</label>
                    <input type="text" name="motivo_consulta" required minlength="5"
                           placeholder="Ej. Evaluación cardiológica preventiva / Control especialista"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#0284C7]" />
                </div>

                <div class="pt-2 flex flex-col sm:flex-row gap-2.5">
                    <button type="submit"
                            class="flex-1 bg-[#0284C7] hover:bg-[#0369A1] text-white font-semibold py-3 rounded-xl shadow-lg shadow-[#0284C7]/25 transition-all text-sm">
                        Confirmar Reserva de Cita (CU11)
                    </button>
                    <a href="{{ url('/citas') }}"
                       class="px-4 py-3 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-xs flex items-center justify-center">
                        Ir a Módulo Completo
                    </a>
                </div>
            </form>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
