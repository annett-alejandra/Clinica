@extends('layouts.app')

@section('title', 'Reportes y Métricas Administrativas — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">PAQUETE 07 · CU19</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Reportes y Métricas Gerenciales</h1>
            <p class="text-sm text-slate-500 mt-1">Evolución de citas, volúmenes de atención médica, efectividad y tasas de inasistencia.</p>
        </div>
    </div>

    @include('components.alerts')

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

    <!-- Grillas de Desglose -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Distribución de Estados -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">Distribución por Estados de Cita</h2>
            <div class="space-y-4">
                @forelse($reporte['distribucion_estados'] ?? [] as $est)
                    <div>
                        <div class="flex justify-between text-xs font-semibold mb-1 text-slate-700">
                            <span>{{ $est['nombre_estado'] }}</span>
                            <span>{{ $est['total'] }} citas ({{ $est['porcentaje'] }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-sky-600 h-2 rounded-full" style="width: {{ $est['porcentaje'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-400 text-xs py-4">Sin datos estadísticos registrados.</p>
                @endforelse
            </div>
        </div>

        <!-- Volumen por Especialidad -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">Citas por Especialidad Médica</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
                        <tr>
                            <th class="py-2">Especialidad</th>
                            <th class="py-2 text-right">Volumen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reporte['por_especialidad'] ?? [] as $esp)
                            <tr>
                                <td class="py-3 font-medium text-slate-900">{{ $esp->nombre_especialidad }}</td>
                                <td class="py-3 text-right font-mono font-bold text-sky-700">{{ $esp->total }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-4 text-center text-slate-400">Sin registros</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
