@extends('layouts.app')

@section('title', 'Consulta de Oferta y Disponibilidad — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">DISPONIBILIDAD MÉDICA</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Oferta Médica y Turnos Libres</h1>
            <p class="text-sm text-slate-500 mt-1">Consulta turnos disponibles en tiempo real cruzando horarios de atención con citas ya agendadas.</p>
        </div>
    </div>

    
    <!-- Filtros de Consulta -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm max-w-4xl mb-8">
        <form action="{{ url('/disponibilidad') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Especialidad</label>
                <select name="id_especialidad" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none">
                    <option value="">Todas las especialidades</option>
                    @foreach($especialidades ?? [] as $esp)
                        <option value="{{ $esp->id_especialidad }}" {{ request('id_especialidad') == $esp->id_especialidad ? 'selected' : '' }}>
                            {{ $esp->nombre_especialidad }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Médico *</label>
                <select name="id_medico" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" required>
                    <option value="">Seleccione especialista...</option>
                    @foreach($medicos ?? [] as $m)
                        <option value="{{ $m->id_medico }}" {{ request('id_medico') == $m->id_medico ? 'selected' : '' }}>
                            {{ $m->nombre_completo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Fecha a Consultar *</label>
                <input type="date" name="fecha" value="{{ request('fecha', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" required>
            </div>

            <div class="sm:col-span-3 flex justify-end">
                <button type="submit" class="btn-primary">
                    Consultar Disponibilidad en Tiempo Real
                </button>
            </div>
        </form>
    </div>

    <!-- Resultados de Turnos Disponibles -->
    @if(request()->has('id_medico'))
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center justify-between">
                <span>Turnos Libres Encontrados</span>
                <span class="text-xs font-mono font-normal text-slate-400">Fecha: {{ request('fecha') }}</span>
            </h2>

            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                @forelse($turnos ?? [] as $t)
                    <div class="p-3 rounded-lg border border-sky-100 bg-sky-50/50 text-center hover:border-sky-300 transition-colors">
                        <strong class="text-base font-mono font-bold text-sky-800 block">
                            {{ substr($t['hora_inicio'], 0, 5) }}
                        </strong>
                        <span class="text-[11px] text-slate-500 block">a {{ substr($t['hora_fin'], 0, 5) }}</span>
                        <a href="{{ url('/citas?id_medico='.request('id_medico').'&fecha_cita='.request('fecha').'&hora_cita='.substr($t['hora_inicio'], 0, 5).'&id_especialidad='.request('id_especialidad')) }}" class="mt-2 text-xs font-bold text-sky-600 hover:text-sky-800 inline-block">
                            Reservar →
                        </a>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-slate-400">
                        No hay turnos disponibles para este especialista en la fecha seleccionada.
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
@endsection

