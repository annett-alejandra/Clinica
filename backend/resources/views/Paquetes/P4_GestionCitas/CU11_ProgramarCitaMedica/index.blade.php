@extends('layouts.app')

@section('title', 'Programación y Reserva de Citas — Clínica Vida Salud')

@section('content')
@php
    $pacienteDefaultId = auth()->user()?->tieneRol('Paciente') && auth()->user()?->id_paciente
        ? (string) auth()->user()->id_paciente
        : '';
@endphp
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('reservaCitas', () => ({
        pacienteId: '{{ old('id_paciente', request('id_paciente', $pacienteDefaultId)) }}',
        especialidadId: '{{ old('id_especialidad', request('id_especialidad', '')) }}',
        medicoId: '{{ old('id_medico', request('id_medico', '')) }}',
        fecha: '{{ old('fecha_cita', request('fecha_cita', date('Y-m-d'))) }}',
        hora: '{{ old('hora_cita', request('hora_cita', '')) }}',
        cargandoCupos: false,
        bloquesHorario: [],
        cuposDisponibles: [],
        medicos: @json($medicos),
        mensajeDisponibilidad: 'Selecciona especialidad o médico y la fecha para ver los horarios disponibles y ocupados del día.',

        get bloquesManana() {
            return this.bloquesHorario.filter(b => parseInt(b.hora.split(':')[0], 10) < 13);
        },

        get bloquesTarde() {
            return this.bloquesHorario.filter(b => parseInt(b.hora.split(':')[0], 10) >= 13);
        },

        get totalLibres() {
            return this.bloquesHorario.filter(b => b.estado === 'disponible').length;
        },

        get totalOcupados() {
            return this.bloquesHorario.filter(b => b.estado === 'ocupado').length;
        },

        get etiquetaFechaDia() {
            if (!this.fecha) return '';
            const partes = this.fecha.split('-').map(Number);
            if (partes.length !== 3) return this.fecha;
            const d = new Date(partes[0], partes[1] - 1, partes[2]);
            const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
            return `${dias[d.getDay()]} ${partes[2]} ${meses[partes[1] - 1]}`;
        },

        get horaEstaOcupada() {
            if (!this.hora) return false;
            const hCorta = this.hora.substring(0, 5);
            return this.bloquesHorario.some(b => b.hora === hCorta && b.estado === 'ocupado');
        },

        get medicosFiltrados() {
            if (!this.especialidadId) return this.medicos;
            return this.medicos.filter(m => m.especialidades && m.especialidades.some(e => e.id_especialidad == this.especialidadId));
        },

        renderOpcionesMedicos() {
            const select = this.$refs.selectMedico;
            if (!select) return;
            const currentVal = String(this.medicoId || '');
            select.innerHTML = '';

            const defaultOpt = document.createElement('option');
            defaultOpt.value = '';
            defaultOpt.textContent = this.especialidadId
                ? 'Seleccione médico especialista...'
                : 'Todos los médicos (agrupados por especialidad)...';
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
                // Agrupar todos los médicos por su especialidad principal (sin duplicar)
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

        get medicoSeleccionado() {
            if (!this.medicoId) return null;
            return this.medicos.find(m => m.id_medico == this.medicoId);
        },

        get resumenHorariosMedico() {
            const m = this.medicoSeleccionado;
            if (!m || !m.horarios_atencion || m.horarios_atencion.length === 0) return 'Horarios habituales: Lunes a Viernes (08:00 - 18:00)';
            const diasNombres = { 1: 'Lunes', 2: 'Martes', 3: 'Miércoles', 4: 'Jueves', 5: 'Viernes', 6: 'Sábado', 7: 'Domingo' };
            const diasUnicos = [...new Set(m.horarios_atencion.map(h => diasNombres[h.dia_semana] || h.dia_semana))];
            const h0 = m.horarios_atencion[0];
            const rango = `${h0.hora_inicio.substring(0, 5)} a ${h0.hora_fin.substring(0, 5)}`;
            return `${diasUnicos.join(', ')} de ${rango} (Bloques de ${h0.duracion_cita_minutos || 30} min)`;
        },

        onEspecialidadChange() {
            const filtrados = this.medicosFiltrados;
            if (filtrados.length === 1) {
                this.medicoId = String(filtrados[0].id_medico);
            } else if (!filtrados.some(m => m.id_medico == this.medicoId)) {
                this.medicoId = '';
            }
            this.renderOpcionesMedicos();
            this.consultarCupos();
        },

        onMedicoChange() {
            if (!this.especialidadId && this.medicoId) {
                const m = this.medicoSeleccionado;
                if (m && m.especialidades && m.especialidades.length > 0) {
                    this.especialidadId = String(m.especialidades[0].id_especialidad);
                    this.renderOpcionesMedicos();
                }
            }
            this.consultarCupos();
        },

        onFechaChange() {
            this.consultarCupos();
        },

        async consultarCupos() {
            if (!this.especialidadId || !this.fecha) {
                this.bloquesHorario = [];
                this.cuposDisponibles = [];
                this.mensajeDisponibilidad = 'Selecciona especialidad o médico y la fecha para ver los horarios disponibles y ocupados del día.';
                return;
            }

            this.cargandoCupos = true;
            this.mensajeDisponibilidad = '';
            this.bloquesHorario = [];
            this.cuposDisponibles = [];

            try {
                let url = `/api/disponibilidad?id_especialidad=${this.especialidadId}&fecha=${this.fecha}`;
                if (this.medicoId) {
                    url += `&id_medico=${this.medicoId}`;
                }

                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();

                if (json.success && Array.isArray(json.data) && json.data.length > 0) {
                    let bloques = [];

                    if (this.medicoId) {
                        const found = json.data.find(d => d.medico && d.medico.id_medico == this.medicoId);
                        if (found) {
                            const origen = Array.isArray(found.todos_bloques) && found.todos_bloques.length > 0
                                ? found.todos_bloques
                                : (found.cupos || []).map(c => ({
                                    hora_inicio: typeof c === 'string' ? c : c.hora_inicio,
                                    hora_fin: typeof c === 'object' ? (c.hora_fin || '') : '',
                                    estado: 'disponible',
                                    disponible: true,
                                    ocupado: false,
                                }));
                            bloques = origen.map(b => ({
                                hora: String(b.hora_inicio || '').substring(0, 5),
                                hora_fin: String(b.hora_fin || '').substring(0, 5),
                                estado: b.estado || (b.ocupado ? 'ocupado' : 'disponible'),
                                disponible: b.estado ? b.estado === 'disponible' : !b.ocupado,
                            }));
                        }
                    } else {
                        const mapa = {};
                        json.data.forEach(d => {
                            const origen = Array.isArray(d.todos_bloques) && d.todos_bloques.length > 0
                                ? d.todos_bloques
                                : (d.cupos || []).map(c => ({
                                    hora_inicio: typeof c === 'string' ? c : c.hora_inicio,
                                    hora_fin: typeof c === 'object' ? (c.hora_fin || '') : '',
                                    estado: 'disponible',
                                }));
                            origen.forEach(b => {
                                const h = String(b.hora_inicio || '').substring(0, 5);
                                const est = b.estado || (b.ocupado ? 'ocupado' : 'disponible');
                                if (!mapa[h]) {
                                    mapa[h] = { hora: h, hora_fin: String(b.hora_fin || '').substring(0, 5), estado: est, disponible: est === 'disponible' };
                                } else if (est === 'disponible') {
                                    mapa[h].estado = 'disponible';
                                    mapa[h].disponible = true;
                                } else if (est === 'ocupado' && mapa[h].estado === 'pasado') {
                                    mapa[h].estado = 'ocupado';
                                }
                            });
                        });
                        bloques = Object.values(mapa).sort((a, b) => a.hora.localeCompare(b.hora));
                    }

                    this.bloquesHorario = bloques;
                    this.cuposDisponibles = bloques.filter(b => b.estado === 'disponible').map(b => b.hora);

                    if (this.bloquesHorario.length === 0) {
                        this.mensajeDisponibilidad = 'El médico no tiene turnos configurados para este día de la semana.';
                        this.hora = '';
                    } else {
                        if (this.cuposDisponibles.length === 0) {
                            this.mensajeDisponibilidad = 'Todos los horarios de este día están ocupados o ya pasaron.';
                        }
                        if (this.hora && !this.cuposDisponibles.includes(this.hora.substring(0, 5))) {
                            this.hora = '';
                        }
                    }
                } else {
                    this.hora = '';
                    this.mensajeDisponibilidad = json.message || 'El médico no atiende en el día de la semana seleccionado.';
                }
            } catch (e) {
                this.mensajeDisponibilidad = 'Consulta de horarios no disponible temporalmente.';
            } finally {
                this.cargandoCupos = false;
            }
        },

        seleccionarCupo(bloque) {
            if (!bloque || !bloque.disponible) return;
            this.hora = bloque.hora;
        },

        init() {
            this.$nextTick(() => {
                this.renderOpcionesMedicos();
                if (this.especialidadId && this.fecha) {
                    this.consultarCupos();
                }
            });
        }
    }));
});
</script>

<div class="container py-12" x-data="reservaCitas">

    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">PROGRAMACIÓN DE CITAS</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Programación de Citas Médicas</h1>
            <p class="text-sm text-slate-500 mt-1">Reserva clínica interactiva con verificación automática de turnos, horarios y cupos disponibles.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-sky-50 text-[#0284C7] font-bold text-xs border border-sky-200/80">
                Turnos en Tiempo Real
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Formulario Dinámico de Reserva -->
        <div class="lg:col-span-5 bg-white p-6 rounded-xl border border-slate-200 shadow-sm h-fit">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-900">Nueva Cita Médica</h2>
                <span class="text-[11px] text-slate-400 font-medium">Agenda Clínica</span>
            </div>
            
            <form action="{{ url('/citas') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Paciente -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Paciente *</label>
                    <select name="id_paciente" x-model="pacienteId" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" required>
                        <option value="">Seleccione paciente...</option>
                        @foreach($pacientes ?? [] as $p)
                            <option value="{{ $p->id_paciente }}">{{ $p->nombre_completo }} (CI: {{ $p->ci }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Asistente Inteligente de Triage por Síntomas -->
                <div class="p-3 rounded-xl bg-gradient-to-r from-sky-50 to-emerald-50 border border-sky-200/80 flex items-center justify-between gap-3">
                    <div class="text-xs">
                        <p class="font-bold text-[#0F172A]">¿No sabes qué especialista elegir?</p>
                        <p class="text-[11px] text-slate-600">Describe tus síntomas y nuestra IA te recomendará al médico ideal.</p>
                    </div>
                    <a href="{{ route('triage.index') }}"
                       class="px-3 py-1.5 rounded-lg bg-[#0284C7] hover:bg-[#0369A1] text-white text-[11px] font-bold shrink-0 transition-colors">
                        Triage IA →
                    </a>
                </div>

                <!-- Especialidad Médica -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Especialidad Médica *</label>
                    <select name="id_especialidad" x-model="especialidadId" @change="onEspecialidadChange()"
                            class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" required>
                        <option value="">Seleccione especialidad...</option>
                        @foreach($especialidades ?? [] as $e)
                            <option value="{{ $e->id_especialidad }}">{{ $e->nombre_especialidad }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Médico Especialista (Filtrado automáticamente por especialidad) -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Médico Especialista *</label>
                        <span class="text-[11px] text-slate-400 font-medium" x-show="especialidadId">
                            <span x-text="medicosFiltrados.length"></span> médicos disponibles
                        </span>
                    </div>
                    <select name="id_medico" x-ref="selectMedico" x-model="medicoId" @change="onMedicoChange()"
                            class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" required>
                        <option value="">Cargando médicos...</option>
                    </select>

                    <!-- Resumen del Horario del Médico Seleccionado -->
                    <template x-if="medicoSeleccionado">
                        <div class="mt-2 p-2.5 rounded-lg bg-sky-50/70 border border-sky-100 text-xs">
                            <div class="flex items-center gap-1.5 font-bold text-sky-800 mb-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0 text-[#0284C7]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <span>Turnos de Atención Médica:</span>
                            </div>
                            <p class="text-slate-600 text-[11px]" x-text="resumenHorariosMedico"></p>
                        </div>
                    </template>
                </div>

                <!-- Fecha y Hora de la Cita -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Fecha de la Cita *</label>
                        <input type="date" name="fecha_cita" x-model="fecha" @change="onFechaChange()"
                               class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none transition-colors"
                               required min="{{ date('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Hora Seleccionada *</label>
                        <input type="time" name="hora_cita" x-model="hora" step="1800"
                               :class="horaEstaOcupada
                                   ? 'border-rose-400 bg-rose-50 text-rose-700'
                                   : (hora ? 'border-emerald-400 bg-emerald-50/70 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500')"
                               class="w-full text-sm rounded-xl p-2.5 border focus:bg-white focus:border-emerald-500 focus:outline-none font-bold transition-colors" required>
                    </div>
                </div>

                <!-- Selector Visual de Horarios (Disponibles sin color, Seleccionado verde, Ocupados rojo) -->
                <div class="p-4 rounded-2xl bg-slate-50/90 border border-slate-200/90 space-y-3 shadow-inner">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider block">Horarios del Día</span>
                            <span class="text-[11px] text-slate-500 font-medium" x-show="fecha" x-text="etiquetaFechaDia"></span>
                        </div>

                        <template x-if="cargandoCupos">
                            <span class="text-[11px] text-[#0284C7] flex items-center gap-1.5 font-semibold bg-sky-50 px-2.5 py-1 rounded-full border border-sky-200">
                                <span class="w-2 h-2 rounded-full bg-[#0284C7] animate-ping"></span>
                                <span>Consultando agenda...</span>
                            </span>
                        </template>

                        <template x-if="!cargandoCupos && bloquesHorario.length > 0">
                            <div class="flex items-center gap-1.5 text-[11px] font-bold">
                                <span class="px-2 py-0.5 rounded-full bg-white text-slate-700 border border-slate-200">
                                    <span x-text="totalLibres"></span> libres
                                </span>
                                <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200" x-show="totalOcupados > 0">
                                    <span x-text="totalOcupados"></span> ocupados
                                </span>
                            </div>
                        </template>
                    </div>

                    <!-- Leyenda de colores -->
                    <div class="flex flex-wrap items-center gap-3 pt-1 pb-2 border-b border-slate-200/70 text-[11px] text-slate-600">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded bg-white border border-slate-300 shadow-xs"></span>
                            <span>Disponible</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded bg-emerald-600 border border-emerald-600"></span>
                            <span class="font-semibold text-emerald-700">Seleccionado</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded bg-rose-100 border border-rose-400"></span>
                            <span class="font-semibold text-rose-600">Ocupado</span>
                        </span>
                    </div>

                    <!-- Aviso si el usuario escribió manualmente una hora ocupada -->
                    <template x-if="horaEstaOcupada">
                        <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
                            Ese horario ya está ocupado para este médico. Selecciona un bloque disponible.
                        </div>
                    </template>

                    <!-- Mensaje informativo si no hay bloques o todos están ocupados -->
                    <template x-if="mensajeDisponibilidad && !cargandoCupos">
                        <div class="p-3 rounded-xl text-xs border"
                             :class="bloquesHorario.length > 0 ? 'bg-rose-50/70 border-rose-200 text-rose-700' : 'bg-white border-slate-200 text-slate-500'">
                            <p x-text="mensajeDisponibilidad"></p>
                        </div>
                    </template>

                    <!-- Grilla de Bloques Horarios (Mañana y Tarde) -->
                    <template x-if="bloquesHorario.length > 0 && !cargandoCupos">
                        <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                            <!-- Turno Mañana -->
                            <template x-if="bloquesManana.length > 0">
                                <div class="space-y-1.5">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                                        <span>Turno Mañana</span>
                                        <span class="flex-1 h-px bg-slate-200/80"></span>
                                    </p>
                                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                        <template x-for="b in bloquesManana" :key="'m-' + b.hora">
                                            <button type="button"
                                                    @click="seleccionarCupo(b)"
                                                    :disabled="!b.disponible"
                                                    :title="b.estado === 'ocupado' ? 'Horario ocupado' : (b.estado === 'pasado' ? 'Horario ya pasado' : 'Seleccionar ' + b.hora)"
                                                    :class="b.estado === 'ocupado'
                                                        ? 'bg-rose-50 text-rose-600 border-rose-300 cursor-not-allowed'
                                                        : (b.estado === 'pasado'
                                                            ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed line-through'
                                                            : (hora && hora.substring(0, 5) === b.hora
                                                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/25 ring-2 ring-emerald-200 scale-[1.02]'
                                                                : 'bg-white text-slate-700 border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/40 hover:text-emerald-800 cursor-pointer'))"
                                                    class="py-2 px-1.5 rounded-xl border text-center transition-all flex flex-col items-center justify-center leading-tight">
                                                <span class="font-mono text-xs font-bold flex items-center gap-1">
                                                    <span x-show="b.estado === 'ocupado'" class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                    <span x-show="b.disponible && hora && hora.substring(0, 5) === b.hora">✓</span>
                                                    <span x-text="b.hora"></span>
                                                </span>
                                                <span class="text-[9px] font-semibold mt-0.5 uppercase tracking-wide"
                                                      :class="b.estado === 'ocupado' ? 'text-rose-500' : (b.disponible && hora && hora.substring(0, 5) === b.hora ? 'text-emerald-100' : 'text-slate-400')"
                                                      x-text="b.estado === 'ocupado' ? 'Ocupado' : (b.estado === 'pasado' ? 'Pasado' : (hora && hora.substring(0, 5) === b.hora ? 'Elegido' : 'Libre'))"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Turno Tarde -->
                            <template x-if="bloquesTarde.length > 0">
                                <div class="space-y-1.5">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                                        <span>Turno Tarde</span>
                                        <span class="flex-1 h-px bg-slate-200/80"></span>
                                    </p>
                                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                        <template x-for="b in bloquesTarde" :key="'t-' + b.hora">
                                            <button type="button"
                                                    @click="seleccionarCupo(b)"
                                                    :disabled="!b.disponible"
                                                    :title="b.estado === 'ocupado' ? 'Horario ocupado' : (b.estado === 'pasado' ? 'Horario ya pasado' : 'Seleccionar ' + b.hora)"
                                                    :class="b.estado === 'ocupado'
                                                        ? 'bg-rose-50 text-rose-600 border-rose-300 cursor-not-allowed'
                                                        : (b.estado === 'pasado'
                                                            ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed line-through'
                                                            : (hora && hora.substring(0, 5) === b.hora
                                                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/25 ring-2 ring-emerald-200 scale-[1.02]'
                                                                : 'bg-white text-slate-700 border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/40 hover:text-emerald-800 cursor-pointer'))"
                                                    class="py-2 px-1.5 rounded-xl border text-center transition-all flex flex-col items-center justify-center leading-tight">
                                                <span class="font-mono text-xs font-bold flex items-center gap-1">
                                                    <span x-show="b.estado === 'ocupado'" class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                    <span x-show="b.disponible && hora && hora.substring(0, 5) === b.hora">✓</span>
                                                    <span x-text="b.hora"></span>
                                                </span>
                                                <span class="text-[9px] font-semibold mt-0.5 uppercase tracking-wide"
                                                      :class="b.estado === 'ocupado' ? 'text-rose-500' : (b.disponible && hora && hora.substring(0, 5) === b.hora ? 'text-emerald-100' : 'text-slate-400')"
                                                      x-text="b.estado === 'ocupado' ? 'Ocupado' : (b.estado === 'pasado' ? 'Pasado' : (hora && hora.substring(0, 5) === b.hora ? 'Elegido' : 'Libre'))"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Resumen de selección verde -->
                    <template x-if="hora && !horaEstaOcupada && bloquesHorario.length > 0">
                        <div class="pt-2 border-t border-slate-200/70 flex items-center justify-between text-xs">
                            <span class="text-slate-500">Turno elegido:</span>
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-mono font-bold shadow-xs">
                                ✓ <span x-text="hora.substring(0, 5)"></span> hrs
                            </span>
                        </div>
                    </template>
                </div>

                <!-- Motivo de Consulta -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Motivo Clínico (Opcional)</label>
                    <textarea name="motivo" rows="2" class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" placeholder="Síntomas o control preventivo...">{{ old('motivo', request('motivo', '')) }}</textarea>
                </div>

                <button type="submit"
                        :disabled="horaEstaOcupada"
                        :class="horaEstaOcupada ? 'opacity-50 cursor-not-allowed' : ''"
                        class="btn-primary w-full justify-center shadow-md shadow-[#0284C7]/20 py-3 text-sm">
                    Confirmar Reserva de Cita
                </button>
            </form>
        </div>

        <!-- Listado de Citas Programadas Recientes -->
        <div class="lg:col-span-7 bg-white p-6 rounded-xl border border-slate-200 shadow-sm h-fit">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-900">Citas Programadas</h2>
                <span class="text-xs text-slate-500 font-semibold">Últimas 20 citas</span>
            </div>
            
            <div class="space-y-3">
                @forelse($citas ?? [] as $cita)
                    <div class="p-4 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-white hover:border-slate-200 hover:shadow-sm transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    {{ $cita->estado?->nombre_estado ?? $cita->estadoCita?->nombre_estado ?? 'Programada' }}
                                </span>
                                @if ($cita->fecha_creacion && \Illuminate\Support\Carbon::parse($cita->fecha_creacion)->greaterThanOrEqualTo(now()->subMinutes(15)))
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-sky-100 text-[#0284C7] border border-sky-200">
                                        Recién agregada
                                    </span>
                                @endif
                                <span class="text-xs font-bold text-slate-700">
                                    {{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }} a las {{ substr($cita->hora_cita, 0, 5) }}
                                </span>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">{{ $cita->paciente?->nombre_completo ?? 'Paciente' }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Dr(a). {{ $cita->medico?->nombre_completo }} · <span class="text-[#0284C7] font-semibold">{{ $cita->especialidad?->nombre_especialidad }}</span>
                            </p>
                            @if (!empty($cita->motivo))
                                <p class="text-[11px] text-slate-600 mt-1 italic line-clamp-1">"{{ $cita->motivo }}"</p>
                            @endif
                        </div>
                        <div class="text-right shrink-0 flex flex-col items-end gap-2">
                            <span class="font-mono text-[11px] text-sky-700 bg-sky-50 px-2.5 py-1 rounded-lg border border-sky-100 block">
                                {{ substr($cita->codigo_unico, 0, 8) }}...
                            </span>
                            @if(in_array((int)$cita->id_estado_cita, [1, 2]))
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ url('/citas/'.$cita->id_cita.'/reprogramar') }}"
                                       class="px-2 py-1 rounded-lg border border-sky-200 bg-white hover:bg-[#0284C7] hover:text-white text-[#0284C7] text-[11px] font-semibold transition-colors">
                                        Reprogramar
                                    </a>
                                    <a href="{{ url('/citas/'.$cita->id_cita.'/cancelar') }}"
                                       class="px-2 py-1 rounded-lg border border-rose-200 bg-white hover:bg-rose-600 hover:text-white text-rose-700 text-[11px] font-semibold transition-colors">
                                        Cancelar
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 py-12 text-center">No hay citas registradas recientemente.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
