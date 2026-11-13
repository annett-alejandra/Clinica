@extends('layouts.app')

@section('title', 'Cancelar Cita Médica — Clínica Vida Salud')

@section('content')
<div class="container py-12 max-w-xl mx-auto">
    <div class="pb-6 mb-8 border-b border-rose-200">
        <span class="text-xs font-mono font-semibold uppercase tracking-widest text-rose-500">CANCELACIÓN DE CITAS</span>
        <h1 class="text-3xl font-serif text-slate-900 mt-1">Cancelar Cita Médica</h1>
        <p class="text-sm text-slate-500 mt-1">La anulación libera de forma inmediata el espacio en la agenda del especialista.</p>
    </div>

    
    <div class="bg-white p-6 md:p-8 rounded-xl border border-rose-100 shadow-sm">
        @if(isset($cita))
            <div class="bg-rose-50/60 border border-rose-100 rounded-lg p-4 mb-6 text-sm text-slate-700">
                <div class="mb-2"><strong>Código:</strong> <span class="font-mono">{{ $cita->codigo_unico }}</span></div>
                <div><strong>Paciente:</strong> {{ $cita->paciente?->nombre_completo }}</div>
                <div><strong>Médico:</strong> {{ $cita->medico?->nombre_completo }} ({{ $cita->especialidad?->nombre_especialidad }})</div>
                <div class="text-rose-700 mt-2 font-medium">Fecha: {{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }} a las {{ substr($cita->hora_cita, 0, 5) }}</div>
            </div>

            <form action="{{ url('/citas/'.$cita->id_cita.'/cancelar') }}" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Motivo de Cancelación</label>
                    <textarea name="motivo_cancelacion" rows="3" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-rose-500 focus:outline-none" placeholder="Indicar razón médica o particular del paciente..." required></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ url('/agenda-medica') }}" class="btn-minimal">Volver</a>
                    <button type="submit" class="btn-primary" style="background-color: #e11d48; border-color: #e11d48;">
                        Dar de Baja Cita
                    </button>
                </div>
            </form>
        @else
            <p class="text-slate-500 text-center py-6">Selecciona una cita médica para tramitar su baja.</p>
        @endif
    </div>
</div>
@endsection
