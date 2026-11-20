@extends('layouts.app')

@section('title', 'Cancelar Cita Médica — Clínica Vida Salud')

@section('content')
@php
    $prevPath = parse_url(url()->previous(), PHP_URL_PATH) ?: '';
    $rutasOrigen = ['/citas', '/mis-citas', '/control-atencion', '/portal', '/agenda-medica'];
    $urlDefault = auth()->user()?->tieneRol('Paciente') ? '/mis-citas' : '/control-atencion';
    $urlVolver = in_array($prevPath, $rutasOrigen, true) ? $prevPath : $urlDefault;
    $est = (int) ($cita->id_estado_cita ?? 0);
@endphp
<div class="container py-12 max-w-xl mx-auto">
    <div class="pb-6 mb-8 border-b border-rose-200 flex items-center justify-between gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-rose-500">CANCELACIÓN DE CITAS</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Cancelar Cita Médica</h1>
            <p class="text-sm text-slate-500 mt-1">La anulación libera de forma inmediata el espacio en la agenda del especialista.</p>
        </div>
        <a href="{{ url($urlVolver) }}" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-xs shrink-0">
            ← Volver
        </a>
    </div>

    <div class="bg-white p-6 md:p-8 rounded-xl border border-rose-100 shadow-sm">
        @if(isset($cita))
            <div class="bg-rose-50/60 border border-rose-100 rounded-lg p-4 mb-6 text-sm text-slate-700 space-y-1.5">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <div><strong>Código:</strong> <span class="font-mono text-xs">{{ $cita->codigo_unico }}</span></div>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider border
                        {{ $est === 1 ? 'bg-amber-50 text-amber-800 border-amber-200' : '' }}
                        {{ $est === 2 ? 'bg-sky-50 text-sky-700 border-sky-200' : '' }}
                        {{ $est === 3 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : '' }}
                        {{ $est >= 4 ? 'bg-rose-100 text-rose-800 border-rose-200' : '' }}">
                        {{ $cita->estado?->nombre_estado ?? 'Programada' }}
                    </span>
                </div>
                <div><strong>Paciente:</strong> {{ $cita->paciente?->nombre_completo }} <span class="font-mono text-xs text-slate-500">(CI: {{ $cita->paciente?->ci }})</span></div>
                <div><strong>Médico:</strong> {{ $cita->medico?->nombre_completo }} ({{ $cita->especialidad?->nombre_especialidad }})</div>
                <div class="text-rose-700 pt-2 font-semibold">Fecha: {{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }} a las {{ substr($cita->hora_cita, 0, 5) }}</div>
            </div>

            @error('id_estado_cita')
                <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700">
                    {{ $message }}
                </div>
            @enderror

            @if(in_array($est, [1, 2], true))
                <form action="{{ url('/citas/'.$cita->id_cita.'/cancelar') }}"
                      method="POST"
                      x-data="{ enviando: false }"
                      @submit="enviando = true"
                      class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="redirect_to" value="{{ $urlVolver }}">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Motivo / Justificación de Cancelación *</label>
                        <textarea name="motivo_cancelacion" rows="3" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-rose-500 focus:outline-none" placeholder="Indicar razón médica o particular del paciente..." required>{{ old('motivo_cancelacion') }}</textarea>
                        @error('motivo_cancelacion')
                            <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <a href="{{ url($urlVolver) }}" class="btn-minimal">Volver</a>
                        <button type="submit"
                                :disabled="enviando"
                                :class="enviando ? 'opacity-60 cursor-not-allowed' : ''"
                                class="btn-primary"
                                style="background-color: #e11d48; border-color: #e11d48;"
                                x-text="enviando ? 'Cancelando cita...' : 'Confirmar Cancelación'">
                            Confirmar Cancelación
                        </button>
                    </div>
                </form>
            @else
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-700 space-y-2">
                    <p class="font-semibold text-slate-900">
                        Esta cita ya se encuentra en estado <span class="text-rose-700">{{ $cita->estado?->nombre_estado ?? 'Procesada' }}</span>.
                    </p>
                    @if(!empty($cita->motivo_cancelacion))
                        <p class="text-xs text-slate-600"><strong>Motivo registrado:</strong> {{ $cita->motivo_cancelacion }}</p>
                    @endif
                    <div class="pt-2">
                        <a href="{{ url($urlVolver) }}" class="btn-primary inline-flex items-center gap-1.5 text-xs">
                            <span>← Volver al listado</span>
                        </a>
                    </div>
                </div>
            @endif
        @else
            <p class="text-slate-500 text-center py-6">Selecciona una cita médica para tramitar su baja.</p>
        @endif
    </div>
</div>
@endsection
