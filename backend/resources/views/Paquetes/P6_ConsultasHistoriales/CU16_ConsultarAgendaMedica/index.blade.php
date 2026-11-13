@extends('layouts.app')

@section('title', 'Agenda Médica General — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">AGENDA MÉDICA CLÍNICA</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Agenda Médica General</h1>
            <p class="text-sm text-slate-500 mt-1">Listado organizado de consultas con filtros por profesional, especialidad, fecha y estado.</p>
        </div>
        <a href="{{ url('/citas') }}" class="btn-primary">+ Programar Cita</a>
    </div>

    
    <!-- Filtros de Búsqueda -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm mb-8">
        <form action="{{ url('/agenda-medica') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Fecha</label>
                <input type="date" name="fecha_cita" value="{{ request('fecha_cita') }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Médico</label>
                <select name="id_medico" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none">
                    <option value="">Todos los médicos</option>
                    @foreach($medicos ?? [] as $m)
                        <option value="{{ $m->id_medico }}" {{ request('id_medico') == $m->id_medico ? 'selected' : '' }}>
                            {{ $m->nombre_completo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Especialidad</label>
                <select name="id_especialidad" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none">
                    <option value="">Todas</option>
                    @foreach($especialidades ?? [] as $esp)
                        <option value="{{ $esp->id_especialidad }}" {{ request('id_especialidad') == $esp->id_especialidad ? 'selected' : '' }}>
                            {{ $esp->nombre_especialidad }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Estado</label>
                <select name="id_estado_cita" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none">
                    <option value="">Todos los estados</option>
                    <option value="1" {{ request('id_estado_cita') == '1' ? 'selected' : '' }}>Programada</option>
                    <option value="2" {{ request('id_estado_cita') == '2' ? 'selected' : '' }}>Confirmada</option>
                    <option value="3" {{ request('id_estado_cita') == '3' ? 'selected' : '' }}>Atendida</option>
                    <option value="4" {{ request('id_estado_cita') == '4' ? 'selected' : '' }}>Cancelada</option>
                    <option value="5" {{ request('id_estado_cita') == '5' ? 'selected' : '' }}>Inasistencia</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary w-full justify-center">Filtrar</button>
                <a href="{{ url('/agenda-medica') }}" class="btn-minimal">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- Grilla de Citas -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Código / Horario</th>
                        <th class="px-6 py-4">Paciente</th>
                        <th class="px-6 py-4">Médico Asignado</th>
                        <th class="px-6 py-4">Especialidad</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Gestión</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($citas ?? [] as $cita)
                        @php $est = (int) $cita->id_estado_cita; @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs text-sky-700 bg-sky-50 px-2 py-0.5 rounded border border-sky-100 inline-block mb-1">
                                    {{ substr($cita->codigo_unico, 0, 8) }}...
                                </span>
                                <div class="font-bold text-slate-900 text-sm">
                                    {{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }} a las {{ substr($cita->hora_cita, 0, 5) }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $cita->paciente?->nombre_completo }}</div>
                                <div class="text-xs text-slate-500">CI: {{ $cita->paciente?->ci }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-900">{{ $cita->medico?->nombre_completo }}</div>
                                <div class="text-xs text-slate-400">Cons. {{ $cita->medico?->numero_consultorio ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-slate-700">{{ $cita->especialidad?->nombre_especialidad }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                    {{ $est === 1 ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                    {{ $est === 2 ? 'bg-sky-50 text-sky-700 border border-sky-200' : '' }}
                                    {{ $est === 3 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                    {{ $est >= 4 ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}">
                                    {{ $cita->estado?->nombre_estado }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if(in_array($est, [1, 2]))
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ url('/citas/'.$cita->id_cita.'/reprogramar') }}" class="text-xs font-medium text-sky-600 hover:text-sky-800">
                                            Reprogramar
                                        </a>
                                        <span class="text-slate-300">|</span>
                                        <a href="{{ url('/citas/'.$cita->id_cita.'/cancelar') }}" class="text-xs font-medium text-rose-600 hover:text-rose-800">
                                            Cancelar
                                        </a>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Concluida</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">No se encontraron citas médicas con los filtros indicados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
