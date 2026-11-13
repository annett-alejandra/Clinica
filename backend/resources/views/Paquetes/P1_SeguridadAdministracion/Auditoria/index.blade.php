@extends('layouts.app')

@section('title', 'Módulo de Auditoría y Seguridad — Clínica Vida Salud')

@section('content')
<div class="container py-8 sm:py-12" x-data="{ modalDetalle: false, detallePayload: null, detalleAccion: '', detalleFecha: '', detalleOperador: '' }">
    <!-- Cabecera Institucional de Seguridad -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 text-[11px] font-bold px-2.5 py-0.5 rounded-full border border-red-200">
                    <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    ACCESO EXCLUSIVO: ADMINISTRADOR DEL SISTEMA
                </span>
                <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono font-medium">PostgreSQL Inmutable</span>
            </div>
            <h1 class="text-3xl font-serif text-slate-900 tracking-tight">Auditoría, Accesos y Trazabilidad</h1>
            <p class="text-sm text-slate-500 mt-1">Supervisión en tiempo real de entradas (logins), salidas (logouts), intentos no autorizados y actividad clínica/administrativa.</p>
        </div>
        <div class="flex items-center gap-2 self-start md:self-center">
            <a href="{{ route('portal') }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs flex items-center gap-2 transition-colors shadow-sm bg-white">
                <span>←</span>
                <span>Volver al Portal</span>
            </a>
            <a href="{{ url('/usuarios') }}" class="btn-primary py-2 px-4 text-xs shadow-sm">
                Gestión de Usuarios
            </a>
        </div>
    </div>

    <!-- TARJETAS KPI DE SEGURIDAD (QUIÉN ENTRA, QUIÉN SALE Y ALERTAS) -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4 mb-7">
        <!-- Total Eventos -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-sm">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Total Eventos</span>
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div class="text-2xl font-extrabold text-slate-900">{{ number_format($kpis['total']) }}</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Logs inmutables en BD</p>
        </div>

        <!-- Inicios de Sesión (Entradas) -->
        <a href="{{ route('auditoria', ['categoria' => 'entradas']) }}"
           class="bg-white p-4 rounded-2xl border transition-all shadow-sm hover:border-emerald-300 hover:shadow-md {{ $categoria === 'entradas' ? 'ring-2 ring-emerald-500 border-emerald-500' : 'border-slate-200/90' }}">
            <div class="flex items-center justify-between text-emerald-600 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Entradas (Login)</span>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>
            <div class="text-2xl font-extrabold text-emerald-700">{{ number_format($kpis['logins']) }}</div>
            <p class="text-[11px] text-emerald-600 font-medium mt-0.5">Accesos validados</p>
        </a>

        <!-- Cierres de Sesión (Salidas) -->
        <a href="{{ route('auditoria', ['categoria' => 'salidas']) }}"
           class="bg-white p-4 rounded-2xl border transition-all shadow-sm hover:border-blue-300 hover:shadow-md {{ $categoria === 'salidas' ? 'ring-2 ring-blue-500 border-blue-500' : 'border-slate-200/90' }}">
            <div class="flex items-center justify-between text-blue-600 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Salidas (Logout)</span>
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </div>
            <div class="text-2xl font-extrabold text-blue-700">{{ number_format($kpis['logouts']) }}</div>
            <p class="text-[11px] text-blue-600 font-medium mt-0.5">Cierres seguros</p>
        </a>

        <!-- Intentos Fallidos -->
        <a href="{{ route('auditoria', ['categoria' => 'fallidos']) }}"
           class="bg-white p-4 rounded-2xl border transition-all shadow-sm hover:border-rose-300 hover:shadow-md {{ $categoria === 'fallidos' ? 'ring-2 ring-rose-500 border-rose-500' : 'border-slate-200/90' }}">
            <div class="flex items-center justify-between text-rose-600 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Intentos Fallidos</span>
                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="text-2xl font-extrabold text-rose-700">{{ number_format($kpis['fallidos']) }}</div>
            <p class="text-[11px] text-rose-600 font-medium mt-0.5">Alertas de seguridad</p>
        </a>

        <!-- Actividad Clínica -->
        <a href="{{ route('auditoria', ['categoria' => 'clinica']) }}"
           class="col-span-2 lg:col-span-1 bg-white p-4 rounded-2xl border transition-all shadow-sm hover:border-sky-300 hover:shadow-md {{ $categoria === 'clinica' ? 'ring-2 ring-sky-500 border-sky-500' : 'border-slate-200/90' }}">
            <div class="flex items-center justify-between text-sky-600 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider">Gestión Clínica</span>
                <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="text-2xl font-extrabold text-sky-700">{{ number_format($kpis['clinicos']) }}</div>
            <p class="text-[11px] text-sky-600 font-medium mt-0.5">Citas y atenciones</p>
        </a>
    </div>

    <!-- PESTAÑAS DE FILTRO RÁPIDO (TABS) -->
    <div class="flex items-center gap-1.5 overflow-x-auto pb-2 mb-4 scrollbar-none">
        <a href="{{ route('auditoria', ['categoria' => 'todas']) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap {{ $categoria === 'todas' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            Todos los Eventos ({{ number_format($kpis['total']) }})
        </a>
        <a href="{{ route('auditoria', ['categoria' => 'entradas']) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 {{ $categoria === 'entradas' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-50' }}">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            Quién Entra (Login: {{ number_format($kpis['logins']) }})
        </a>
        <a href="{{ route('auditoria', ['categoria' => 'salidas']) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 {{ $categoria === 'salidas' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-blue-700 border border-blue-200 hover:bg-blue-50' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
            Quién Sale (Logout: {{ number_format($kpis['logouts']) }})
        </a>
        <a href="{{ route('auditoria', ['categoria' => 'fallidos']) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 {{ $categoria === 'fallidos' ? 'bg-rose-600 text-white shadow-sm' : 'bg-white text-rose-700 border border-rose-200 hover:bg-rose-50' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
            Intentos Fallidos ({{ number_format($kpis['fallidos']) }})
        </a>
        <a href="{{ route('auditoria', ['categoria' => 'clinica']) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 {{ $categoria === 'clinica' ? 'bg-sky-600 text-white shadow-sm' : 'bg-white text-sky-700 border border-sky-200 hover:bg-sky-50' }}">
            Actividad Clínica (Citas)
        </a>
        <a href="{{ route('auditoria', ['categoria' => 'admin']) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 {{ $categoria === 'admin' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-purple-700 border border-purple-200 hover:bg-purple-50' }}">
            Horarios y Usuarios
        </a>
    </div>

    <!-- FORMULARIO DE BÚSQUEDA Y FILTROS AVANZADOS -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm mb-6">
        <form action="{{ route('auditoria') }}" method="GET" class="space-y-4">
            <input type="hidden" name="categoria" value="{{ $categoria }}">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
                <!-- Búsqueda General -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Búsqueda General (Usuario, Correo, IP, Detalle)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                            </svg>
                        </span>
                        <input type="text" name="q" value="{{ $busqueda }}"
                               class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50 focus:bg-white focus:border-[#0284C7] focus:outline-none transition-colors"
                               placeholder="Ej: admin_uagrm, 166.114, LOGIN, citas...">
                    </div>
                </div>

                <!-- Fecha Desde -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fecha Desde</label>
                    <input type="date" name="desde" value="{{ $desde }}"
                           class="w-full py-2 px-3 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:border-[#0284C7] focus:outline-none">
                </div>

                <!-- Fecha Hasta -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fecha Hasta</label>
                    <input type="date" name="hasta" value="{{ $hasta }}"
                           class="w-full py-2 px-3 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:border-[#0284C7] focus:outline-none">
                </div>

                <!-- Acción Realizada -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Acción Específica</label>
                    <select name="accion" class="w-full text-xs font-medium border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none">
                        <option value="todas">Todas las acciones</option>
                        @foreach($acciones as $act)
                            <option value="{{ $act }}" {{ ($accion == $act) ? 'selected' : '' }}>
                                {{ $act }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Botonera de Filtro -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pt-3 border-t border-slate-100 gap-3">
                <span class="text-xs text-slate-500 font-medium">
                    Mostrando resultados filtrados: <strong class="text-slate-900 font-bold">{{ $auditorias->total() }} registros</strong>
                </span>
                <div class="flex items-center gap-2">
                    @if($busqueda || ($accion && $accion !== 'todas') || ($tabla && $tabla !== 'todas') || $desde || $hasta || $categoria !== 'todas')
                        <a href="{{ route('auditoria') }}" class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-semibold transition-colors">
                            Limpiar Filtros
                        </a>
                    @endif
                    <button type="submit" class="btn-primary py-2 px-5 text-xs shadow-sm">
                        Aplicar Filtros
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- TABLA DE AUDITORÍA Y TRAZABILIDAD -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-[11px] uppercase text-slate-500 font-bold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">ID / Fecha y Hora</th>
                        <th class="px-5 py-3.5">Operador / Usuario</th>
                        <th class="px-5 py-3.5">Acción Registrada</th>
                        <th class="px-5 py-3.5">IP / Dispositivo</th>
                        <th class="px-5 py-3.5">Tabla / Registro</th>
                        <th class="px-5 py-3.5 text-right">Detalle Técnico</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($auditorias as $aud)
                        @php
                            $detalle = $aud->detalle ?? [];
                            $ip = $detalle['ip'] ?? null;
                            $accion = $aud->accion;

                            // Identificar badges visuales
                            $isLogin = str_contains($accion, 'LOGIN') && !str_contains($accion, 'FALLIDO');
                            $isLogout = str_contains($accion, 'LOGOUT');
                            $isFallido = str_contains($accion, 'FALLIDO');
                            $isPassword = str_contains($accion, 'PASSWORD');
                            $isClinica = str_contains($accion, 'CITA') || str_contains($accion, 'PACIENTE') || str_contains($accion, 'ATENDIDA') || str_contains($accion, 'EXPEDIENTE');

                            $colorBadge = match(true) {
                                $isLogin => 'bg-emerald-50 text-emerald-800 border-emerald-300 font-bold',
                                $isLogout => 'bg-blue-50 text-blue-800 border-blue-200 font-bold',
                                $isFallido => 'bg-rose-50 text-rose-800 border-rose-300 font-extrabold',
                                $isPassword => 'bg-purple-50 text-purple-800 border-purple-200 font-bold',
                                $isClinica => 'bg-sky-50 text-sky-800 border-sky-200 font-semibold',
                                default => 'bg-slate-100 text-slate-700 border-slate-200 font-medium',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors {{ $isFallido ? 'bg-rose-50/20' : '' }}">
                            <!-- ID y Fecha -->
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="font-mono text-[10px] text-slate-400 block font-semibold">#{{ $aud->id_auditoria }}</span>
                                <strong class="text-slate-800 font-bold">
                                    {{ \Carbon\Carbon::parse($aud->fecha_hora)->format('d/m/Y') }}
                                </strong>
                                <span class="text-slate-500 font-mono text-[11px] block">
                                    {{ \Carbon\Carbon::parse($aud->fecha_hora)->format('H:i:s') }}
                                </span>
                            </td>

                            <!-- Operador / Rol -->
                            <td class="px-5 py-3.5">
                                @if($aud->usuario)
                                    <div class="flex items-center gap-1.5">
                                        <strong class="text-slate-900 font-bold block">{{ $aud->usuario->nombre_usuario }}</strong>
                                        @if($aud->usuario->rol)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                                {{ $aud->usuario->rol->nombre_rol }}
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-[11px] text-slate-500 block truncate max-w-[180px]">{{ $aud->usuario->correo }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-slate-400 italic">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        Sistema / Anónimo
                                    </span>
                                @endif
                            </td>

                            <!-- Acción con Badge Expresivo -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-1.5">
                                    @if($isLogin)
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                    @elseif($isLogout)
                                        <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
                                    @elseif($isFallido)
                                        <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                                    @endif
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] uppercase tracking-wider border {{ $colorBadge }}">
                                        {{ $accion }}
                                    </span>
                                </div>
                            </td>

                            <!-- IP y Red -->
                            <td class="px-5 py-3.5 font-mono text-[11px] whitespace-nowrap">
                                @if($ip)
                                    <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200 font-semibold">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                        {{ $ip }}
                                    </span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>

                            <!-- Tabla / Registro -->
                            <td class="px-5 py-3.5 font-mono text-[11px] whitespace-nowrap">
                                <span class="text-slate-800 font-bold block">{{ $aud->tabla_afectada }}</span>
                                <span class="text-slate-400">ID Reg: {{ $aud->id_registro_afectado ?? '—' }}</span>
                            </td>

                            <!-- Detalle / JSON Inspector -->
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                @if($aud->detalle)
                                    <button type="button"
                                            @click="
                                                modalDetalle = true;
                                                detallePayload = {{ json_encode($aud->detalle) }};
                                                detalleAccion = '{{ $aud->accion }}';
                                                detalleFecha = '{{ \Carbon\Carbon::parse($aud->fecha_hora)->format('d/m/Y H:i:s') }}';
                                                detalleOperador = '{{ $aud->usuario?->nombre_usuario ?? 'Sistema / Anónimo' }}';
                                            "
                                            class="px-2.5 py-1 rounded-lg border border-slate-200 hover:border-[#0284C7] hover:text-[#0284C7] bg-white text-slate-600 text-[11px] font-semibold transition-colors shadow-2xl">
                                        Ver JSON
                                    </button>
                                @else
                                    <span class="text-slate-300 text-[11px]">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-slate-400 text-sm">
                                <div class="max-w-xs mx-auto text-center">
                                    <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="font-semibold text-slate-600">Sin registros de auditoría</p>
                                    <p class="text-xs text-slate-400 mt-1">No se encontraron eventos para el filtro o categoría seleccionada.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginador con información precisa -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
            <div>
                Página <strong class="text-slate-900 font-bold">{{ $auditorias->currentPage() }}</strong> de <strong class="text-slate-900 font-bold">{{ $auditorias->lastPage() }}</strong>
                (Total: <strong class="text-slate-900 font-bold">{{ $auditorias->total() }}</strong> eventos)
            </div>

            <div class="flex items-center gap-2">
                @if($auditorias->onFirstPage())
                    <span class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-400 cursor-not-allowed bg-white">
                        ← Anterior
                    </span>
                @else
                    <a href="{{ $auditorias->previousPageUrl() }}" class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold bg-white transition-colors shadow-sm">
                        ← Anterior
                    </a>
                @endif

                @if($auditorias->hasMorePages())
                    <a href="{{ $auditorias->nextPageUrl() }}" class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold bg-white transition-colors shadow-sm">
                        Siguiente →
                    </a>
                @else
                    <span class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-400 cursor-not-allowed bg-white">
                        Siguiente →
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- MODAL DE DETALLE JSON -->
    <div x-show="modalDetalle" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
         @click.self="modalDetalle = false">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <div>
                    <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <span class="text-xs bg-slate-200 px-2 py-0.5 rounded font-mono" x-text="detalleAccion"></span>
                        Detalle Técnico del Evento
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Operador: <span class="font-semibold text-slate-700" x-text="detalleOperador"></span> ·
                        Fecha: <span class="font-mono" x-text="detalleFecha"></span>
                    </p>
                </div>
                <button type="button" @click="modalDetalle = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-5">
                <pre class="bg-slate-900 text-emerald-400 p-4 rounded-xl font-mono text-xs overflow-x-auto max-h-80 select-all"
                     x-text="JSON.stringify(detallePayload, null, 2)"></pre>
            </div>
            <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button type="button" @click="modalDetalle = false" class="btn-primary py-1.5 px-4 text-xs">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
