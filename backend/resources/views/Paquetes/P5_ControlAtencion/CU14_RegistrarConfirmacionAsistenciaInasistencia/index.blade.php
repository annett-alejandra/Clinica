@extends('layouts.app')

@section('title', 'Control de Asistencia e Inasistencias — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">CONTROL DE ASISTENCIA</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Control Operativo de Citas</h1>
            <p class="text-sm text-slate-500 mt-1">Recepción: confirmación de citas, registro de llegada a sala de espera y marcación de inasistencia.</p>
        </div>
    </div>

    @include('components.alerts')

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
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <strong class="text-slate-900 text-base">{{ substr($cita->hora_cita, 0, 5) }}</strong>
                                <span class="text-xs text-slate-500 block">{{ $cita->fecha_cita }}</span>
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
                                @php $est = (int) $cita->id_estado_cita; @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                    {{ $est === 1 ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                    {{ $est === 2 ? 'bg-sky-50 text-sky-700 border border-sky-200' : '' }}
                                    {{ $est === 3 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                    {{ $est >= 4 ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}">
                                    {{ $cita->estado?->nombre_estado }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($cita->fecha_hora_llegada)
                                    <span class="text-emerald-700 font-semibold text-xs flex items-center gap-1">
                                        ✓ {{ \Carbon\Carbon::parse($cita->fecha_hora_llegada)->format('H:i') }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">Sin arribar</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
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
                                        <form action="{{ url('/control-atencion/citas/'.$cita->id_cita.'/llegada') }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-2.5 py-1 text-xs rounded border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 font-medium text-emerald-800">
                                                Llegó
                                            </button>
                                        </form>

                                        <form action="{{ url('/control-atencion/citas/'.$cita->id_cita.'/inasistencia') }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-2.5 py-1 text-xs rounded border border-rose-200 bg-rose-50 hover:bg-rose-100 font-medium text-rose-700" onclick="return confirm('¿Marcar inasistencia del paciente?');">
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
