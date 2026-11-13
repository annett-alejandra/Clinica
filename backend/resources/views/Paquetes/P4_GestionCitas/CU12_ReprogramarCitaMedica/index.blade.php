@extends('layouts.app')

@section('title', 'Reprogramar Cita Médica — Clínica Vida Salud')

@section('content')
<div class="container py-12 max-w-2xl mx-auto">
    <div class="pb-6 mb-8 border-b border-slate-200">
        <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">REPROGRAMACIÓN DE CITAS</span>
        <h1 class="text-3xl font-serif text-slate-900 mt-1">Reprogramar Cita Médica</h1>
        <p class="text-sm text-slate-500 mt-1">Modificación de fecha u hora liberando de inmediato la franja anterior.</p>
    </div>

    
    <div class="bg-white p-6 md:p-8 rounded-xl border border-slate-200 shadow-sm">
        @if(isset($cita))
            <div class="bg-sky-50/60 border border-sky-100 rounded-lg p-4 mb-6 text-sm">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <span class="text-xs text-sky-800 font-semibold block uppercase">Paciente</span>
                        <strong class="text-slate-900">{{ $cita->paciente?->nombre_completo }}</strong>
                        <span class="text-xs text-slate-500 block">CI: {{ $cita->paciente?->ci }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-sky-800 font-semibold block uppercase">Especialista</span>
                        <strong class="text-slate-900">{{ $cita->medico?->nombre_completo }}</strong>
                        <span class="text-xs text-slate-500 block">{{ $cita->especialidad?->nombre_especialidad }}</span>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-sky-100 text-xs text-amber-700 font-medium">
                    Horario Actual: {{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }} a las {{ substr($cita->hora_cita, 0, 5) }} ({{ $cita->estado?->nombre_estado }})
                </div>
            </div>

            <form action="{{ url('/citas/'.$cita->id_cita.'/reprogramar') }}" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nueva Fecha *</label>
                        <input type="date" name="fecha_cita" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" required min="{{ date('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nueva Hora *</label>
                        <input type="time" name="hora_cita" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Motivo de Reprogramación</label>
                    <textarea name="motivo_reprogramacion" rows="2" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" placeholder="Solicitado por el paciente / ajuste operativo..."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ url('/agenda-medica') }}" class="btn-minimal">Volver</a>
                    <button type="submit" class="btn-primary">Confirmar Reprogramación</button>
                </div>
            </form>
        @else
            <p class="text-slate-500 text-center py-6">Selecciona una cita desde la agenda médica para reprogramarla.</p>
        @endif
    </div>
</div>
@endsection
