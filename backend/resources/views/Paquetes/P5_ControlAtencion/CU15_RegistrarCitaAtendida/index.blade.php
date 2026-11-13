@extends('layouts.app')

@section('title', 'Atención Médica en Consulta — Clínica Vida Salud')

@section('content')
<div class="container py-12"
     x-data="{
        filtro: 'pendientes',
        busqueda: '',
        coincide(nombre, ci, motivo, estadoId) {
            const term = this.busqueda.toLowerCase().trim();
            const est = parseInt(estadoId);

            if (this.filtro === 'pendientes' && est === 3) return false;
            if (this.filtro === 'atendidas' && est !== 3) return false;

            if (!term) return true;
            return (nombre && nombre.toLowerCase().includes(term)) ||
                   (ci && ci.toLowerCase().includes(term)) ||
                   (motivo && motivo.toLowerCase().includes(term));
        }
     }">

    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-[#0284C7] block">CONSULTA Y REGISTRO CLÍNICO</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Consultas del Día (Atención Médica)</h1>
            <p class="text-sm text-slate-500 mt-1">El facultativo registra las notas de evolución, diagnóstico clínico y marca formalmente la cita como atendida.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('portal') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-xs flex items-center gap-1.5 transition-colors">
                <span>←</span>
                <span>Volver al Portal</span>
            </a>
            <a href="{{ url('/agenda-medica') }}" class="btn-primary py-2 text-xs">
                Ver Agenda Semanal
            </a>
        </div>
    </div>

    <!-- Barra de Herramientas: Filtros y Selector de Médico -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-8 flex flex-col md:flex-row items-center justify-between gap-4">
        
        <!-- Pestañas de Filtro -->
        <div class="flex items-center gap-2 w-full md:w-auto">
            <button type="button" @click="filtro = 'pendientes'"
                    :class="filtro === 'pendientes' ? 'bg-[#0284C7] text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-200 transition-all">
                Pendientes ({{ $citas->where('id_estado_cita', '!=', 3)->count() }})
            </button>
            <button type="button" @click="filtro = 'atendidas'"
                    :class="filtro === 'atendidas' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-200 transition-all">
                Atendidas ({{ $citas->where('id_estado_cita', 3)->count() }})
            </button>
            <button type="button" @click="filtro = 'todas'"
                    :class="filtro === 'todas' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold border border-slate-200 transition-all">
                Todas ({{ count($citas ?? []) }})
            </button>
        </div>

        <!-- Buscador en tiempo real y Selector de Médico -->
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                </span>
                <input type="text" x-model="busqueda"
                       class="w-full pl-9 pr-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-[#0284C7] focus:outline-none"
                       placeholder="Buscar paciente o CI...">
            </div>

            <!-- Selector de Médico para Administradores / Intercambio de Guardia -->
            <form action="{{ url('/atencion-medica') }}" method="GET" class="w-full sm:w-auto">
                <select name="id_medico" onchange="this.form.submit()"
                        class="w-full text-xs font-semibold border-slate-200 rounded-xl px-3 py-2 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none">
                    @foreach($medicos as $m)
                        <option value="{{ $m->id_medico }}" {{ (isset($idMedico) && $idMedico == $m->id_medico) ? 'selected' : '' }}>
                            Dr(a). {{ $m->nombre_completo }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <!-- Grilla de Tarjetas de Consulta Médica -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($citas ?? [] as $cita)
            @php
                $atendida = ((int)$cita->id_estado_cita === 3);
                $pac = $cita->paciente;
            @endphp
            <div x-show="coincide('{{ addslashes($pac?->nombre_completo ?? '') }}', '{{ $pac?->ci ?? '' }}', '{{ addslashes($cita->motivo ?? '') }}', '{{ $cita->id_estado_cita }}')"
                 class="bg-white rounded-2xl border {{ $atendida ? 'border-emerald-200 bg-emerald-50/10' : 'border-slate-200 shadow-sm' }} p-6 flex flex-col justify-between hover:shadow-md transition-all space-y-4">
                
                <div>
                    <!-- Encabezado de la Cita -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <span class="font-mono text-xs font-bold text-slate-700 block">
                                {{ substr($cita->hora_cita, 0, 5) }} · {{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">Ref: {{ substr($cita->codigo_unico, 0, 8) }}</span>
                        </div>
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $atendida ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800' }}">
                            {{ $cita->estado?->nombre_estado }}
                        </span>
                    </div>

                    <!-- Datos del Paciente -->
                    <div class="mt-3">
                        <h3 class="text-base font-bold text-slate-900 leading-snug">{{ $pac?->nombre_completo }}</h3>
                        <div class="flex items-center justify-between text-xs text-slate-500 mt-1">
                            <span class="font-mono font-semibold text-sky-700 bg-sky-50 px-2 py-0.5 rounded border border-sky-100">
                                CI: {{ $pac?->ci }}
                            </span>
                            <span>Tel: {{ $pac?->telefono ?? '—' }}</span>
                        </div>
                    </div>

                    <!-- Botón para ver Ficha / Historial del Paciente -->
                    @if($pac?->ci)
                        <div class="mt-2.5">
                            <a href="{{ url('/historial-paciente?identificador='.$pac->ci) }}"
                               class="text-[11px] font-semibold text-[#0284C7] hover:underline inline-flex items-center gap-1">
                                <span>Ver expediente e historial clínico completo →</span>
                            </a>
                        </div>
                    @endif

                    <!-- Motivo de Consulta -->
                    <div class="text-xs bg-slate-50 border border-slate-100 rounded-xl p-3 mt-3">
                        <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px] block mb-1">Motivo de Consulta:</span>
                        <p class="text-slate-800 italic">{{ $cita->motivo ?: 'Control general preventivo' }}</p>
                    </div>

                    <!-- Diagnóstico / Conclusión si ya fue atendida -->
                    @if($atendida && $cita->observacion_atencion)
                        <div class="text-xs bg-emerald-50/80 border border-emerald-200 rounded-xl p-3.5 mt-3 space-y-1">
                            <span class="font-bold text-emerald-800 uppercase tracking-wider text-[10px] block">Diagnóstico y Prescripción Registrada:</span>
                            <p class="text-slate-800 font-medium">{{ $cita->observacion_atencion }}</p>
                        </div>
                    @endif
                </div>

                <!-- Formulario para atender cita -->
                @if(!$atendida)
                    <form action="{{ url('/citas/'.$cita->id_cita.'/atender') }}" method="POST" class="mt-4 pt-4 border-t border-slate-100 space-y-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Notas Clínicas, Diagnóstico y Prescripción *
                            </label>
                            <textarea name="observacion_atencion" rows="3"
                                      class="w-full text-xs border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none"
                                      placeholder="Indicar evolución clínica, signos vitales, diagnóstico y medicamentos prescritos..." required></textarea>
                        </div>
                        <button type="submit" class="btn-primary w-full justify-center text-xs py-2.5 shadow-md shadow-[#0284C7]/20">
                            Finalizar Consulta y Registrar Evolución
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400">
                <p class="text-sm">No hay consultas programadas para este facultativo.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
