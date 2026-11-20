@extends('layouts.app')

@section('title', 'Alertas Rojas y Monitor Triage IA — Clínica Vida Salud')

@section('content')
@php
    $acciones = $accionesDerivacion ?? \App\Models\Paquetes\P4_GestionCitas\ConsultaTriage::ACCIONES_DERIVACION;
@endphp
<div class="pt-6 pb-20 px-4 sm:px-6 md:px-12 max-w-7xl mx-auto space-y-8">

    <!-- Encabezado -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-6 border-b border-slate-200">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-rose-600 block mb-1">
                CENTRO DE MONITOREO CLÍNICO EN TIEMPO REAL
            </span>
            <h1 class="font-serif text-3xl text-[#0F172A]">
                Alertas Rojas de Emergencia & Triage Vectorial IA
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Supervisión, derivación manual de emergencias detectadas por lenguaje natural y administración de vectores JSONB en PostgreSQL.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('triage.index') }}"
               class="px-4 py-2.5 rounded-xl bg-sky-50 hover:bg-sky-100 text-[#0284C7] border border-sky-200 text-xs font-bold transition-colors">
                Probar Simulador Triage IA
            </a>
            @if (auth()->user()?->tieneRol('Administrador'))
                <form method="POST" action="{{ route('triage.reindexar') }}">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2.5 rounded-xl bg-[#0F172A] hover:bg-slate-800 text-white text-xs font-bold shadow-sm transition-colors inline-flex items-center gap-1.5">
                        <x-icon name="cpu" class="w-3.5 h-3.5 text-sky-400" />
                        <span>Reindexar Vectores JSONB ({{ $totalPerfilesVectorizados }})</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border {{ $kpis['emergencias_activas'] > 0 ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200' }} shadow-sm">
            <span class="text-xs font-semibold text-slate-500 block mb-1">Alertas Rojas Activas</span>
            <span class="text-3xl font-extrabold {{ $kpis['emergencias_activas'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                {{ $kpis['emergencias_activas'] }}
            </span>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 block mb-1">Total Evaluaciones IA</span>
            <span class="text-3xl font-extrabold text-[#0F172A]">{{ $kpis['total_consultas'] }}</span>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 block mb-1">Emergencias Históricas</span>
            <span class="text-3xl font-extrabold text-amber-600">{{ $kpis['emergencias_totales'] }}</span>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 block mb-1">Afinidad Promedio</span>
            <span class="text-3xl font-extrabold text-[#0284C7]">{{ $kpis['afinidad_promedio'] }}%</span>
            <span class="text-[11px] text-slate-400 block mt-0.5">Motor: {{ $proveedorActivo }}</span>
        </div>
    </div>

    <!-- Alertas Rojas Activas (Sin Atender) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-rose-600 text-white flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <x-icon name="alert-triangle" class="w-5 h-5 animate-pulse" />
                <h2 class="font-bold text-sm uppercase tracking-wider">Alertas Rojas Pendientes de Atención y Derivación Manual</h2>
            </div>
            <span class="px-2.5 py-0.5 rounded-full bg-white text-rose-700 text-xs font-extrabold">
                {{ count($alertasActivas) }} activa(s)
            </span>
        </div>

        <div class="p-6">
            @forelse ($alertasActivas as $alerta)
                @php
                    $paramsCita = array_filter([
                        'id_paciente' => $alerta['id_paciente'] ?? null,
                        'id_especialidad' => $alerta['id_especialidad_sugerida'] ?? null,
                        'id_medico' => $alerta['id_medico_sugerido'] ?? null,
                        'motivo' => \Illuminate\Support\Str::limit('[URGENCIA TRIAGE #' . $alerta['id_triage'] . '] ' . ($alerta['sintomas_texto'] ?? ''), 220),
                    ], fn ($v) => $v !== null && $v !== '');
                    $urlCitaPrioritaria = url('/citas?' . http_build_query($paramsCita));
                @endphp
                <div class="mb-5 last:mb-0 p-5 rounded-xl border-2 border-rose-200 bg-rose-50/60 space-y-4">
                    <!-- Cabecera y detalle clínico de la alerta -->
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                        <div class="space-y-1.5 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2 py-0.5 rounded bg-rose-600 text-white text-[10px] font-extrabold uppercase">
                                    CÓDIGO ROJO #{{ $alerta['id_triage'] }}
                                </span>
                                <span class="font-bold text-sm text-slate-900">{{ $alerta['paciente_nombre'] }}</span>
                                @if (!empty($alerta['paciente_ci']))
                                    <span class="text-xs text-slate-500">CI: {{ $alerta['paciente_ci'] }}</span>
                                @endif
                                @if (!empty($alerta['paciente_telefono']))
                                    <a href="tel:{{ $alerta['paciente_telefono'] }}"
                                       class="text-xs font-semibold text-rose-700 hover:underline">
                                        Tel: {{ $alerta['paciente_telefono'] }}
                                    </a>
                                @endif
                            </div>
                            <p class="text-sm text-slate-800 font-medium">"{{ $alerta['sintomas_texto'] }}"</p>
                            <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                @foreach ($alerta['palabras_detectadas'] as $pal)
                                    <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-200 text-[11px] font-bold">
                                        {{ $pal }}
                                    </span>
                                @endforeach
                                <span class="text-[11px] text-slate-500 ml-1">
                                    Sugerido IA: <strong>{{ $alerta['especialidad_sugerida'] }}</strong>
                                    @if (!empty($alerta['medico_sugerido']))
                                        ({{ $alerta['medico_sugerido'] }})
                                    @endif
                                    @if (!empty($alerta['puntaje_afinidad']))
                                        · {{ number_format((float) $alerta['puntaje_afinidad'], 1) }}%
                                    @endif
                                    · {{ $alerta['fecha_registro'] }}
                                </span>
                            </div>
                        </div>

                        <!-- Acciones rápidas de contacto / agendamiento prioritario -->
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            @if (!empty($alerta['paciente_telefono']))
                                <a href="tel:{{ $alerta['paciente_telefono'] }}"
                                   class="px-3 py-2 rounded-lg bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold inline-flex items-center gap-1.5 transition-colors">
                                    <x-icon name="phone-call" class="w-3.5 h-3.5" />
                                    <span>Llamar al paciente</span>
                                </a>
                            @endif
                            <a href="{{ $urlCitaPrioritaria }}"
                               class="px-3 py-2 rounded-lg bg-[#0284C7] hover:bg-[#0369A1] text-white text-xs font-bold inline-flex items-center gap-1.5 shadow-sm transition-colors">
                                <x-icon name="calendar" class="w-3.5 h-3.5" />
                                <span>Derivar a Cita Prioritaria →</span>
                            </a>
                        </div>
                    </div>

                    <!-- Formulario de Derivación y Resolución Manual -->
                    <form method="POST"
                          action="{{ route('triage.alertas.atender', ['id' => $alerta['id_triage']]) }}"
                          class="pt-3.5 border-t border-rose-200/80 grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        @csrf
                        @method('PATCH')

                        <div class="md:col-span-3">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">
                                Acción / Derivación *
                            </label>
                            <select name="tipo_derivacion"
                                    required
                                    class="w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-rose-500 focus:outline-none">
                                @foreach ($acciones as $codigo => $etiqueta)
                                    <option value="{{ $codigo }}">{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">
                                Médico de Guardia Asignado
                            </label>
                            <select name="id_medico_asignado"
                                    class="w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-rose-500 focus:outline-none">
                                <option value="">Sin asignar / Equipo de Urgencias</option>
                                @foreach ($medicosGuardia ?? [] as $med)
                                    <option value="{{ $med->id_medico }}" @selected((int) ($alerta['id_medico_sugerido'] ?? 0) === (int) $med->id_medico)>
                                        {{ $med->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">
                                Observación / Gestión Realizada
                            </label>
                            <input type="text"
                                   name="observacion"
                                   maxlength="500"
                                   placeholder="Ej. Trasladado a Box Shock 02, familiar notificado..."
                                   class="w-full rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-rose-500 focus:outline-none">
                        </div>

                        <div class="md:col-span-2">
                            <button type="submit"
                                    class="w-full px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition-colors">
                                Confirmar Derivación y Cerrar Alerta
                            </button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="py-8 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center mx-auto mb-3">
                        <x-icon name="check-circle" class="w-6 h-6" />
                    </div>
                    <p class="text-sm font-bold text-slate-700">Sin alertas rojas pendientes en este momento</p>
                    <p class="text-xs text-slate-400 mt-0.5">Cualquier emergencia reportada en el Triage IA aparecerá aquí y en la barra superior de forma automática.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Bitácora Histórica de Consultas Triage IA -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="font-bold text-base text-[#0F172A]">Historial de Evaluaciones de Triage Inteligente</h2>
            <span class="text-xs text-slate-400">Últimos registros clínicos NLP</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200">
                        <th class="py-3 px-5">ID / Fecha</th>
                        <th class="py-3 px-5">Paciente</th>
                        <th class="py-3 px-5">Síntomas Reportados</th>
                        <th class="py-3 px-5">Urgencia</th>
                        <th class="py-3 px-5">Especialista Sugerido</th>
                        <th class="py-3 px-5">Estado / Resolución</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70 text-xs">
                    @forelse ($consultas as $c)
                        @php
                            $resolucion = $c->resolucionManual();
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="py-3.5 px-5 font-mono text-slate-500 whitespace-nowrap">
                                <span class="font-bold text-[#0284C7] block">#{{ $c->id_triage }}</span>
                                {{ \Carbon\Carbon::parse($c->fecha_registro)->timezone('America/La_Paz')->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3.5 px-5 font-semibold text-slate-800">
                                {{ $c->paciente?->nombre_completo ?? $c->usuario?->nombreVisible() ?? 'Visitante' }}
                            </td>
                            <td class="py-3.5 px-5 text-slate-600 max-w-xs">
                                <p class="line-clamp-2">{{ $c->sintomas_texto }}</p>
                            </td>
                            <td class="py-3.5 px-5">
                                @if ($c->es_emergencia)
                                    <span class="px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200 font-extrabold text-[10px]">EMERGENCIA</span>
                                @elseif ($c->nivel_urgencia === 'PRIORITARIO')
                                    <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200 font-bold text-[10px]">PRIORITARIO</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[10px]">NORMAL</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-800 block">{{ $c->especialidadRecomendada?->nombre_especialidad ?? '—' }}</span>
                                <span class="text-slate-500">{{ $c->medicoRecomendado?->nombre_completo ?? '' }} ({{ number_format((float) $c->puntaje_afinidad, 1) }}%)</span>
                            </td>
                            <td class="py-3.5 px-5 max-w-xs">
                                @if ($c->es_emergencia)
                                    @if ($c->alerta_atendida)
                                        <div class="space-y-1">
                                            @if (!empty($resolucion['etiqueta_derivacion']))
                                                <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold text-[10px]">
                                                    {{ $resolucion['etiqueta_derivacion'] }}
                                                </span>
                                            @endif
                                            <span class="text-emerald-700 font-semibold block">
                                                Atendida por {{ $c->usuarioAtendio?->nombre_usuario ?? 'Staff' }}
                                                @if ($c->fecha_atencion_alerta)
                                                    · {{ \Carbon\Carbon::parse($c->fecha_atencion_alerta)->timezone('America/La_Paz')->format('d/m H:i') }}
                                                @endif
                                            </span>
                                            @if (!empty($resolucion['medico_asignado']))
                                                <span class="text-[11px] text-slate-600 block">Médico: {{ $resolucion['medico_asignado'] }}</span>
                                            @endif
                                            @if (!empty($resolucion['observacion']))
                                                <span class="text-[11px] text-slate-500 italic block">"{{ $resolucion['observacion'] }}"</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-rose-600 font-extrabold animate-pulse">Pendiente de derivación</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">Aún no se registran evaluaciones en el sistema de Triage IA.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($consultas instanceof \Illuminate\Pagination\LengthAwarePaginator && $consultas->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $consultas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
