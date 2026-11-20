@extends('layouts.app')

@section('title', 'Reprogramar Cita Médica — Clínica Vida Salud')

@section('content')
@if(isset($cita))
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('reprogramarCita', () => ({
        especialidadId: {{ (int) $cita->id_especialidad }},
        medicoId: {{ (int) $cita->id_medico }},
        fecha: @js(old('fecha_cita', \Carbon\Carbon::parse($cita->fecha_cita)->format('Y-m-d'))),
        hora: @js(old('hora_cita', '')),
        cargandoCupos: false,
        bloquesHorario: [],
        cuposDisponibles: [],
        mensajeDisponibilidad: '',

        get totalLibres() {
            return this.bloquesHorario.filter(b => b.estado === 'disponible').length;
        },

        get totalOcupados() {
            return this.bloquesHorario.filter(b => b.estado === 'ocupado').length;
        },

        async consultarCupos() {
            if (!this.fecha) {
                this.bloquesHorario = [];
                this.cuposDisponibles = [];
                return;
            }
            this.cargandoCupos = true;
            this.mensajeDisponibilidad = '';
            this.bloquesHorario = [];
            this.cuposDisponibles = [];

            try {
                const url = `/api/disponibilidad?id_especialidad=${this.especialidadId}&id_medico=${this.medicoId}&fecha=${this.fecha}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();

                if (json.success && Array.isArray(json.data) && json.data.length > 0) {
                    const item = json.data[0];
                    const origen = Array.isArray(item.todos_bloques) && item.todos_bloques.length > 0
                        ? item.todos_bloques
                        : (item.cupos || []).map(c => ({
                            hora_inicio: typeof c === 'string' ? c : c.hora_inicio,
                            estado: 'disponible',
                            disponible: true,
                        }));

                    this.bloquesHorario = origen.map(b => ({
                        hora: String(b.hora_inicio || '').substring(0, 5),
                        estado: b.estado || (b.ocupado ? 'ocupado' : 'disponible'),
                        disponible: b.estado ? b.estado === 'disponible' : !b.ocupado,
                    }));
                    this.cuposDisponibles = this.bloquesHorario.filter(b => b.disponible).map(b => b.hora);

                    if (this.cuposDisponibles.length === 0) {
                        this.mensajeDisponibilidad = 'No hay cupos libres en esta fecha para este especialista (todos ocupados o pasados).';
                        this.hora = '';
                    } else if (this.hora && !this.cuposDisponibles.includes(this.hora.substring(0, 5))) {
                        this.hora = '';
                    }
                } else {
                    this.hora = '';
                    this.mensajeDisponibilidad = json.message || 'El médico no atiende este día.';
                }
            } catch (e) {
                this.mensajeDisponibilidad = 'No se pudo consultar la disponibilidad en tiempo real.';
            } finally {
                this.cargandoCupos = false;
            }
        },

        seleccionarCupo(bloque) {
            if (!bloque || !bloque.disponible) return;
            this.hora = bloque.hora;
        },

        init() {
            this.consultarCupos();
        }
    }));
});
</script>
@endif

@php
    $urlVolver = auth()->user()?->tieneRol('Paciente')
        ? url('/mis-citas')
        : url('/control-atencion');
@endphp

<div class="container py-12 max-w-2xl mx-auto" @if(isset($cita)) x-data="reprogramarCita" @endif>
    <div class="pb-6 mb-8 border-b border-slate-200 flex items-center justify-between gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-[#0284C7]">REPROGRAMACIÓN DE CITAS</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Reprogramar Cita Médica</h1>
            <p class="text-sm text-slate-500 mt-1">Modificación de fecha u hora liberando de inmediato la franja anterior.</p>
        </div>
        <a href="{{ $urlVolver }}" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-xs shrink-0">
            ← Volver
        </a>
    </div>

    <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200 shadow-sm">
        @if(isset($cita))
            <div class="bg-sky-50/60 border border-sky-100 rounded-xl p-4 mb-6 text-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <span class="text-[11px] text-sky-800 font-bold block uppercase tracking-wider">Paciente</span>
                        <strong class="text-slate-900">{{ $cita->paciente?->nombre_completo }}</strong>
                        <span class="text-xs text-slate-500 block font-mono">CI: {{ $cita->paciente?->ci }}</span>
                    </div>
                    <div>
                        <span class="text-[11px] text-sky-800 font-bold block uppercase tracking-wider">Especialista</span>
                        <strong class="text-slate-900">{{ $cita->medico?->nombre_completo }}</strong>
                        <span class="text-xs text-[#0284C7] font-semibold block">{{ $cita->especialidad?->nombre_especialidad }}</span>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-sky-100 text-xs text-amber-800 font-semibold flex items-center justify-between">
                    <span>Horario Actual: {{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }} a las {{ substr($cita->hora_cita, 0, 5) }}</span>
                    <span class="px-2 py-0.5 rounded bg-white border border-amber-200 text-[10px] uppercase">{{ $cita->estado?->nombre_estado }}</span>
                </div>
            </div>

            <form action="{{ url('/citas/'.$cita->id_cita.'/reprogramar') }}" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nueva Fecha *</label>
                        <input type="date" name="fecha_cita" x-model="fecha" @change="consultarCupos()"
                               class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none"
                               required min="{{ date('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nueva Hora Seleccionada *</label>
                        <input type="time" name="hora_cita" x-model="hora" step="1800"
                               class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none font-bold text-[#0284C7]" required>
                    </div>
                </div>

                <!-- Turnos del Especialista (Disponibles sin color, Seleccionado verde, Ocupados rojo) -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Horarios del Médico</span>
                        <template x-if="cargandoCupos">
                            <span class="text-[11px] text-[#0284C7] font-semibold">Consultando horarios...</span>
                        </template>
                        <template x-if="!cargandoCupos && bloquesHorario.length > 0">
                            <div class="flex items-center gap-1.5 text-[11px] font-bold">
                                <span class="px-2 py-0.5 rounded-full bg-white text-slate-700 border border-slate-200"><span x-text="totalLibres"></span> libres</span>
                                <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200" x-show="totalOcupados > 0"><span x-text="totalOcupados"></span> ocupados</span>
                            </div>
                        </template>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pb-2 border-b border-slate-200/70 text-[11px] text-slate-600">
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-white border border-slate-300"></span> Disponible</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-600"></span> <span class="font-semibold text-emerald-700">Seleccionado</span></span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-100 border border-rose-400"></span> <span class="font-semibold text-rose-600">Ocupado</span></span>
                    </div>

                    <template x-if="mensajeDisponibilidad && !cargandoCupos">
                        <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs" x-text="mensajeDisponibilidad"></div>
                    </template>

                    <template x-if="bloquesHorario.length > 0 && !cargandoCupos">
                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 max-h-52 overflow-y-auto p-0.5">
                            <template x-for="b in bloquesHorario" :key="b.hora">
                                <button type="button"
                                        @click="seleccionarCupo(b)"
                                        :disabled="!b.disponible"
                                        :title="b.estado === 'ocupado' ? 'Horario ocupado' : (b.estado === 'pasado' ? 'Horario pasado' : 'Seleccionar ' + b.hora)"
                                        :class="b.estado === 'ocupado'
                                            ? 'bg-rose-50 text-rose-600 border-rose-300 cursor-not-allowed'
                                            : (b.estado === 'pasado'
                                                ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed line-through'
                                                : (hora && hora.substring(0, 5) === b.hora
                                                    ? 'bg-emerald-600 text-white border-emerald-600 font-bold shadow-md shadow-emerald-600/25 ring-2 ring-emerald-200'
                                                    : 'bg-white text-slate-700 border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/40 font-semibold cursor-pointer'))"
                                        class="py-2 px-1.5 rounded-xl text-xs border text-center transition-all flex flex-col items-center justify-center leading-tight">
                                    <span class="font-mono font-bold flex items-center gap-1">
                                        <span x-show="b.estado === 'ocupado'" class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span x-show="b.disponible && hora && hora.substring(0, 5) === b.hora">✓</span>
                                        <span x-text="b.hora"></span>
                                    </span>
                                    <span class="text-[9px] uppercase mt-0.5"
                                          :class="b.estado === 'ocupado' ? 'text-rose-500' : (b.disponible && hora && hora.substring(0, 5) === b.hora ? 'text-emerald-100' : 'text-slate-400')"
                                          x-text="b.estado === 'ocupado' ? 'Ocupado' : (b.estado === 'pasado' ? 'Pasado' : (hora && hora.substring(0, 5) === b.hora ? 'Elegido' : 'Libre'))"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Motivo / Justificación de Reprogramación *</label>
                    <textarea name="motivo_reprogramacion" rows="2" required
                              class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none"
                              placeholder="Indique el motivo del cambio de horario..."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ $urlVolver }}" class="btn-minimal">Cancelar y Volver</a>
                    <button type="submit" class="btn-primary">Confirmar Reprogramación</button>
                </div>
            </form>
        @else
            <p class="text-slate-500 text-center py-6">Selecciona una cita vigente para reprogramarla.</p>
        @endif
    </div>
</div>
@endsection
