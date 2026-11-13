@extends('layouts.app')

@section('title', 'Reportes y Métricas Administrativas — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">REPORTES Y ESTADÍSTICAS GERENCIALES</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Reportes y Métricas Gerenciales</h1>
            <p class="text-sm text-slate-500 mt-1">Evolución de citas, volúmenes de atención médica, efectividad y tasas de inasistencia.</p>
        </div>
    </div>

    
    <!-- Tarjetas de KPIs -->
    @if(isset($reporte['kpis']))
        @php $k = $reporte['kpis']; @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-sky-500">
                <span class="text-xs uppercase font-semibold text-slate-400 block mb-1">Volumen Total Citas</span>
                <span class="text-3xl font-serif text-slate-900 font-bold">{{ $k['total_citas'] }}</span>
            </div>

            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-emerald-500">
                <span class="text-xs uppercase font-semibold text-slate-400 block mb-1">Citas Atendidas</span>
                <span class="text-3xl font-serif text-emerald-700 font-bold">{{ $k['atendidas'] }}</span>
                <span class="text-xs text-emerald-600 block mt-1">{{ $k['tasa_efectividad_pct'] }}% tasa de efectividad</span>
            </div>

            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-rose-500">
                <span class="text-xs uppercase font-semibold text-slate-400 block mb-1">Cancelaciones</span>
                <span class="text-3xl font-serif text-rose-700 font-bold">{{ $k['canceladas'] }}</span>
                <span class="text-xs text-rose-600 block mt-1">{{ $k['tasa_cancelacion_pct'] }}% tasa de cancelación</span>
            </div>

            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-amber-500">
                <span class="text-xs uppercase font-semibold text-slate-400 block mb-1">Inasistencias</span>
                <span class="text-3xl font-serif text-amber-700 font-bold">{{ $k['inasistencias'] }}</span>
                <span class="text-xs text-amber-600 block mt-1">{{ $k['tasa_inasistencia_pct'] }}% tasa de inasistencia</span>
            </div>
        </div>
    @endif

    <!-- Grillas de Desglose Superior -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Distribución de Estados con Barras Temáticas -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm p-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">Distribución por Estados de Cita</h2>
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

        <!-- Volumen por Especialidad Médica -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm p-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">Demanda por Especialidad / Área</h2>
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
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                <div class="bg-sky-600 h-2 rounded-full" style="width: {{ $pctRelativo }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-400 text-xs py-4 text-center">Sin registros</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Grillas de Desglose Inferior: Médicos y Cancelaciones -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Top Médicos con Mayor Demanda -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm p-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">Ranking de Médicos con Mayor Actividad</h2>
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
                                <td class="py-2.5 font-medium text-slate-900 text-xs">{{ $m->nombre_completo }}</td>
                                <td class="py-2.5 text-right font-mono font-bold text-sky-700 text-xs">{{ $m->total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-center text-slate-400">Sin datos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Análisis de Motivos de Cancelación -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm p-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">Motivos Frecuentes de Cancelación</h2>
                <span class="text-xs text-rose-600 font-mono font-bold">{{ $k['canceladas'] ?? 0 }} canceladas</span>
            </div>
            <div class="space-y-3">
                @forelse($reporte['motivos_cancelacion'] ?? [] as $motivo)
                    <div class="p-3 bg-rose-50/60 border border-rose-100 rounded-lg flex items-center justify-between gap-3">
                        <span class="text-xs text-slate-800 leading-snug">{{ $motivo->motivo_cancelacion }}</span>
                        <span class="text-xs font-mono font-bold bg-white text-rose-700 border border-rose-200 px-2 py-0.5 rounded-full shrink-0">
                            {{ $motivo->total }} {{ $motivo->total == 1 ? 'caso' : 'casos' }}
                        </span>
                    </div>
                @empty
                    <p class="text-slate-400 text-xs py-4 text-center">No hay cancelaciones registradas.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
