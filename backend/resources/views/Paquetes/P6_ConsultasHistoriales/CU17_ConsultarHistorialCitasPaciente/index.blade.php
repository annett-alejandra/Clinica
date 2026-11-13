@extends('layouts.app')

@section('title', 'Historial de Citas por Paciente — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">HISTORIAL CLÍNICO DE CITAS</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Expediente Clínico de Citas</h1>
            <p class="text-sm text-slate-500 mt-1">Consulta cronológica del historial de atenciones, inasistencias y cancelaciones por cédula (CI).</p>
        </div>
    </div>

    
    <!-- Buscador por Cédula / Identificador -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm max-w-xl mb-8">
        <form action="{{ url('/historial-paciente') }}" method="GET" class="flex gap-3">
            <input type="text" name="identificador" value="{{ request('identificador') }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="Ingrese número de cédula (CI)..." required>
            <button type="submit" class="btn-primary">Buscar Ficha</button>
        </form>
    </div>

    @if(isset($paciente))
        <!-- Resumen del Paciente -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 mb-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold block">Paciente</span>
                    <strong class="text-slate-900 text-base">{{ $paciente->nombre_completo }}</strong>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold block">Cédula de Identidad</span>
                    <strong class="text-slate-900 font-mono">{{ $paciente->ci }}</strong>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold block">Contacto</span>
                    <span class="text-slate-700">{{ $paciente->telefono }} · {{ $paciente->correo }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase font-semibold block">Total Citas</span>
                    <span class="inline-flex px-2.5 py-0.5 rounded text-xs font-bold bg-sky-100 text-sky-800">
                        {{ count($citas ?? []) }} Registros
                    </span>
                </div>
            </div>
        </div>

        <!-- Tabla Histórica -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Fecha y Hora</th>
                            <th class="px-6 py-4">Especialidad & Médico</th>
                            <th class="px-6 py-4">Motivo Original</th>
                            <th class="px-6 py-4">Estado Final</th>
                            <th class="px-6 py-4">Evolución / Observación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($citas ?? [] as $c)
                            @php $est = (int) $c->id_estado_cita; @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <strong class="text-slate-900">{{ \Carbon\Carbon::parse($c->fecha_cita)->format('d/m/Y') }}</strong>
                                    <span class="text-xs text-slate-400 block">{{ substr($c->hora_cita, 0, 5) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{{ $c->especialidad?->nombre_especialidad }}</div>
                                    <div class="text-xs text-slate-500">{{ $c->medico?->nombre_completo }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600">
                                    {{ $c->motivo ?: 'Control preventivo' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                        {{ $est === 3 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                        {{ $est === 4 ? 'bg-rose-50 text-rose-700 border border-rose-200' : '' }}
                                        {{ $est === 5 ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                        {{ in_array($est, [1, 2]) ? 'bg-sky-50 text-sky-700 border border-sky-200' : '' }}">
                                        {{ $c->estado?->nombre_estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    @if($c->observacion_atencion)
                                        <p class="text-slate-900 font-medium">{{ $c->observacion_atencion }}</p>
                                    @elseif($c->motivo_cancelacion)
                                        <p class="text-rose-600 italic">Cancelación: {{ $c->motivo_cancelacion }}</p>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">El paciente no tiene consultas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
