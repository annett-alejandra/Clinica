@extends('layouts.app')

@section('title', 'Consulta de Disponibilidad y Oferta Médica — Clínica Vida Salud')

@section('content')
@php
    $ahoraBolivia = \Carbon\Carbon::now('America/La_Paz');
    $hoyBolivia = $ahoraBolivia->format('Y-m-d');
    $fechaInput = request('fecha', $hoyBolivia);
    $diasMapa = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];
@endphp

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('disponibilidadMedica', () => ({
        especialidadId: '{{ request('id_especialidad', '') }}',
        medicoId: '{{ request('id_medico', '') }}',
        fecha: '{{ $fechaInput }}',
        medicos: @json($medicos),

        get medicosFiltrados() {
            if (!this.especialidadId) return this.medicos;
            return this.medicos.filter(m => m.especialidades && m.especialidades.some(e => e.id_especialidad == this.especialidadId));
        },

        get diasAtencionTexto() {
            const diasNombres = { 1: 'Lunes', 2: 'Martes', 3: 'Miércoles', 4: 'Jueves', 5: 'Viernes', 6: 'Sábado', 7: 'Domingo' };
            let lista = [];
            if (this.medicoId) {
                const m = this.medicos.find(doc => doc.id_medico == this.medicoId);
                if (m) lista = [m];
            } else if (this.especialidadId) {
                lista = this.medicosFiltrados;
            }
            if (lista.length === 0) return '';

            const setDias = new Set();
            lista.forEach(m => {
                (m.horarios_atencion || []).forEach(h => {
                    if (h.dia_semana) setDias.add(Number(h.dia_semana));
                });
            });
            const ordenados = Array.from(setDias).sort((a, b) => a - b);
            if (ordenados.length === 0) return 'Sin horarios regulares asignados en agenda';
            return 'Atención habitual: ' + ordenados.map(d => diasNombres[d] || d).join(', ');
        },

        renderOpcionesMedicos() {
            const select = this.$refs.selectMedico;
            if (!select) return;
            const currentVal = String(this.medicoId || '');
            select.innerHTML = '';

            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = this.especialidadId
                ? 'Todos los médicos de esta área'
                : 'Todos los médicos (agrupados por especialidad)';
            select.appendChild(defaultOpt);

            if (this.especialidadId) {
                this.medicosFiltrados.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = String(m.id_medico);
                    opt.textContent = `${m.nombre_completo} (${m.registro_profesional || 'Médico'})`;
                    if (String(m.id_medico) === currentVal) opt.selected = true;
                    select.appendChild(opt);
                });
            } else {
                const grupos = {};
                this.medicos.forEach(m => {
                    const espPrincipal = (m.especialidades && m.especialidades.length > 0)
                        ? m.especialidades[0].nombre_especialidad
                        : 'Medicina General';
                    if (!grupos[espPrincipal]) grupos[espPrincipal] = [];
                    grupos[espPrincipal].push(m);
                });

                Object.keys(grupos).sort().forEach(espNombre => {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = espNombre;
                    grupos[espNombre].forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = String(m.id_medico);
                        opt.textContent = `${m.nombre_completo} (${m.registro_profesional || 'Médico'})`;
                        if (String(m.id_medico) === currentVal) opt.selected = true;
                        optgroup.appendChild(opt);
                    });
                    select.appendChild(optgroup);
                });
            }
        },

        onEspecialidadChange() {
            const filtrados = this.medicosFiltrados;
            if (!filtrados.some(m => m.id_medico == this.medicoId)) {
                this.medicoId = '';
            }
            this.renderOpcionesMedicos();
        },

        onMedicoChange() {
            if (!this.especialidadId && this.medicoId) {
                const m = this.medicos.find(doc => doc.id_medico == this.medicoId);
                if (m && m.especialidades && m.especialidades.length > 0) {
                    this.especialidadId = String(m.especialidades[0].id_especialidad);
                    this.renderOpcionesMedicos();
                }
            }
        },

        formatearFechaLocal(d) {
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        },

        seleccionarFechaRapida(diasOffset) {
            const base = new Date();
            base.setDate(base.getDate() + diasOffset);
            this.fecha = this.formatearFechaLocal(base);
        },

        seleccionarProximoLunes() {
            const base = new Date();
            const dia = base.getDay();
            const delta = dia === 0 ? 1 : (8 - dia);
            base.setDate(base.getDate() + delta);
            this.fecha = this.formatearFechaLocal(base);
        },

        init() {
            this.$nextTick(() => {
                this.renderOpcionesMedicos();
            });
        }
    }));
});
</script>

<div class="container py-10 space-y-8" x-data="disponibilidadMedica">

    <!-- Cabecera Institucional -->
    <div class="rounded-2xl bg-gradient-to-br from-white via-sky-50/50 to-emerald-50/40 border border-slate-200/90 p-6 md:p-8 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase tracking-widest bg-sky-100 text-[#0284C7] border border-sky-200">
                        DISPONIBILIDAD MÉDICA EN TIEMPO REAL
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-white text-slate-600 border border-slate-200">
                        <x-icon name="clock" class="w-3 h-3 text-[#0284C7]" />
                        <span>Hora clínica: {{ $ahoraBolivia->format('d/m/Y H:i') }} (Bolivia)</span>
                    </span>
                </div>
                <h1 class="text-3xl md:text-4xl font-serif text-slate-900">Oferta Médica y Turnos Libres</h1>
                <p class="text-sm text-slate-600 mt-1 max-w-2xl">
                    Consulta bloques horarios disponibles cruzando las agendas médicas con las citas confirmadas y recibe sugerencias automáticas de próximas fechas libres.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <a href="{{ route('portal') }}"
                   class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs inline-flex items-center gap-1.5 transition-colors">
                    <x-icon name="arrow-left" class="w-3.5 h-3.5" />
                    <span>Volver al Portal</span>
                </a>
                <a href="{{ route('triage.index') }}"
                   class="px-3.5 py-2.5 rounded-xl border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs inline-flex items-center gap-1.5 transition-colors">
                    <x-icon name="sparkles" class="w-3.5 h-3.5" />
                    <span>Triage IA</span>
                </a>
                <a href="{{ url('/citas') }}"
                   class="px-4 py-2.5 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white font-bold text-xs inline-flex items-center gap-1.5 shadow-md shadow-[#0284C7]/20 transition-all">
                    <x-icon name="calendar" class="w-3.5 h-3.5" />
                    <span>+ Ir a Agendar Cita</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Tarjeta de Búsqueda Dinámica (Ancho Completo) -->
    <div class="bg-white p-6 md:p-7 rounded-2xl border border-slate-200 shadow-sm">
        <form action="{{ url('/disponibilidad') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <!-- Especialidad -->
                <div class="md:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Especialidad Médica *</label>
                    <select name="id_especialidad" x-model="especialidadId" @change="onEspecialidadChange()"
                            class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" required>
                        <option value="">Seleccione especialidad...</option>
                        @foreach($especialidades ?? [] as $esp)
                            <option value="{{ $esp->id_especialidad }}" {{ request('id_especialidad') == $esp->id_especialidad ? 'selected' : '' }}>
                                {{ $esp->nombre_especialidad }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Médico Especialista -->
                <div class="md:col-span-4">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Médico Especialista</label>
                        <span class="text-[10px] text-[#0284C7] font-bold bg-sky-50 px-2 py-0.5 rounded-full border border-sky-100" x-show="especialidadId">
                            <span x-text="medicosFiltrados.length"></span> doctor(es)
                        </span>
                    </div>
                    <select name="id_medico" x-ref="selectMedico" x-model="medicoId" @change="onMedicoChange()"
                            class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none">
                        <option value="">Cargando médicos...</option>
                    </select>
                </div>

                <!-- Fecha -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Fecha *</label>
                    <input type="date" name="fecha" x-model="fecha"
                           value="{{ $fechaInput }}" min="{{ $hoyBolivia }}"
                           class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" required>
                </div>

                <!-- Botón Consultar -->
                <div class="md:col-span-2">
                    <button type="submit"
                            class="w-full py-2.5 px-4 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white text-xs font-bold shadow-md shadow-[#0284C7]/20 flex items-center justify-center gap-2 transition-all">
                        <x-icon name="search" class="w-4 h-4" />
                        <span>Consultar</span>
                    </button>
                </div>
            </div>

            <!-- Fila inferior: Chips de Fecha Rápida + Días Habituales de Atención -->
            <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mr-1">Fecha rápida:</span>
                    <button type="button" @click="seleccionarFechaRapida(0)"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-[#0284C7] text-[11px] font-semibold border border-slate-200 transition-colors">
                        Hoy
                    </button>
                    <button type="button" @click="seleccionarFechaRapida(1)"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-[#0284C7] text-[11px] font-semibold border border-slate-200 transition-colors">
                        Mañana
                    </button>
                    <button type="button" @click="seleccionarFechaRapida(2)"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-[#0284C7] text-[11px] font-semibold border border-slate-200 transition-colors">
                        En 2 días
                    </button>
                    <button type="button" @click="seleccionarProximoLunes()"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-[#0284C7] text-[11px] font-semibold border border-slate-200 transition-colors">
                        Próximo Lunes
                    </button>
                </div>

                <div class="text-xs font-semibold text-emerald-700 flex items-center gap-1.5" x-show="diasAtencionTexto" x-cloak>
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span x-text="diasAtencionTexto"></span>
                </div>
            </div>
        </form>
    </div>

    <!-- Resultados de Turnos Disponibles -->
    @if(request()->filled('fecha'))
        @php
            try {
                $fechaConsultadaCarbon = \Carbon\Carbon::parse(request('fecha'), 'America/La_Paz');
            } catch (\Throwable) {
                $fechaConsultadaCarbon = $ahoraBolivia->copy();
            }
            $diaConsultadoNombre = $diasMapa[$fechaConsultadaCarbon->dayOfWeekIso] ?? 'Día';
            $totalLibres = count($turnos ?? []);
            $totalOcupadosGlobal = collect($ofertaMedica ?? [])->sum(fn ($o) => (int) ($o['total_ocupados'] ?? 0));
            $baseQuery = array_filter([
                'id_especialidad' => request('id_especialidad') ?: ($especialidadSeleccionada?->id_especialidad),
                'id_medico' => request('id_medico'),
            ], fn ($v) => $v !== null && $v !== '');
        @endphp

        <div class="space-y-6">
            <!-- Cabecera de Resultados -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 sm:px-6 rounded-2xl border border-slate-200 shadow-xs">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-lg font-bold text-slate-900">Turnos Libres Disponibles</h2>
                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-mono font-bold">
                        {{ $diaConsultadoNombre }} {{ $fechaConsultadaCarbon->format('d/m/Y') }}
                    </span>
                    @if($especialidadSeleccionada)
                        <span class="px-2.5 py-1 rounded-lg bg-sky-50 text-[#0284C7] border border-sky-200 text-xs font-bold">
                            {{ $especialidadSeleccionada->nombre_especialidad }}
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $totalLibres > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                        {{ $totalLibres }} bloques horarios libres
                    </span>
                    @if($totalOcupadosGlobal > 0)
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            {{ $totalOcupadosGlobal }} ocupados
                        </span>
                    @endif
                </div>
            </div>

            <!-- Caso A: Sin turnos libres en la fecha consultada -> Sugerencias Destacadas -->
            @if($totalLibres === 0)
                <div class="bg-gradient-to-br from-amber-50/70 via-white to-sky-50/60 rounded-2xl border border-amber-200/90 p-6 md:p-7 shadow-sm space-y-5">
                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0">
                                <x-icon name="calendar" class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-slate-900">
                                    No hay turnos libres para el {{ mb_strtolower($diaConsultadoNombre) }} {{ $fechaConsultadaCarbon->format('d/m/Y') }}
                                </h3>
                                <p class="text-xs sm:text-sm text-slate-600 mt-0.5">
                                    @if(empty($ofertaMedica))
                                        Los especialistas seleccionados no tienen bloques configurados para este día de la semana.
                                    @elseif($totalOcupadosGlobal > 0)
                                        Todos los horarios de este día ya se encuentran reservados o han transcurrido.
                                    @else
                                        Los turnos programados para hoy ya pasaron según la hora actual de la clínica ({{ $ahoraBolivia->format('H:i') }} hrs).
                                    @endif
                                </p>
                                @if(!empty($diasAtencionResumen))
                                    <p class="text-xs font-semibold text-[#0284C7] mt-1.5">
                                        Días habituales de atención en esta especialidad: {{ implode(', ', $diasAtencionResumen) }}.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if(!empty($sugerenciasFechas))
                        <div class="pt-4 border-t border-amber-200/70 space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-extrabold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                    <x-icon name="sparkles" class="w-3.5 h-3.5 text-[#0284C7]" />
                                    <span>Próximas Fechas Sugeridas con Cupos Disponibles</span>
                                </p>
                                <span class="text-[11px] text-slate-500">Haz clic para ver los horarios</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                                @foreach($sugerenciasFechas as $sug)
                                    @php
                                        $urlSug = url('/disponibilidad?' . http_build_query(array_merge($baseQuery, ['fecha' => $sug['fecha']])));
                                        $etiquetaDia = $sug['es_hoy']
                                            ? 'Hoy · ' . $sug['dia_nombre']
                                            : ($sug['es_manana'] ? 'Mañana · ' . $sug['dia_nombre'] : $sug['dia_nombre']);
                                    @endphp
                                    <a href="{{ $urlSug }}"
                                       class="group bg-white hover:bg-sky-50/70 rounded-xl border border-slate-200 hover:border-[#0284C7] p-4 transition-all shadow-xs hover:shadow-md flex flex-col justify-between gap-2.5">
                                        <div>
                                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#0284C7] block">
                                                {{ $etiquetaDia }}
                                            </span>
                                            <span class="font-mono text-base font-extrabold text-slate-900 block mt-0.5">
                                                {{ $sug['fecha_legible'] }}
                                            </span>
                                        </div>

                                        <div class="space-y-1.5">
                                            <span class="inline-flex px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold">
                                                {{ $sug['total_cupos'] }} {{ $sug['total_cupos'] === 1 ? 'turno libre' : 'turnos libres' }}
                                            </span>
                                            <p class="text-[11px] text-slate-500">
                                                {{ $sug['primera_hora'] }} – {{ $sug['ultima_hora'] }} · {{ $sug['medicos_count'] }} {{ $sug['medicos_count'] === 1 ? 'médico' : 'médicos' }}
                                            </p>
                                        </div>

                                        <span class="text-xs font-bold text-[#0284C7] group-hover:translate-x-0.5 transition-transform inline-flex items-center gap-1">
                                            <span>Ver horarios</span>
                                            <span>→</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-white border border-slate-200 text-xs text-slate-600 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <span>No encontramos cupos configurados en los próximos 14 días para este filtro. Puedes consultar otra especialidad o usar el Triage IA.</span>
                            <a href="{{ route('triage.index') }}" class="px-3.5 py-2 rounded-lg bg-[#0284C7] text-white font-bold shrink-0 text-center">
                                Ir a Triage IA →
                            </a>
                        </div>
                    @endif
                </div>
            @elseif(!empty($sugerenciasFechas))
                <!-- Caso B: Sí hay turnos hoy -> Franja compacta con otras fechas próximas -->
                <div class="bg-sky-50/60 rounded-2xl border border-sky-200/80 p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                    <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5 shrink-0">
                        <x-icon name="calendar" class="w-4 h-4 text-[#0284C7]" />
                        <span>Otras fechas próximas disponibles:</span>
                    </span>
                    <div class="flex flex-wrap gap-2">
                        @foreach($sugerenciasFechas as $sug)
                            @php
                                $urlSug = url('/disponibilidad?' . http_build_query(array_merge($baseQuery, ['fecha' => $sug['fecha']])));
                            @endphp
                            <a href="{{ $urlSug }}"
                               class="px-3 py-1.5 rounded-xl bg-white hover:bg-[#0284C7] text-slate-700 hover:text-white border border-sky-200 text-xs font-semibold transition-colors inline-flex items-center gap-1.5 shadow-2xs">
                                <span>{{ $sug['es_manana'] ? 'Mañana' : $sug['dia_nombre'] }} {{ $sug['fecha_legible'] }}</span>
                                <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 text-[10px] font-bold">
                                    {{ $sug['total_cupos'] }} libres
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Tarjetas de Agenda por Médico (si hay bloques configurados en el día) -->
            @if(!empty($ofertaMedica))
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($ofertaMedica as $item)
                        @php
                            $med = $item['medico'] ?? null;
                            $esp = $item['especialidad'] ?? null;
                            $cupos = $item['cupos'] ?? [];
                            $todosBloques = $item['todos_bloques'] ?? array_map(fn ($c) => [
                                'hora_inicio' => is_array($c) ? ($c['hora_inicio'] ?? '') : (string) $c,
                                'estado' => 'disponible',
                                'disponible' => true,
                                'ocupado' => false,
                            ], $cupos);
                            $totalOcupados = $item['total_ocupados'] ?? 0;

                            $palabrasNombre = collect(explode(' ', trim((string) ($med['nombre_completo'] ?? 'Dr'))))
                                ->reject(fn ($w) => in_array(mb_strtolower(rtrim($w, '.')), ['dr', 'dra', 'dr(a)'], true))
                                ->values();
                            $iniciales = $palabrasNombre->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'MD';
                        @endphp
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all space-y-4">
                            <!-- Info Médico -->
                            <div class="flex items-start justify-between gap-3 pb-3.5 border-b border-slate-100">
                                <div class="flex items-start gap-3">
                                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#0284C7] to-[#0369A1] text-white font-bold text-sm flex items-center justify-center shrink-0 shadow-sm">
                                        {{ $iniciales }}
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#0284C7] block">
                                            {{ $esp['nombre_especialidad'] ?? 'Especialista' }}
                                        </span>
                                        <h3 class="text-base font-bold text-slate-900 mt-0.5">
                                            {{ $med['nombre_completo'] ?? 'Dr(a). Médico' }}
                                        </h3>
                                        <span class="text-xs text-slate-400">
                                            Reg. Profesional: {{ $med['registro_profesional'] ?? 'MP-Hosp' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-1 shrink-0">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold {{ count($cupos) > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                                        {{ count($cupos) }} libres
                                    </span>
                                    @if($totalOcupados > 0)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200">
                                            {{ $totalOcupados }} ocupados
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Grilla de Bloques -->
                            @if(count($todosBloques) > 0)
                                <div>
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                            Agenda del Día:
                                        </p>
                                        <div class="flex items-center gap-3 text-[10px] text-slate-500">
                                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-white border border-slate-300"></span> Libre</span>
                                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-rose-100 border border-rose-400"></span> Ocupado</span>
                                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-slate-200 border border-slate-300"></span> Pasado</span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                        @foreach($todosBloques as $b)
                                            @php
                                                $horaCorta = substr((string) ($b['hora_inicio'] ?? ''), 0, 5);
                                                $estado = $b['estado'] ?? 'disponible';
                                                $urlReserva = url('/citas?id_medico='.($med['id_medico'] ?? '').'&id_especialidad='.($esp['id_especialidad'] ?? '').'&fecha_cita='.request('fecha').'&hora_cita='.$horaCorta);
                                            @endphp
                                            @if($estado === 'disponible')
                                                <a href="{{ $urlReserva }}"
                                                   class="py-2 px-1.5 rounded-xl border border-slate-200 bg-white hover:bg-emerald-600 hover:text-white hover:border-emerald-600 text-center font-mono font-bold text-xs text-slate-700 transition-all shadow-xs flex flex-col items-center leading-tight group">
                                                    <span>{{ $horaCorta }}</span>
                                                    <span class="text-[9px] font-sans uppercase text-slate-400 group-hover:text-emerald-100 mt-0.5">Libre</span>
                                                </a>
                                            @elseif($estado === 'ocupado')
                                                <span title="Horario ocupado"
                                                      class="py-2 px-1.5 rounded-xl border border-rose-300 bg-rose-50 text-center font-mono font-bold text-xs text-rose-600 cursor-not-allowed flex flex-col items-center leading-tight">
                                                    <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>{{ $horaCorta }}</span>
                                                    <span class="text-[9px] font-sans uppercase text-rose-500 mt-0.5">Ocupado</span>
                                                </span>
                                            @else
                                                <span title="Horario ya pasado"
                                                      class="py-2 px-1.5 rounded-xl border border-slate-200 bg-slate-100 text-center font-mono font-bold text-xs text-slate-400 line-through cursor-not-allowed flex flex-col items-center leading-tight">
                                                    <span>{{ $horaCorta }}</span>
                                                    <span class="text-[9px] font-sans uppercase no-underline mt-0.5">Pasado</span>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-2.5 text-right">Haz clic en un horario libre para agendar tu cita al instante.</p>
                                </div>
                            @else
                                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs">
                                    No hay turnos configurados para este especialista en la fecha seleccionada.
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <!-- Estado Inicial: Acceso Rápido por Especialidad -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Acceso Rápido por Especialidad</h2>
                    <p class="text-xs text-slate-500">Elige una especialidad para consultar inmediatamente sus próximos horarios disponibles.</p>
                </div>
                <span class="text-xs font-semibold text-slate-400">{{ count($especialidades ?? []) }} áreas médicas</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($especialidades ?? [] as $esp)
                    @php
                        $medsEsp = ($medicos ?? collect())->filter(
                            fn ($m) => $m->especialidades && $m->especialidades->contains('id_especialidad', $esp->id_especialidad)
                        );
                        $diasEsp = $medsEsp
                            ->flatMap(fn ($m) => $m->horariosAtencion->pluck('dia_semana'))
                            ->map(fn ($d) => (int) $d)
                            ->unique()
                            ->sort()
                            ->map(fn (int $d) => mb_substr($diasMapa[$d] ?? '', 0, 3))
                            ->values()
                            ->all();
                        $urlRapido = url('/disponibilidad?' . http_build_query([
                            'id_especialidad' => $esp->id_especialidad,
                            'fecha' => $hoyBolivia,
                        ]));
                    @endphp
                    <a href="{{ $urlRapido }}"
                       class="group bg-white hover:bg-sky-50/40 rounded-2xl border border-slate-200 hover:border-[#0284C7] p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between gap-3">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="w-8 h-8 rounded-lg bg-sky-50 text-[#0284C7] border border-sky-100 flex items-center justify-center">
                                    <x-icon name="stethoscope" class="w-4 h-4" />
                                </span>
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold">
                                    {{ $medsEsp->count() }} {{ $medsEsp->count() === 1 ? 'médico' : 'médicos' }}
                                </span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-900 group-hover:text-[#0284C7] transition-colors">
                                {{ $esp->nombre_especialidad }}
                            </h3>
                            <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                                {{ $esp->descripcion ?: 'Atención clínica especializada con reserva en tiempo real.' }}
                            </p>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                            <span class="text-slate-500 font-medium truncate pr-2">
                                {{ !empty($diasEsp) ? implode(' · ', $diasEsp) : 'Consultar agenda' }}
                            </span>
                            <span class="font-bold text-[#0284C7] shrink-0">Ver turnos →</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
