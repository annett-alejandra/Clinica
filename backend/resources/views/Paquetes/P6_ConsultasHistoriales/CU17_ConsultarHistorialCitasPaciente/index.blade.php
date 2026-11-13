@extends('layouts.app')

@section('title', 'Expediente e Historial Clínico — Clínica Vida Salud')

@section('content')
<div class="container py-12"
     x-data="{
        filtroEspecialidad: 'todas',
        filtroEstado: 'todos',
        busqueda: '{{ $busqueda ?? '' }}',
        coincide(espId, estId) {
            if (this.filtroEspecialidad !== 'todas' && String(espId) !== this.filtroEspecialidad) return false;
            if (this.filtroEstado !== 'todos' && String(estId) !== this.filtroEstado) return false;
            return true;
        }
     }">

    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-[#0284C7] block">HISTORIAL CLÍNICO Y EXPEDIENTE</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Expediente Clínico del Paciente</h1>
            <p class="text-sm text-slate-500 mt-1">Consulta cronológica del historial médico, diagnósticos clínicos, evolución y tratamientos por paciente.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('portal') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-xs flex items-center gap-1.5 transition-colors">
                <span>←</span>
                <span>Volver al Portal</span>
            </a>
            @if(isset($paciente))
                <button type="button" onclick="window.print()" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs flex items-center gap-1.5 shadow-sm transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9"/>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                        <rect width="12" height="8" x="6" y="14"/>
                    </svg>
                    <span>Imprimir Ficha</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Buscador Dinámico y Selección Rápida de Pacientes -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-8 space-y-4">
        <form action="{{ url('/historial-paciente') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                </span>
                <input type="text" name="identificador" value="{{ $busqueda ?? '' }}"
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:border-[#0284C7] focus:outline-none"
                       placeholder="Buscar por Cédula (CI: 13730890), Nombre (Annett Cruz) o Correo..." required autofocus>
            </div>
            <button type="submit" class="btn-primary px-6 py-2.5 text-sm shrink-0 flex items-center justify-center gap-1.5 shadow-md shadow-[#0284C7]/20">
                <span>Buscar Expediente</span>
            </button>
        </form>

        <!-- Chips de Acceso Rápido -->
        <div class="pt-3 border-t border-slate-100">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Expedientes Destacados y Recientes:</span>
            <div class="flex flex-wrap gap-2">
                @foreach($pacientesSugeridos ?? [] as $sug)
                    <a href="{{ url('/historial-paciente?identificador='.$sug->ci) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all {{ (isset($paciente) && $paciente->id_paciente == $sug->id_paciente) ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-sm' : ($sug->ci == '13730890' ? 'bg-amber-50 text-amber-900 border-amber-300 hover:bg-amber-100 font-bold' : 'bg-slate-50 text-slate-700 border-slate-200 hover:border-[#0284C7]') }}">
                        @if($sug->ci == '13730890')
                            <span>⭐</span>
                        @endif
                        <span>{{ $sug->nombre_completo }}</span>
                        <span class="font-mono text-[10px] opacity-75">({{ $sug->ci }})</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if(isset($paciente))
        <!-- Ficha Clínica Principal del Paciente -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 md:p-8 shadow-sm mb-8 space-y-6">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 pb-6 border-b border-slate-100">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-sky-50 text-[#0284C7] flex items-center justify-center font-bold text-xl border border-sky-100 shrink-0">
                        {{ strtoupper(substr($paciente->nombre_completo, 0, 2)) }}
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h2 class="text-2xl font-bold text-slate-900">{{ $paciente->nombre_completo }}</h2>
                            <span class="font-mono text-xs font-bold text-sky-700 bg-sky-50 px-2.5 py-0.5 rounded-md border border-sky-200">
                                CI: {{ $paciente->ci }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $paciente->estado ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $paciente->estado ? 'Ficha Activa' : 'Inactiva' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500">
                            Filiación: {{ $paciente->tipo_paciente ?? 'Paciente Asegurado' }} · Código Historia: #HC-{{ str_pad($paciente->id_paciente, 6, '0', STR_PAD_LEFT) }}
                        </p>
                    </div>
                </div>

                <div class="flex gap-4 text-center">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 min-w-[90px]">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Citas</span>
                        <strong class="text-lg font-bold text-slate-800">{{ count($citas ?? []) }}</strong>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-100 min-w-[90px]">
                        <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Atendidas</span>
                        <strong class="text-lg font-bold text-emerald-800">{{ $citas->where('id_estado_cita', 3)->count() }}</strong>
                    </div>
                    <div class="p-3 rounded-xl bg-sky-50/70 border border-sky-100 min-w-[90px]">
                        <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">Programadas</span>
                        <strong class="text-lg font-bold text-[#0284C7]">{{ $citas->whereIn('id_estado_cita', [1, 2])->count() }}</strong>
                    </div>
                </div>
            </div>

            <!-- Datos Biográficos y Médicos Clave -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-slate-400 uppercase font-bold tracking-wider text-[10px] block mb-1">Nacimiento y Edad</span>
                    <strong class="text-slate-800 text-sm">{{ \Carbon\Carbon::parse($paciente->fecha_nacimiento)->format('d/m/Y') }}</strong>
                    <span class="text-slate-500 block">({{ \Carbon\Carbon::parse($paciente->fecha_nacimiento)->age }} años · {{ $paciente->genero == 'F' ? 'Femenino' : ($paciente->genero == 'M' ? 'Masculino' : 'Otro') }})</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-slate-400 uppercase font-bold tracking-wider text-[10px] block mb-1">Contacto Directo</span>
                    <strong class="text-slate-800 text-sm block">{{ $paciente->telefono ?? 'Sin teléfono' }}</strong>
                    <a href="mailto:{{ $paciente->correo }}" class="text-[#0284C7] hover:underline truncate block">{{ $paciente->correo }}</a>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-slate-400 uppercase font-bold tracking-wider text-[10px] block mb-1">Domicilio Registrado</span>
                    <span class="text-slate-700 font-medium line-clamp-2">{{ $paciente->direccion_domicilio ?? 'Santa Cruz de la Sierra, Bolivia' }}</span>
                </div>

                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-slate-400 uppercase font-bold tracking-wider text-[10px] block mb-1">Contacto de Emergencia</span>
                    <strong class="text-slate-800 block">{{ $paciente->contacto_emergencia ?? 'Familiar de Contacto' }}</strong>
                    <span class="text-slate-500">{{ $paciente->telefono_emergencia ?? '—' }}</span>
                </div>
            </div>

            <!-- Alerta Médica de Alergias y Datos Clínicos de Base -->
            @if($paciente->alergias || $paciente->informacion_medica_basica)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    @if($paciente->alergias)
                        <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-xs">
                            <div class="flex items-center gap-1.5 font-bold text-rose-800 mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-rose-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>
                                </svg>
                                <span>ALERGIAS REGISTRADAS (ALERTA CLÍNICA):</span>
                            </div>
                            <p class="text-rose-900 font-semibold">{{ $paciente->alergias }}</p>
                        </div>
                    @endif

                    @if($paciente->informacion_medica_basica)
                        <div class="p-3.5 rounded-xl bg-sky-50 border border-sky-200 text-xs">
                            <div class="flex items-center gap-1.5 font-bold text-sky-800 mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#0284C7] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                                </svg>
                                <span>ANTECEDENTES Y GRUPO SANGUÍNEO:</span>
                            </div>
                            <p class="text-slate-700">{{ $paciente->informacion_medica_basica }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Cronología de Consultas y Atenciones Médicas (Historial) -->
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#0284C7]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    <span>Evolución Médica y Consultas Registradas</span>
                </h3>

                <!-- Filtros rápidos por estado -->
                <div class="flex items-center gap-1.5 text-xs">
                    <button type="button" @click="filtroEstado = 'todos'"
                            :class="filtroEstado === 'todos' ? 'bg-[#0284C7] text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50'"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 font-semibold transition-all">
                        Todas ({{ count($citas ?? []) }})
                    </button>
                    <button type="button" @click="filtroEstado = '3'"
                            :class="filtroEstado === '3' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50'"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 font-semibold transition-all">
                        Atendidas
                    </button>
                    <button type="button" @click="filtroEstado = '1'"
                            :class="filtroEstado === '1' ? 'bg-sky-600 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-50'"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 font-semibold transition-all">
                        Programadas
                    </button>
                </div>
            </div>

            <!-- Listado de Tarjetas de Evolución Clínica -->
            <div class="space-y-4">
                @forelse($citas ?? [] as $c)
                    <div x-show="coincide('{{ $c->id_especialidad }}', '{{ $c->id_estado_cita }}')"
                         class="bg-white rounded-2xl border border-slate-200 p-5 md:p-6 shadow-sm hover:shadow-md transition-all space-y-4">
                        
                        <!-- Cabecera de la Cita -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 {{ $c->id_estado_cita == 3 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($c->id_estado_cita == 1 ? 'bg-sky-50 text-[#0284C7] border border-sky-200' : 'bg-slate-100 text-slate-600') }}">
                                    {{ substr($c->hora_cita, 0, 5) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-slate-900">
                                            {{ \Carbon\Carbon::parse($c->fecha_cita)->format('d/m/Y') }}
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $c->id_estado_cita == 3 ? 'bg-emerald-100 text-emerald-800' : ($c->id_estado_cita == 1 ? 'bg-sky-100 text-sky-800' : ($c->id_estado_cita == 4 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700')) }}">
                                            {{ $c->estado?->nombre_estado ?? 'Registrada' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Dr(a). <strong class="text-slate-800">{{ $c->medico?->nombre_completo }}</strong> · <span class="text-[#0284C7] font-semibold">{{ $c->especialidad?->nombre_especialidad }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="text-left sm:text-right">
                                <span class="font-mono text-[10px] text-slate-400 bg-slate-50 px-2 py-0.5 rounded border border-slate-200">
                                    Ref: {{ substr($c->codigo_unico, 0, 8) }}
                                </span>
                            </div>
                        </div>

                        <!-- Motivo Clínico -->
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Motivo Clínico de Consulta:</span>
                            <p class="text-xs sm:text-sm text-slate-700 font-medium bg-slate-50 p-3 rounded-xl border border-slate-100">
                                {{ $c->motivo ?? 'Consulta médica programada de seguimiento preventivo.' }}
                            </p>
                        </div>

                        <!-- Conclusiones Clínicas y Diagnóstico (si fue atendida) -->
                        @if($c->observacion_atencion)
                            <div class="p-4 rounded-xl bg-emerald-50/50 border border-emerald-200/80 space-y-1.5">
                                <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                    <span>Conclusiones Médicas, Diagnóstico y Tratamiento:</span>
                                </div>
                                <p class="text-xs text-slate-800 leading-relaxed pl-5 font-normal">
                                    {{ $c->observacion_atencion }}
                                </p>
                            </div>
                        @elseif($c->id_estado_cita == 1 || $c->id_estado_cita == 2)
                            <div class="p-3 rounded-xl bg-sky-50 border border-sky-100 text-xs text-sky-800 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#0284C7] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>Cita vigente programada. Las notas médicas y diagnóstico se registrarán al concluir la atención presencial.</span>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400">
                        <p class="text-sm">El paciente no tiene consultas médicas registradas en el historial.</p>
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <!-- Estado sin paciente seleccionado -->
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-sky-50 text-[#0284C7] flex items-center justify-center mx-auto mb-4 border border-sky-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800">No se encontró ningún expediente clínico</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                Ingresa una cédula de identidad válida (ej. <strong class="text-slate-700">13730890</strong>) o el nombre del paciente para ver su historial clínico completo.
            </p>
        </div>
    @endif
</div>
@endsection
