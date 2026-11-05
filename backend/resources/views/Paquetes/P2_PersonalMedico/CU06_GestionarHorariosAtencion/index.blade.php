@extends('layouts.app')

@section('title', 'Horarios de Atención — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">PAQUETE 02 · CU06</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Horarios y Turnos de Atención</h1>
            <p class="text-sm text-slate-500 mt-1">Configuración semanal de turnos, duración de cita en minutos y cupos máximos por médico.</p>
        </div>
    </div>

    @include('components.alerts')

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Formulario Horario -->
        <div class="lg:col-span-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">Nuevo Turno Semanal</h2>
            <form action="{{ url('/horarios') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Médico *</label>
                    <select name="id_medico" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" required>
                        <option value="">Seleccione médico...</option>
                        @foreach($medicos ?? [] as $m)
                            <option value="{{ $m->id_medico }}">{{ $m->nombre_completo }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Día de la Semana *</label>
                    <select name="dia_semana" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" required>
                        <option value="1">Lunes</option>
                        <option value="2">Martes</option>
                        <option value="3">Miércoles</option>
                        <option value="4">Jueves</option>
                        <option value="5">Viernes</option>
                        <option value="6">Sábado</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Hora Inicio *</label>
                        <input type="time" name="hora_inicio" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Hora Fin *</label>
                        <input type="time" name="hora_fin" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Duración (min) *</label>
                        <input type="number" name="duracion_cita_minutos" value="30" min="10" step="5" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Cupo Máximo *</label>
                        <input type="number" name="cupo_max_pacientes" value="12" min="1" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    Crear Horario
                </button>
            </form>
        </div>

        <!-- Tabla Horarios -->
        <div class="lg:col-span-8 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Médico</th>
                        <th class="px-6 py-4">Día</th>
                        <th class="px-6 py-4">Intervalo</th>
                        <th class="px-6 py-4">Cita / Cupo</th>
                        <th class="px-6 py-4 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php $dias = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sábado', 7=>'Domingo']; @endphp
                    @forelse($horarios ?? [] as $h)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-900">{{ $h->medico?->nombre_completo }}</td>
                            <td class="px-6 py-4 font-medium text-slate-800">{{ $dias[$h->dia_semana] ?? $h->dia_semana }}</td>
                            <td class="px-6 py-4 font-mono text-xs text-sky-700">
                                {{ substr($h->hora_inicio, 0, 5) }} - {{ substr($h->hora_fin, 0, 5) }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                {{ $h->duracion_cita_minutos }} min | {{ $h->cupo_max_pacientes }} pac.
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ url('/horarios/'.$h->id_horario) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 font-medium" onclick="return confirm('¿Eliminar este turno de atención?');">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400">No hay horarios de atención configurados.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
@endsection

