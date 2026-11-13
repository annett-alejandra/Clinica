@extends('layouts.app')

@section('title', 'Programación y Reserva de Citas — Clínica Vida Salud')

@section('content')
<div class="container py-12"
     x-data="{
        pacienteId: '{{ old('id_paciente', '') }}',
        especialidadId: '{{ old('id_especialidad', '') }}',
        medicoId: '{{ old('id_medico', '') }}',
        fecha: '{{ old('fecha_cita', date('Y-m-d')) }}',
        hora: '{{ old('hora_cita', '08:30') }}',
        cargandoCupos: false,
        cuposDisponibles: [],
        medicos: @json($medicos),
        mensajeDisponibilidad: '',

        get medicosFiltrados() {
            if (!this.especialidadId) return this.medicos;
            return this.medicos.filter(m => m.especialidades && m.especialidades.some(e => e.id_especialidad == this.especialidadId));
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
                this.medicoId = filtrados[0].id_medico;
            } else if (!filtrados.some(m => m.id_medico == this.medicoId)) {
                this.medicoId = '';
            }
            this.consultarCupos();
        },

        onMedicoChange() {
            this.consultarCupos();
        },

        onFechaChange() {
            this.consultarCupos();
        },

        async consultarCupos() {
            if (!this.especialidadId || !this.fecha) {
                this.cuposDisponibles = [];
                this.mensajeDisponibilidad = 'Selecciona especialidad y fecha para consultar horarios disponibles.';
                return;
            }

            this.cargandoCupos = true;
            this.mensajeDisponibilidad = '';
            this.cuposDisponibles = [];

            try {
                let url = `{{ url('/api/disponibilidad') }}?id_especialidad=${this.especialidadId}&fecha=${this.fecha}`;
                if (this.medicoId) {
                    url += `&id_medico=${this.medicoId}`;
                }

                const res = await fetch(url);
                const json = await res.json();

                if (json.success && Array.isArray(json.data) && json.data.length > 0) {
                    let lista = [];
                    if (this.medicoId) {
                        const found = json.data.find(d => d.medico && d.medico.id_medico == this.medicoId);
                        if (found && found.cupos) {
                            lista = found.cupos.map(c => typeof c === 'string' ? c : c.hora_inicio);
                        }
                    } else {
                        const set = new Set();
                        json.data.forEach(d => {
                            if (d.cupos) {
                                d.cupos.forEach(c => set.add(typeof c === 'string' ? c : c.hora_inicio));
                            }
                        });
                        lista = Array.from(set).sort();
                    }

                    this.cuposDisponibles = lista.map(h => h.substring(0, 5));
                    if (this.cuposDisponibles.length === 0) {
                        this.mensajeDisponibilidad = 'No hay cupos disponibles en esta fecha. El médico no atiende hoy o todos los turnos están ocupados.';
                    } else {
                        if (!this.hora || !this.cuposDisponibles.includes(this.hora)) {
                            this.hora = this.cuposDisponibles[0];
                        }
                    }
                } else {
                    this.mensajeDisponibilidad = 'El médico no atiende en el día de la semana seleccionado o no hay turnos libres.';
                }
            } catch (e) {
                this.mensajeDisponibilidad = 'Consulta de cupos no disponible temporalmente.';
            } finally {
                this.cargandoCupos = false;
            }
        },

        seleccionarCupo(h) {
            this.hora = h;
        },

        init() {
            if (this.especialidadId && this.fecha) {
                this.consultarCupos();
            }
        }
     }">

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
                    <select name="id_medico" x-model="medicoId" @change="onMedicoChange()"
                            class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" required>
                        <option value="">Seleccione médico...</option>
                        <template x-for="m in medicosFiltrados" :key="m.id_medico">
                            <option :value="m.id_medico" x-text="m.nombre_completo + ' (' + (m.registro_profesional || 'Médico') + ')'"></option>
                        </template>
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
                               class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none"
                               required min="{{ date('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Hora Seleccionada *</label>
                        <input type="time" name="hora_cita" x-model="hora" step="1800"
                               class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none font-bold text-[#0284C7]" required>
                    </div>
                </div>

                <!-- Selector Dinámico de Horarios y Cupos Disponibles -->
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Horarios Disponibles</span>
                        <template x-if="cargandoCupos">
                            <span class="text-[11px] text-[#0284C7] flex items-center gap-1 font-semibold">
                                <span class="w-2 h-2 rounded-full bg-[#0284C7] animate-ping"></span>
                                <span>Consultando cupos...</span>
                            </span>
                        </template>
                    </div>

                    <!-- Mensaje si no hay cupos o faltan datos -->
                    <template x-if="mensajeDisponibilidad && !cargandoCupos">
                        <div class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-xs">
                            <p x-text="mensajeDisponibilidad"></p>
                        </div>
                    </template>

                    <!-- Lista de Cupos Clicables -->
                    <template x-if="cuposDisponibles.length > 0 && !cargandoCupos">
                        <div>
                            <p class="text-[11px] text-slate-500 mb-2">Haz clic en un turno para seleccionarlo automáticamente:</p>
                            <div class="grid grid-cols-4 gap-1.5 max-h-48 overflow-y-auto p-1">
                                <template x-for="cupo in cuposDisponibles" :key="cupo">
                                    <button type="button"
                                            @click="seleccionarCupo(cupo)"
                                            :class="hora === cupo
                                                ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-sm font-bold scale-[1.02]'
                                                : 'bg-white text-slate-700 border-slate-200 hover:border-[#0284C7] hover:bg-sky-50 font-medium'"
                                            class="py-1.5 px-2 rounded-lg text-xs border text-center transition-all cursor-pointer">
                                        <span x-text="cupo"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Motivo de Consulta -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Motivo Clínico (Opcional)</label>
                    <textarea name="motivo" rows="2" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" placeholder="Síntomas o control preventivo..."></textarea>
                </div>

                <button type="submit" class="btn-primary w-full justify-center shadow-md shadow-[#0284C7]/20 py-3 text-sm">
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
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    {{ $cita->estado?->nombre_estado ?? $cita->estadoCita?->nombre_estado ?? 'Programada' }}
                                </span>
                                <span class="text-xs font-bold text-slate-700">
                                    {{ \Carbon\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }} a las {{ substr($cita->hora_cita, 0, 5) }}
                                </span>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">{{ $cita->paciente?->nombre_completo ?? 'Paciente' }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Dr(a). {{ $cita->medico?->nombre_completo }} · <span class="text-[#0284C7] font-semibold">{{ $cita->especialidad?->nombre_especialidad }}</span>
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="font-mono text-[11px] text-sky-700 bg-sky-50 px-2.5 py-1 rounded-lg border border-sky-100 block">
                                {{ substr($cita->codigo_unico, 0, 8) }}...
                            </span>
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
