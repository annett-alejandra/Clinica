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
        cuposDisponibles: [],
        mensajeDisponibilidad: '',

        async consultarCupos() {
            if (!this.fecha) {
                this.cuposDisponibles = [];
                return;
            }
            this.cargandoCupos = true;
            this.mensajeDisponibilidad = '';
            this.cuposDisponibles = [];

            try {
                const url = `{{ url('/api/disponibilidad') }}?id_especialidad=${this.especialidadId}&id_medico=${this.medicoId}&fecha=${this.fecha}`;
                const res = await fetch(url);
                const json = await res.json();

                if (json.success && Array.isArray(json.data) && json.data.length > 0 && json.data[0].cupos) {
                    this.cuposDisponibles = json.data[0].cupos.map(c => (typeof c === 'string' ? c : c.hora_inicio).substring(0, 5));
                    if (this.cuposDisponibles.length === 0) {
                        this.mensajeDisponibilidad = 'No hay cupos libres en esta fecha para este especialista.';
                    } else if (!this.hora || !this.cuposDisponibles.includes(this.hora)) {
                        this.hora = this.cuposDisponibles[0];
                    }
                } else {
                    this.mensajeDisponibilidad = json.message || 'El médico no atiende este día o todos los turnos están ocupados.';
                }
            } catch (e) {
                this.mensajeDisponibilidad = 'No se pudo consultar la disponibilidad en tiempo real.';
            } finally {
                this.cargandoCupos = false;
            }
        },

        seleccionarCupo(h) {
            this.hora = h;
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

                <!-- Turnos Libres Reales del Especialista -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Turnos Libres del Médico</span>
                        <template x-if="cargandoCupos">
                            <span class="text-[11px] text-[#0284C7] font-semibold">Consultando horarios...</span>
                        </template>
                    </div>

                    <template x-if="mensajeDisponibilidad && !cargandoCupos">
                        <div class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs" x-text="mensajeDisponibilidad"></div>
                    </template>

                    <template x-if="cuposDisponibles.length > 0 && !cargandoCupos">
                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-1.5 max-h-40 overflow-y-auto p-0.5">
                            <template x-for="cupo in cuposDisponibles" :key="cupo">
                                <button type="button"
                                        @click="seleccionarCupo(cupo)"
                                        :class="hora === cupo
                                            ? 'bg-[#0284C7] text-white border-[#0284C7] font-bold shadow-sm'
                                            : 'bg-white text-slate-700 border-slate-200 hover:border-[#0284C7] hover:bg-sky-50 font-medium'"
                                        class="py-1.5 px-2 rounded-lg text-xs border text-center transition-all cursor-pointer">
                                    <span x-text="cupo"></span>
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
