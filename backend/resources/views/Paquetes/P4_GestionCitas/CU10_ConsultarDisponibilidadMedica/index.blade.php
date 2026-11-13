@extends('layouts.app')

@section('title', 'Consulta de Disponibilidad y Oferta Médica — Clínica Vida Salud')

@section('content')
<div class="container py-12"
     x-data="{
        especialidadId: '{{ request('id_especialidad', '') }}',
        medicoId: '{{ request('id_medico', '') }}',
        fecha: '{{ request('fecha', date('Y-m-d')) }}',
        medicos: @json($medicos),

        get medicosFiltrados() {
            if (!this.especialidadId) return this.medicos;
            return this.medicos.filter(m => m.especialidades && m.especialidades.some(e => e.id_especialidad == this.especialidadId));
        },

        onEspecialidadChange() {
            const filtrados = this.medicosFiltrados;
            if (!filtrados.some(m => m.id_medico == this.medicoId)) {
                this.medicoId = '';
            }
        }
     }">

    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-[#0284C7] block">DISPONIBILIDAD MÉDICA</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Oferta Médica y Turnos Libres</h1>
            <p class="text-sm text-slate-500 mt-1">Consulta turnos disponibles en tiempo real cruzando horarios de atención con citas ya agendadas.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('portal') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-xs flex items-center gap-1.5 transition-colors">
                <span>←</span>
                <span>Volver al Portal</span>
            </a>
            <a href="{{ url('/citas') }}" class="btn-primary py-2 text-xs">
                + Ir a Agendar Cita
            </a>
        </div>
    </div>

    <!-- Filtros de Consulta Dinámica -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm max-w-4xl mb-8">
        <form action="{{ url('/disponibilidad') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <!-- Especialidad -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Especialidad Médica *</label>
                <select name="id_especialidad" x-model="especialidadId" @change="onEspecialidadChange()"
                        class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" required>
                    <option value="">Seleccione especialidad...</option>
                    @foreach($especialidades ?? [] as $esp)
                        <option value="{{ $esp->id_especialidad }}" {{ request('id_especialidad') == $esp->id_especialidad ? 'selected' : '' }}>
                            {{ $esp->nombre_especialidad }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Médico Especialista (Filtrado automáticamente) -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Médico Especialista</label>
                    <span class="text-[10px] text-slate-400 font-semibold" x-show="especialidadId">
                        <span x-text="medicosFiltrados.length"></span> doctores
                    </span>
                </div>
                <select name="id_medico" x-model="medicoId"
                        class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none">
                    <option value="">Todos los médicos de esta área</option>
                    <template x-for="m in medicosFiltrados" :key="m.id_medico">
                        <option :value="m.id_medico"
                                :selected="medicoId == m.id_medico"
                                x-text="m.nombre_completo + ' (' + (m.registro_profesional || 'Médico') + ')'"></option>
                    </template>
                </select>
            </div>

            <!-- Fecha -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fecha a Consultar *</label>
                <input type="date" name="fecha" x-model="fecha"
                       value="{{ request('fecha', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}"
                       class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" required>
            </div>

            <div class="sm:col-span-3 flex justify-end pt-2">
                <button type="submit" class="btn-primary py-2.5 px-6 shadow-md shadow-[#0284C7]/20 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                    <span>Consultar Disponibilidad en Tiempo Real</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Resultados de Turnos Disponibles -->
    @if(request()->filled('fecha'))
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <span>Turnos Libres Disponibles</span>
                    <span class="text-xs font-mono font-normal text-slate-500">
                        ({{ \Carbon\Carbon::parse(request('fecha'))->format('d/m/Y') }})
                    </span>
                </h2>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-sky-50 text-[#0284C7] border border-sky-100">
                    {{ count($turnos ?? []) }} bloques horarios libres
                </span>
            </div>

            @if(!empty($ofertaMedica))
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($ofertaMedica as $item)
                        @php
                            $med = $item['medico'] ?? null;
                            $esp = $item['especialidad'] ?? null;
                            $cupos = $item['cupos'] ?? [];
                        @endphp
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all space-y-4">
                            <!-- Info Médico -->
                            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#0284C7] block">
                                        {{ $esp['nombre_especialidad'] ?? 'Especialista' }}
                                    </span>
                                    <h3 class="text-base font-bold text-slate-900 mt-0.5">
                                        {{ $med['nombre_completo'] ?? 'Dr(a). Médico' }}
                                    </h3>
                                    <span class="text-xs text-slate-400">
                                        Reg. Profesional: {{ $med['registro_profesional'] ?? 'MP-Hosp' }}
                                    </span>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ count($cupos) > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                                    {{ count($cupos) }} cupos
                                </span>
                            </div>

                            <!-- Grilla de Cupos -->
                            @if(count($cupos) > 0)
                                <div>
                                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2.5">
                                        Horarios de Consulta Disponibles:
                                    </p>
                                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                        @foreach($cupos as $c)
                                            @php
                                                $horaStr = is_array($c) ? ($c['hora_inicio'] ?? '') : (string)$c;
                                                $horaCorta = substr($horaStr, 0, 5);
                                                $urlReserva = url('/citas?id_medico='.($med['id_medico'] ?? '').'&id_especialidad='.($esp['id_especialidad'] ?? '').'&fecha_cita='.request('fecha').'&hora_cita='.$horaCorta);
                                            @endphp
                                            <a href="{{ $urlReserva }}"
                                               class="p-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-[#0284C7] hover:text-white hover:border-[#0284C7] text-center font-mono font-bold text-xs text-slate-700 transition-all shadow-sm group">
                                                <span>{{ $horaCorta }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-2 text-right">Haz clic en un horario para agendar tu cita.</p>
                                </div>
                            @else
                                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs">
                                    No hay turnos libres para este especialista en la fecha seleccionada.
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400">
                    <p class="text-sm">No se encontraron turnos disponibles para los criterios seleccionados en esta fecha.</p>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
