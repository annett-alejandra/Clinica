@extends('layouts.app')

@section('title', 'Agenda Médica y Calendario de Turnos — Clínica Vida Salud')

@section('content')
<div class="container py-12"
     x-data="{
        vista: 'calendario',
        idMedico: '{{ $idMedicoSeleccionado ?? '' }}'
     }">

    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-[#0284C7] block">AGENDA Y CALENDARIO MÉDICO</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Agenda Médica y Turnos Semanales</h1>
            <p class="text-sm text-slate-500 mt-1">Visualización semanal interactiva tipo Google Calendar de consultas agendadas y turnos de atención.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('portal') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-xs flex items-center gap-1.5 transition-colors">
                <span>←</span>
                <span>Volver al Portal</span>
            </a>
            <a href="{{ url('/citas') }}" class="btn-primary py-2 text-xs">
                + Nueva Cita
            </a>
        </div>
    </div>

    <!-- Barra de Navegación Semanal (Estilo Google Calendar) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-8 flex flex-col md:flex-row items-center justify-between gap-4">
        
        <!-- Controles de Semana (Anterior, Hoy, Siguiente) -->
        <div class="flex items-center gap-2">
            @php
                $paramMedico = $idMedicoSeleccionado ? '&id_medico='.$idMedicoSeleccionado : '';
            @endphp
            <a href="{{ url('/agenda-medica?semana='.$semanaAnterior.$paramMedico) }}"
               class="p-2 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-600 transition-colors shadow-sm"
               title="Semana Anterior">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
            </a>

            <a href="{{ url('/agenda-medica?semana='.$semanaHoy.$paramMedico) }}"
               class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-sm transition-colors">
                Hoy
            </a>

            <a href="{{ url('/agenda-medica?semana='.$semanaSiguiente.$paramMedico) }}"
               class="p-2 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-600 transition-colors shadow-sm"
               title="Semana Siguiente">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </a>

            <div class="ml-2">
                <span class="text-xs font-bold text-slate-900 block">
                    Semana del {{ $inicioSemana->format('d/m/Y') }} al {{ $finSemana->format('d/m/Y') }}
                </span>
                <span class="text-[11px] text-slate-400">
                    Mes de {{ $inicioSemana->locale('es')->isoFormat('MMMM YYYY') }}
                </span>
            </div>
        </div>

        <!-- Selector de Médico y Conmutador de Vista -->
        <div class="flex items-center gap-3 w-full md:w-auto justify-end">
            <!-- Selector de Médico -->
            <form action="{{ url('/agenda-medica') }}" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="semana" value="{{ $inicioSemana->format('Y-m-d') }}">
                <select name="id_medico" onchange="this.form.submit()"
                        class="text-xs font-semibold border-slate-200 rounded-xl px-3 py-2 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none">
                    <option value="">Todos los Médicos</option>
                    @foreach($medicos as $m)
                        <option value="{{ $m->id_medico }}" {{ (isset($idMedicoSeleccionado) && $idMedicoSeleccionado == $m->id_medico) ? 'selected' : '' }}>
                            Dr(a). {{ $m->nombre_completo }}
                        </option>
                    @endforeach
                </select>
            </form>

            <!-- Botones de Conmutación de Vista -->
            <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200">
                <button type="button" @click="vista = 'calendario'"
                        :class="vista === 'calendario' ? 'bg-white text-[#0284C7] shadow-sm font-bold' : 'text-slate-600 font-medium'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                        <line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>
                    </svg>
                    <span>Calendario</span>
                </button>
                <button type="button" @click="vista = 'tabla'"
                        :class="vista === 'tabla' ? 'bg-white text-[#0284C7] shadow-sm font-bold' : 'text-slate-600 font-medium'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/>
                        <line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/>
                    </svg>
                    <span>Tabla</span>
                </button>
            </div>
        </div>
    </div>

    <!-- VISTA 1: CALENDARIO SEMANAL INTERACTIVO (GOOGLE CALENDAR STYLE) -->
    <div x-show="vista === 'calendario'" class="space-y-6">
        
        <!-- Ficha Resumen del Médico Activo -->
        @if($medicoActual)
            <div class="bg-sky-50/70 border border-sky-100 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0284C7] text-white flex items-center justify-center font-bold">
                        {{ strtoupper(substr($medicoActual->nombre_completo, 0, 2)) }}
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#0284C7] block">Agenda de Turnos Activa:</span>
                        <strong class="text-sm text-slate-900">{{ $medicoActual->nombre_completo }}</strong>
                        <span class="text-slate-500">· Reg: {{ $medicoActual->registro_profesional }}</span>
                    </div>
                </div>
                <div class="text-slate-600">
                    <strong class="text-slate-800">Horarios Registrados:</strong>
                    @if($horariosMedico->count() > 0)
                        <span>{{ $horariosMedico->map(fn($h) => substr($h->hora_inicio, 0, 5).' - '.substr($h->hora_fin, 0, 5))->unique()->implode(', ') }}</span>
                    @else
                        <span>Lunes a Viernes (08:00 - 18:00)</span>
                    @endif
                </div>
            </div>
        @endif

        <!-- Grilla Semanal de 7 Columnas -->
        <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
            @foreach($diasSemana as $dia)
                @php
                    $fechaDia = $dia['fecha'];
                    $citasDelDia = $citas->filter(fn($c) => \Carbon\Carbon::parse($c->fecha_cita)->format('Y-m-d') === $fechaDia);
                    $horarioDia = $horariosMedico->filter(fn($h) => $h->dia_semana == $dia['dia_iso']);
                @endphp
                <div class="bg-white rounded-2xl border {{ $dia['es_hoy'] ? 'border-[#0284C7] shadow-md ring-2 ring-[#0284C7]/20' : 'border-slate-200 shadow-sm' }} overflow-hidden flex flex-col min-h-[380px]">
                    
                    <!-- Encabezado del Día -->
                    <div class="p-3 border-b text-center {{ $dia['es_hoy'] ? 'bg-[#0284C7] text-white' : 'bg-slate-50 text-slate-800' }}">
                        <span class="text-[10px] uppercase font-bold tracking-wider block opacity-90">{{ $dia['dia_nombre'] }}</span>
                        <strong class="text-sm font-bold">{{ $dia['dia_mes'] }}</strong>
                        @if($dia['es_hoy'])
                            <span class="inline-block mt-0.5 px-2 py-0.2 rounded-full text-[9px] font-extrabold uppercase bg-white text-[#0284C7]">Hoy</span>
                        @endif
                    </div>

                    <!-- Turno de Atención del Día -->
                    @if($horarioDia->count() > 0)
                        <div class="px-2 py-1.5 bg-sky-50/60 border-b border-sky-100 text-[10px] text-sky-800 text-center font-semibold">
                            ⏰ {{ $horarioDia->map(fn($h) => substr($h->hora_inicio, 0, 5).'-'.substr($h->hora_fin, 0, 5))->implode(', ') }}
                        </div>
                    @else
                        <div class="px-2 py-1 bg-slate-50/50 border-b border-slate-100 text-[10px] text-slate-400 text-center">
                            Sin turno habitual
                        </div>
                    @endif

                    <!-- Bloques de Citas Médicas Agendadas -->
                    <div class="p-2.5 flex-1 space-y-2 overflow-y-auto max-h-[420px]">
                        @forelse($citasDelDia as $cita)
                            <div class="p-2.5 rounded-xl border text-xs transition-all space-y-1.5 {{ $cita->id_estado_cita == 3 ? 'bg-emerald-50/70 border-emerald-200 text-emerald-950' : ($cita->id_estado_cita == 1 ? 'bg-sky-50/70 border-sky-200 text-sky-950' : ($cita->id_estado_cita == 4 ? 'bg-rose-50 border-rose-200 text-rose-950' : 'bg-slate-50 border-slate-200 text-slate-900')) }}">
                                
                                <div class="flex items-center justify-between">
                                    <span class="font-mono font-bold text-xs {{ $cita->id_estado_cita == 3 ? 'text-emerald-700' : 'text-[#0284C7]' }}">
                                        {{ substr($cita->hora_cita, 0, 5) }}
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase {{ $cita->id_estado_cita == 3 ? 'bg-emerald-100 text-emerald-800' : ($cita->id_estado_cita == 1 ? 'bg-sky-100 text-sky-800' : 'bg-slate-200 text-slate-700') }}">
                                        {{ $cita->estado?->nombre_estado ?? 'Cita' }}
                                    </span>
                                </div>

                                <strong class="text-xs block line-clamp-1 text-slate-900">
                                    {{ $cita->paciente?->nombre_completo ?? 'Paciente' }}
                                </strong>

                                <span class="text-[10px] text-slate-500 block truncate">
                                    {{ $cita->especialidad?->nombre_especialidad }}
                                </span>

                                @if($cita->id_estado_cita == 1 && (auth()->user()?->tieneRol('Médico') || auth()->user()?->tieneRol('Administrador')))
                                    <div class="pt-1 border-t border-slate-200/60">
                                        <a href="{{ url('/atencion-medica?id_medico='.$cita->id_medico) }}" class="text-[10px] font-bold text-[#0284C7] hover:underline flex items-center gap-0.5">
                                            <span>Atender consulta →</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="py-8 text-center text-slate-300 text-xs">
                                <span class="text-slate-300 text-lg block">☕</span>
                                <span class="text-[11px]">Libre</span>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pie del Día con botón rápido de agendar -->
                    <div class="p-2 border-t border-slate-100 bg-slate-50/50 text-center">
                        <a href="{{ url('/citas?fecha_cita='.$fechaDia.'&id_medico='.($medicoActual->id_medico ?? '')) }}"
                           class="text-[11px] font-bold text-[#0284C7] hover:underline block py-1">
                            + Agendar
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- VISTA 2: TABLA CRONOLÓGICA TRADICIONAL CON FILTROS -->
    <div x-show="vista === 'tabla'" class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Fecha y Hora</th>
                            <th class="px-6 py-4">Paciente</th>
                            <th class="px-6 py-4">Médico / Especialidad</th>
                            <th class="px-6 py-4">Motivo Clínico</th>
                            <th class="px-6 py-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($citas ?? [] as $cita)
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
                                    <div class="text-xs text-slate-500 font-mono">CI: {{ $cita->paciente?->ci }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-slate-900 font-medium">{{ $cita->medico?->nombre_completo }}</div>
                                    <span class="text-xs text-[#0284C7] font-semibold">{{ $cita->especialidad?->nombre_especialidad }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 max-w-xs">
                                    <span class="line-clamp-2">{{ $cita->motivo ?: 'Control preventivo' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $cita->id_estado_cita == 3 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($cita->id_estado_cita == 1 ? 'bg-sky-50 text-[#0284C7] border border-sky-200' : 'bg-slate-100 text-slate-600') }}">
                                        {{ $cita->estado?->nombre_estado ?? 'Programada' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                    No hay citas médicas para los criterios seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
