@extends('layouts.app')

@section('title', 'Mis Citas Médicas — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">PAQUETE 06 · CU18</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Mis Citas Médicas</h1>
            <p class="text-sm text-slate-500 mt-1">Consulta tus próximas visitas al consultorio y el historial de consultas previas.</p>
        </div>
        <a href="{{ url('/citas') }}" class="btn-primary">+ Agendar Nueva Cita</a>
    </div>

    @include('components.alerts')

    <!-- Pestañas Editorial -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Próximas Citas -->
        <div>
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-sky-100">
                <h2 class="text-lg font-bold text-slate-900">Próximas Visitas Agendadas</h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">
                    {{ count($proximas ?? []) }} Vigentes
                </span>
            </div>

            <div class="space-y-4">
                @forelse($proximas ?? [] as $cita)
                    <div class="bg-white p-5 rounded-xl border border-sky-100 shadow-sm hover:border-sky-300 transition-colors">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold bg-sky-50 text-sky-700 border border-sky-100 uppercase">
                                    {{ $cita->especialidad?->nombre_especialidad }}
                                </span>
                                <h3 class="text-base font-bold text-slate-900 mt-1">{{ $cita->medico?->nombre_completo }}</h3>
                                <p class="text-xs text-slate-500">Consultorio: {{ $cita->medico?->numero_consultorio ?? 'Principal' }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-bold text-sky-700 font-mono block">{{ substr($cita->hora_cita, 0, 5) }}</span>
                                <span class="text-xs text-slate-500">{{ $cita->fecha_cita }}</span>
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Código: <strong class="font-mono text-slate-700">{{ substr($cita->codigo_unico, 0, 8) }}</strong></span>
                            <span class="inline-flex items-center gap-1 font-medium text-amber-700">
                                Estado: {{ $cita->estado?->nombre_estado }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-8 rounded-xl border border-dashed border-slate-200 text-center text-slate-400 text-sm">
                        No tienes ninguna consulta futura programada.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Historial de Consultas Concluidas -->
        <div>
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Historial de Consultas</h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                    {{ count($historial ?? []) }} Registros
                </span>
            </div>

            <div class="space-y-3">
                @forelse($historial ?? [] as $c)
                    <div class="bg-white p-4 rounded-xl border border-slate-200 text-sm">
                        <div class="flex justify-between items-center mb-1">
                            <strong class="text-slate-800">{{ $c->especialidad?->nombre_especialidad }}</strong>
                            <span class="text-xs text-slate-400">{{ $c->fecha_cita }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mb-2">Dr(a). {{ $c->medico?->nombre_completo }}</p>
                        @if($c->observacion_atencion)
                            <div class="text-xs bg-slate-50 p-2.5 rounded border border-slate-100 text-slate-700">
                                <strong>Diagnóstico / Observación:</strong> {{ $c->observacion_atencion }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="bg-white p-8 rounded-xl border border-dashed border-slate-200 text-center text-slate-400 text-sm">
                        Aún no cuentas con atenciones clínicas concluidas.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
