@extends('layouts.app')

@section('title', 'Programación y Reserva de Citas — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">PROGRAMACIÓN DE CITAS</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Programación de Citas Médicas</h1>
            <p class="text-sm text-slate-500 mt-1">Reserva clínica con comprobación atómica de no superposición y código único de seguimiento.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Formulario de Reserva -->
        <div class="lg:col-span-5 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">Nueva Cita Médica</h2>
            
            <form action="{{ url('/citas') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Paciente</label>
                    <select name="id_paciente" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" required>
                        <option value="">Seleccione paciente...</option>
                        @foreach($pacientes ?? [] as $p)
                            <option value="{{ $p->id_paciente }}">{{ $p->nombre_completo }} (CI: {{ $p->ci }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Especialidad</label>
                    <select name="id_especialidad" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" required>
                        <option value="">Seleccione especialidad...</option>
                        @foreach($especialidades ?? [] as $e)
                            <option value="{{ $e->id_especialidad }}">{{ $e->nombre_especialidad }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Médico Especialista</label>
                    <select name="id_medico" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" required>
                        <option value="">Seleccione médico...</option>
                        @foreach($medicos ?? [] as $m)
                            <option value="{{ $m->id_medico }}">{{ $m->nombre_completo }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Fecha</label>
                        <input type="date" name="fecha_cita" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" required min="{{ date('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Hora</label>
                        <input type="time" name="hora_cita" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Motivo (Opcional)</label>
                    <textarea name="motivo" rows="2" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" placeholder="Síntomas o control preventivo..."></textarea>
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    Confirmar Reserva
                </button>
            </form>
        </div>

        <!-- Listado de Citas Programadas -->
        <div class="lg:col-span-7 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">Citas Programadas</h2>
            
            <div class="space-y-3">
                @forelse($citas ?? [] as $cita)
                    <div class="p-4 rounded-lg border border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">
                                    {{ $cita->estadoCita?->nombre_estado ?? 'Programada' }}
                                </span>
                                <span class="text-xs font-semibold text-slate-500">{{ $cita->fecha_cita }} a las {{ substr($cita->hora_cita, 0, 5) }}</span>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">{{ $cita->paciente?->nombre_completo ?? 'Paciente' }}</h3>
                            <p class="text-xs text-slate-500">Dr(a). {{ $cita->medico?->nombre_completo }} · {{ $cita->especialidad?->nombre_especialidad }}</p>
                        </div>
                        <div class="text-right">
                            <span class="font-mono text-[11px] text-sky-700 bg-sky-50 px-2 py-1 rounded border border-sky-100 block">
                                {{ substr($cita->codigo_unico, 0, 8) }}...
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 py-8 text-center">No hay citas registradas recientemente.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

