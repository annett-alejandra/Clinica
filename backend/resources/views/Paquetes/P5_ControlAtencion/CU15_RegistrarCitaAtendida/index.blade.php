@extends('layouts.app')

@section('title', 'Atención Médica en Consulta — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">PAQUETE 05 · CU15</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Consultas del Día (Atención Médica)</h1>
            <p class="text-sm text-slate-500 mt-1">El facultativo registra las conclusiones clínicas y marca formalmente la cita como atendida.</p>
        </div>
        <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium bg-sky-50 text-sky-800 border border-sky-200">
            Módulo Médico
        </span>
    </div>

    @include('components.alerts')

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($citas ?? [] as $cita)
            @php $atendida = ((int)$cita->id_estado_cita === 3); @endphp
            <div class="bg-white rounded-xl border {{ $atendida ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200' }} p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-mono font-bold text-slate-500">{{ substr($cita->hora_cita, 0, 5) }}</span>
                        <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold {{ $atendida ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800' }}">
                            {{ $cita->estado?->nombre_estado }}
                        </span>
                    </div>

                    <h3 class="text-base font-bold text-slate-900 mb-1">{{ $cita->paciente?->nombre_completo }}</h3>
                    <p class="text-xs text-slate-500 mb-3">CI: {{ $cita->paciente?->ci }} · Tel: {{ $cita->paciente?->telefono }}</p>

                    <div class="text-xs bg-slate-50 border border-slate-100 rounded p-3 mb-4">
                        <span class="font-semibold text-slate-600 block mb-1">Motivo de Consulta:</span>
                        <p class="text-slate-700 italic">{{ $cita->motivo ?: 'Control general preventivo' }}</p>
                    </div>

                    @if($atendida && $cita->observacion_atencion)
                        <div class="text-xs bg-emerald-50/60 border border-emerald-100 rounded p-3 mb-4">
                            <span class="font-semibold text-emerald-800 block mb-1">Diagnóstico / Prescripción:</span>
                            <p class="text-slate-800">{{ $cita->observacion_atencion }}</p>
                        </div>
                    @endif
                </div>

                @if(!$atendida)
                    <form action="{{ url('/citas/'.$cita->id_cita.'/atender') }}" method="POST" class="mt-4 pt-4 border-t border-slate-100 space-y-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-700 uppercase mb-1">Notas Clínicas / Prescripción</label>
                            <textarea name="observacion_atencion" rows="2" class="w-full text-xs border-slate-200 rounded p-2 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="Evolución clínica, medicamentos..." required></textarea>
                        </div>
                        <button type="submit" class="btn-primary w-full justify-center text-xs py-2">
                            Finalizar Consulta (Atendida)
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="col-span-full py-16 text-center text-slate-400 bg-white rounded-xl border border-dashed border-slate-200">
                No hay pacientes citados para atender en este momento.
            </div>
        @endforelse
    </div>
</div>
@endsection
