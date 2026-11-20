@extends('layouts.app')

@section('title', 'Control de Asistencia e Inasistencias — Clínica Vida Salud')

@section('content')
@php
    $ordenActual = $orden ?? 'llegada';
    $filtroActual = $filtro ?? 'todos';
    $ahoraBolivia = \Carbon\Carbon::now('America/La_Paz');
@endphp
<div class="container py-12">
    <div class="flex flex-col lg:flex-row lg:items-end justify-between pb-6 mb-6 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">CONTROL DE ASISTENCIA</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Control Operativo de Citas</h1>
            <p class="text-sm text-slate-500 mt-1">Recepción: confirmación de citas, registro de llegada a sala de espera y marcación de inasistencia.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-sky-50 border border-sky-200 text-xs font-semibold text-[#0284C7]">
                <x-icon name="clock" class="w-3.5 h-3.5" />
                <span>Hora clínica: {{ $ahoraBolivia->format('d/m/Y H:i') }} (Bolivia)</span>
            </span>
        </div>
    </div>

    <!-- Filtros rápidos y ordenamiento -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-6">
        <div class="flex flex-wrap items-center gap-1.5">
            @php
                $filtros = [
                    'todos' => 'Todas',
                    'sala' => 'En Sala de Espera',
                    'confirmadas' => 'Confirmadas',
                    'programadas' => 'Programadas',
                ];
            @endphp
            @foreach($filtros as $clave => $etiqueta)
                <a href="{{ url('/control-atencion?' . http_build_query(['filtro' => $clave, 'orden' => $ordenActual])) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors {{ $filtroActual === $clave ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                    {{ $etiqueta }}
                </a>
            @endforeach
        </div>

        <div class="flex items-center gap-1.5 text-xs">
            <span class="text-slate-400 font-semibold uppercase tracking-wider text-[11px] mr-1">Orden:</span>
            <a href="{{ url('/control-atencion?' . http_build_query(['filtro' => $filtroActual, 'orden' => 'llegada'])) }}"
               class="px-3 py-1.5 rounded-lg font-semibold border transition-colors {{ $ordenActual === 'llegada' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                Por llegada y turno
            </a>
            <a href="{{ url('/control-atencion?' . http_build_query(['filtro' => $filtroActual, 'orden' => 'turno'])) }}"
               class="px-3 py-1.5 rounded-lg font-semibold border transition-colors {{ $ordenActual === 'turno' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                Solo fecha y turno
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Turno / Fecha</th>
                        <th class="px-6 py-4">Paciente</th>
                        <th class="px-6 py-4">Médico & Especialidad</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4">Llegada a Clínica</th>
                        <th class="px-6 py-4 text-right">Acciones Rápidas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($citas ?? [] as $cita)
                        @php
                            $est = (int) $cita->id_estado_cita;
                            $fechaStr = \Carbon\Carbon::parse($cita->fecha_cita)->format('Y-m-d');
                            $horaStr = substr((string) $cita->hora_cita, 0, 5);
                            $turnoBolivia = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $fechaStr . ' ' . $horaStr, 'America/La_Paz')->startOfMinute();
                            $llegadaBolivia = $cita->fecha_hora_llegada
                                ? \Carbon\Carbon::parse($cita->fecha_hora_llegada)->timezone('America/La_Paz')
                                : null;
                            $horaDefault = $llegadaBolivia
                                ? $llegadaBolivia->format('H:i')
                                : (($fechaStr === $ahoraBolivia->format('Y-m-d') && abs($ahoraBolivia->diffInMinutes($turnoBolivia, false)) <= 120)
                                    ? $ahoraBolivia->format('H:i')
                                    : $horaStr);
                            $diffMin = $llegadaBolivia
                                ? (int) round(($llegadaBolivia->getTimestamp() - $turnoBolivia->getTimestamp()) / 60)
                                : null;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <strong class="text-slate-900 text-base">{{ $horaStr }}</strong>
                                <span class="text-xs text-slate-500 block">{{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $cita->paciente?->nombre_completo }}</div>
                                <div class="text-xs text-slate-500">CI: {{ $cita->paciente?->ci }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div>{{ $cita->medico?->nombre_completo }}</div>
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-sky-50 text-sky-700 border border-sky-100">
                                    {{ $cita->especialidad?->nombre_especialidad }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($est === 2 && $llegadaBolivia)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        En Sala de Espera
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                        {{ $est === 1 ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                        {{ $est === 2 ? 'bg-sky-50 text-sky-700 border border-sky-200' : '' }}
                                        {{ $est === 3 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                        {{ $est >= 4 ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}">
                                        {{ $cita->estado?->nombre_estado }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($llegadaBolivia)
                                    <div class="space-y-1">
                                        <span class="text-emerald-700 font-semibold text-xs flex items-center gap-1">
                                            ✓ {{ $llegadaBolivia->format('H:i') }}
                                            @if($llegadaBolivia->format('Y-m-d') !== $fechaStr)
                                                <span class="text-[11px] text-slate-500 font-normal">({{ $llegadaBolivia->format('d/m') }})</span>
                                            @endif
                                        </span>
                                        @if($diffMin !== null)
                                            @if(abs($diffMin) <= 5)
                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    A tiempo
                                                </span>
                                            @elseif($diffMin < -5)
                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                                    {{ abs($diffMin) }} min antes
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                    +{{ $diffMin }} min tarde
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-400 text-xs">Sin arribar</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    @if($est === 1)
                                        <form action="{{ url('/control-atencion/citas/'.$cita->id_cita.'/confirmar') }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-2.5 py-1 text-xs rounded border border-slate-300 bg-white hover:bg-slate-50 font-medium text-slate-700">
                                                Confirmar
                                            </button>
                                        </form>
                                    @endif

                                    @if(in_array($est, [1, 2]))
                                        <form action="{{ url('/control-atencion/citas/'.$cita->id_cita.'/llegada') }}" method="POST" class="inline-flex items-center gap-1">
                                            @csrf
                                            @method('PATCH')
                                            <input type="time"
                                                   name="hora_llegada"
                                                   value="{{ $horaDefault }}"
                                                   aria-label="Hora de llegada"
                                                   class="px-2 py-1 text-xs rounded border border-emerald-200 bg-white text-slate-700 focus:outline-none focus:border-emerald-500">
                                            <button type="submit" class="px-2.5 py-1 text-xs rounded border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 font-medium text-emerald-800 whitespace-nowrap">
                                                {{ $llegadaBolivia ? 'Ajustar hora' : 'Llegó' }}
                                            </button>
                                        </form>

                                        <a href="{{ url('/citas/'.$cita->id_cita.'/reprogramar') }}"
                                           class="px-2.5 py-1 text-xs rounded border border-sky-200 bg-sky-50 hover:bg-[#0284C7] hover:text-white font-medium text-[#0284C7] transition-colors">
                                            Reprogramar
                                        </a>

                                        <a href="{{ url('/citas/'.$cita->id_cita.'/cancelar') }}"
                                           class="px-2.5 py-1 text-xs rounded border border-rose-200 bg-white hover:bg-rose-600 hover:text-white font-medium text-rose-700 transition-colors">
                                            Cancelar
                                        </a>

                                        <form action="{{ url('/control-atencion/citas/'.$cita->id_cita.'/inasistencia') }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-2.5 py-1 text-xs rounded border border-amber-200 bg-amber-50 hover:bg-amber-100 font-medium text-amber-800" onclick="return confirm('¿Marcar inasistencia del paciente?');">
                                                Inasistió
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">No hay citas pendientes de control en este momento.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
