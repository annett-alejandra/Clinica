@extends('layouts.app')

@section('title', 'CU19: Reportes y Estadísticas Gerenciales — Clínica Vida Salud')

@section('content')
<div class="container py-8 sm:py-12" x-data="{ formatoVista: 'pantalla' }">

    <!-- ESTILOS DE IMPRESIÓN Y EXPORTACIÓN A PDF -->
    <style>
        @media print {
            body {
                background: #ffffff !important;
                color: #0f172a !important;
                font-size: 11pt !important;
            }
            .no-print, nav, header, footer, .btn-primary {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            .page-break {
                page-break-before: always;
            }
            .container {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .shadow-sm, .shadow-md, .shadow-xl {
                box-shadow: none !important;
            }
            .border {
                border-color: #cbd5e1 !important;
            }
            /* Forzar visualización de ambas secciones al imprimir */
            .print-all {
                display: block !important;
            }
        }
        @media screen {
            .print-only {
                display: none !important;
            }
        }
    </style>

    <!-- MEMBRETE OFICIAL EXCLUSIVO PARA IMPRESIÓN Y EXPORTACIÓN PDF -->
    <div class="print-only mb-6 pb-4 border-b-2 border-slate-900">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold font-serif text-slate-900 uppercase tracking-tight">Clínica Vida Salud</h1>
                <p class="text-xs text-slate-600">Complejo Hospitalario de Alta Especialidad · Certificación JCI</p>
                <p class="text-[10px] text-slate-500 font-mono mt-0.5">Av. Busch, 3er Anillo Interno · Santa Cruz de la Sierra, Bolivia</p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-100 border border-slate-300 font-mono font-bold text-xs uppercase text-slate-800">
                    CU19: Reporte Estadístico Oficial
                </span>
                <p class="text-[10px] text-slate-500 mt-1 font-mono">
                    Fecha de Emisión: <strong>{{ date('d/m/Y H:i:s') }}</strong>
                </p>
                <p class="text-[10px] text-slate-500 font-mono">
                    Generado por: <strong>{{ auth()->user()?->nombre_usuario ?? 'Administración' }}</strong>
                </p>
            </div>
        </div>
        <div class="mt-3 pt-2 border-t border-slate-200 flex justify-between text-[11px] text-slate-600">
            <span>Periodo auditado: <strong>{{ request('fecha_desde', 'Inicio histórico') }}</strong> al <strong>{{ request('fecha_hasta', date('d/m/Y')) }}</strong></span>
            <span>Tipo de documento: <strong>Reporte de Gestión Clínica y Ocupación</strong></span>
        </div>
    </div>

    <!-- CABECERA INSTITUCIONAL EN PANTALLA -->
    <div class="no-print flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-mono font-semibold uppercase tracking-widest text-[#0284C7] bg-sky-50 px-2.5 py-0.5 rounded-full border border-sky-200">
                    PAQUETE 07 · CU19
                </span>
                <span class="text-xs text-slate-400 font-mono font-medium">Reportes Gerenciales y Estadísticas</span>
            </div>
            <h1 class="text-3xl font-serif text-slate-900 tracking-tight">Reportes y Métricas Administrativas</h1>
            <p class="text-sm text-slate-500 mt-1">Evolución de citas, volúmenes de atención médica, efectividad y tasas de inasistencia.</p>
        </div>

        <!-- BOTONES DE EXPORTACIÓN Y ACCIONES -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Botón Exportar PDF / Imprimir -->
            <button type="button"
                    onclick="window.print()"
                    class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs flex items-center gap-2 transition-all shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span>Exportar a PDF / Imprimir</span>
            </button>

            <!-- Botón Exportar CSV / Excel -->
            <a href="{{ url('/reportes-administrativos') }}?formato=csv&{{ http_build_query(request()->all()) }}"
               class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs flex items-center gap-2 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Exportar CSV</span>
            </a>

            <!-- Volver al Portal -->
            <a href="{{ route('portal') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 font-semibold text-xs flex items-center gap-1.5 transition-colors shadow-sm">
                <span>←</span>
                <span>Portal</span>
            </a>
        </div>
    </div>

    <!-- SELECTOR DE FORMATO DE VISUALIZACIÓN: PANTALLA VS REPORTES TABULARES -->
    <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between pb-4 mb-6 border-b border-slate-100 gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Formato de visualización:</span>
            <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200">
                <button type="button"
                        @click="formatoVista = 'pantalla'"
                        :class="formatoVista === 'pantalla' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-[#0284C7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>Visual e Interactivo (Pantalla)</span>
                </button>
                <button type="button"
                        @click="formatoVista = 'tabular'"
                        :class="formatoVista === 'tabular' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-800 font-medium'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 4h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Reportes Tabulares (Detalle Citas)</span>
                </button>
            </div>
        </div>
        <div class="text-xs text-slate-500">
            Filtros activos: <strong class="text-slate-800">{{ $reporte['kpis']['total_citas'] ?? 0 }} citas computadas</strong>
        </div>
    </div>

    <!-- BARRA DE FILTROS GERENCIALES -->
    <div class="no-print bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm mb-8">
        <form action="{{ url('/reportes-administrativos') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
            <!-- Fecha Desde -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fecha Desde</label>
                <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                       class="w-full text-xs border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none">
            </div>

            <!-- Fecha Hasta -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                       class="w-full text-xs border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none">
            </div>

            <!-- Especialidad -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Especialidad</label>
                <select name="id_especialidad" class="w-full text-xs border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none">
                    <option value="">Todas las especialidades</option>
                    @foreach($especialidades ?? [] as $esp)
                        <option value="{{ $esp->id_especialidad }}" {{ request('id_especialidad') == $esp->id_especialidad ? 'selected' : '' }}>
                            {{ $esp->nombre_especialidad }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Médico -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Médico Especialista</label>
                <select name="id_medico" class="w-full text-xs border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none">
                    <option value="">Todos los médicos</option>
                    @foreach($medicos ?? [] as $m)
                        <option value="{{ $m->id_medico }}" {{ request('id_medico') == $m->id_medico ? 'selected' : '' }}>
                            {{ $m->nombre_completo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Botones -->
            <div class="flex items-center gap-2">
                @if(request()->hasAny(['fecha_desde', 'fecha_hasta', 'id_especialidad', 'id_medico']))
                    <a href="{{ url('/reportes-administrativos') }}" class="px-3.5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-medium text-center">
                        Limpiar
                    </a>
                @endif
                <button type="submit" class="btn-primary py-2.5 px-5 text-xs w-full shadow-sm">
                    Filtrar Reporte
                </button>
            </div>
        </form>
    </div>

    <!-- TARJETAS DE KPIS GERENCIALES (SE VEN EN PANTALLA Y EN PDF) -->
    @if(isset($reporte['kpis']))
        @php $k = $reporte['kpis']; @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-sky-500">
                <span class="text-[11px] uppercase font-bold text-slate-400 block mb-1">Volumen Total Citas</span>
                <span class="text-3xl font-serif text-slate-900 font-extrabold">{{ number_format($k['total_citas']) }}</span>
                <span class="text-[11px] text-slate-500 block mt-1">Registradas en el periodo</span>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-emerald-500">
                <span class="text-[11px] uppercase font-bold text-slate-400 block mb-1">Citas Atendidas</span>
                <span class="text-3xl font-serif text-emerald-700 font-extrabold">{{ number_format($k['atendidas']) }}</span>
                <span class="text-[11px] text-emerald-600 font-semibold block mt-1">🟢 {{ $k['tasa_efectividad_pct'] }}% tasa de efectividad</span>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-rose-500">
                <span class="text-[11px] uppercase font-bold text-slate-400 block mb-1">Cancelaciones</span>
                <span class="text-3xl font-serif text-rose-700 font-extrabold">{{ number_format($k['canceladas']) }}</span>
                <span class="text-[11px] text-rose-600 font-semibold block mt-1">🔴 {{ $k['tasa_cancelacion_pct'] }}% tasa de cancelación</span>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-amber-500">
                <span class="text-[11px] uppercase font-bold text-slate-400 block mb-1">Inasistencias</span>
                <span class="text-3xl font-serif text-amber-700 font-extrabold">{{ number_format($k['inasistencias']) }}</span>
                <span class="text-[11px] text-amber-600 font-semibold block mt-1">⚠️ {{ $k['tasa_inasistencia_pct'] }}% tasa de inasistencia</span>
            </div>
        </div>
    @endif

    <!-- SECCIÓN 1: VISTA VISUAL E INTERACTIVA (PANTALLA) -->
    <div x-show="formatoVista === 'pantalla'" class="space-y-8">
        <!-- Grillas de Desglose Superior: Estados y Especialidades -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Distribución de Estados con Barras Temáticas -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm p-6">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#0284C7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        <span>Distribución por Estados de Cita</span>
                    </h2>
                    <span class="text-xs text-slate-400 font-mono">{{ $k['total_citas'] ?? 0 }} total</span>
                </div>
                <div class="space-y-4">
                    @forelse($reporte['distribucion_estados'] ?? [] as $est)
                        @php
                            $colorBg = match($est['nombre_estado']) {
                                'Atendida' => 'bg-emerald-500',
                                'Programada' => 'bg-sky-500',
                                'Confirmada' => 'bg-indigo-500',
                                'Cancelada' => 'bg-rose-500',
                                'Inasistencia' => 'bg-amber-500',
                                default => 'bg-slate-500'
                            };
                            $colorText = match($est['nombre_estado']) {
                                'Atendida' => 'text-emerald-700',
                                'Programada' => 'text-sky-700',
                                'Confirmada' => 'text-indigo-700',
                                'Cancelada' => 'text-rose-700',
                                'Inasistencia' => 'text-amber-700',
                                default => 'text-slate-700'
                            };
                        @endphp
                        <div>
                            <div class="flex justify-between text-xs font-semibold mb-1">
                                <span class="text-slate-700 flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $colorBg }} inline-block"></span>
                                    {{ $est['nombre_estado'] }}
                                </span>
                                <span class="{{ $colorText }} font-mono font-bold">{{ $est['total'] }} citas ({{ $est['porcentaje'] }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                <div class="{{ $colorBg }} h-2.5 rounded-full transition-all duration-500" style="width: {{ $est['porcentaje'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-400 text-xs py-4">Sin datos estadísticos registrados.</p>
                    @endforelse
                </div>
            </div>

            <!-- Demanda por Especialidad / Área -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm p-6">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Demanda por Especialidad / Área Médica</span>
                    </h2>
                    <span class="text-xs text-slate-400 font-mono">Top por volumen</span>
                </div>
                <div class="overflow-x-auto max-h-[340px] overflow-y-auto pr-2">
                    @php $maxEsp = ($reporte['por_especialidad'] ?? collect())->first()?->total ?? 1; @endphp
                    <div class="space-y-3">
                        @forelse($reporte['por_especialidad'] ?? [] as $esp)
                            @php $pctRelativo = round(($esp->total / $maxEsp) * 100); @endphp
                            <div>
                                <div class="flex justify-between text-xs font-medium mb-1">
                                    <span class="text-slate-800 font-semibold truncate">{{ $esp->nombre_especialidad }}</span>
                                    <span class="text-sky-700 font-mono font-bold">{{ $esp->total }} citas</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-sky-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $pctRelativo }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-400 text-xs py-4 text-center">Sin registros de especialidades.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Grillas de Desglose Inferior: Médicos y Cancelaciones -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Ranking de Médicos -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm p-6">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>Ranking de Médicos con Mayor Actividad</span>
                    </h2>
                    <span class="text-xs text-slate-400">Consultorios activos</span>
                </div>
                <div class="overflow-x-auto max-h-[300px] overflow-y-auto">
                    <table class="w-full text-left text-sm text-slate-700">
                        <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
                            <tr>
                                <th class="py-2">#</th>
                                <th class="py-2">Médico Especialista</th>
                                <th class="py-2 text-right">Citas Atendidas / Asignadas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($reporte['por_medico'] ?? [] as $idx => $m)
                                <tr>
                                    <td class="py-2.5 font-mono text-xs text-slate-400">{{ $idx + 1 }}</td>
                                    <td class="py-2.5 font-semibold text-slate-900 text-xs">{{ $m->nombre_completo }}</td>
                                    <td class="py-2.5 text-right font-mono font-bold text-sky-700 text-xs">{{ $m->total }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-center text-slate-400">Sin datos registrados</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Motivos Frecuentes de Cancelación -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm p-6">
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Motivos Frecuentes de Cancelación</span>
                    </h2>
                    <span class="text-xs text-rose-600 font-mono font-bold">{{ $k['canceladas'] ?? 0 }} canceladas</span>
                </div>
                <div class="space-y-3">
                    @forelse($reporte['motivos_cancelacion'] ?? [] as $motivo)
                        <div class="p-3 bg-rose-50/60 border border-rose-100 rounded-xl flex items-center justify-between gap-3">
                            <span class="text-xs text-slate-800 leading-snug">{{ $motivo->motivo_cancelacion }}</span>
                            <span class="text-xs font-mono font-bold bg-white text-rose-700 border border-rose-200 px-2 py-0.5 rounded-full shrink-0">
                                {{ $motivo->total }} {{ $motivo->total == 1 ? 'caso' : 'casos' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-slate-400 text-xs py-4 text-center">No hay cancelaciones registradas en el periodo.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: REPORTE TABULAR EJECUTIVO (DETALLE DE CITAS E HISTORIAL TABULAR) -->
    <div x-show="formatoVista === 'tabular'" class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50">
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 4h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Reporte Tabular Detallado de Citas</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Listado detallado de citas clínicas computadas para exportación e impresión institucional.</p>
                </div>
                <span class="text-xs font-mono font-semibold text-slate-600 bg-white px-3 py-1 rounded-lg border border-slate-200">
                    Total registros: {{ count($reporte['detalle_citas'] ?? []) }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 border-collapse">
                    <thead class="bg-slate-100/80 text-[11px] uppercase font-bold text-slate-500 tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4"># ID</th>
                            <th class="py-3 px-4">Fecha / Hora</th>
                            <th class="py-3 px-4">Paciente (CI)</th>
                            <th class="py-3 px-4">Médico Especialista</th>
                            <th class="py-3 px-4">Especialidad</th>
                            <th class="py-3 px-4">Estado</th>
                            <th class="py-3 px-4">Motivo / Notas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reporte['detalle_citas'] ?? [] as $cita)
                            @php
                                $badgeEstado = match($cita->nombre_estado) {
                                    'Atendida' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'Programada' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'Confirmada' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'Cancelada' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    'Inasistencia' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200'
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 px-4 font-mono font-bold text-[#0284C7]">#{{ $cita->id_cita }}</td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <strong class="text-slate-800">{{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }}</strong>
                                    <span class="text-slate-400 block font-mono text-[11px]">{{ substr($cita->hora_cita, 0, 5) }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <strong class="text-slate-900 block">{{ $cita->nombre_paciente }}</strong>
                                    <span class="text-slate-400 font-mono text-[11px]">CI: {{ $cita->ci_paciente }}</span>
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-800">{{ $cita->nombre_medico }}</td>
                                <td class="py-3 px-4 text-slate-600 font-medium">{{ $cita->nombre_especialidad }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border {{ $badgeEstado }}">
                                        {{ $cita->nombre_estado }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500 max-w-xs truncate" title="{{ $cita->motivo }}">
                                    {{ $cita->motivo ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                    No se encontraron citas para los criterios seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECCIÓN DE FIRMAS PARA LA VERSIÓN IMPRESA / EXPORTADA EN PDF -->
    <div class="print-only mt-16 pt-8 border-t border-slate-300">
        <div class="grid grid-cols-2 gap-12 text-center text-xs text-slate-700">
            <div>
                <div class="w-48 mx-auto border-b border-slate-400 mb-2"></div>
                <p class="font-bold uppercase tracking-wider text-slate-900">Dirección de Auditoría Médica</p>
                <p class="text-[10px] text-slate-500">Clínica Vida Salud · Complejo Hospitalario</p>
            </div>
            <div>
                <div class="w-48 mx-auto border-b border-slate-400 mb-2"></div>
                <p class="font-bold uppercase tracking-wider text-slate-900">Administración General y Finanzas</p>
                <p class="text-[10px] text-slate-500">Supervisión Gerencial de Operaciones</p>
            </div>
        </div>
    </div>

</div>
@endsection
