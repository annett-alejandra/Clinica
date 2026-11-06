@extends('layouts.app')

@section('title', 'Portal Clínico Integrado — Clínica Vida Salud')

@section('content')
@php
    $vista = $panel['vista'] ?? 'medico';
    $pacientesMonitoreo = $panel['pacientes_monitoreo'] ?? [];
@endphp

<div class="pt-6 pb-20 px-4 sm:px-6 md:px-12 max-w-7xl mx-auto"
     x-data="{
         patientFilter: 'all',
         searchTerm: '',
         pacientes: @js($pacientesMonitoreo),
         fichaPaciente: null,
         citaAtender: null,
         get filteredPatients() {
             return this.pacientes.filter((p) => {
                 const matchesStatus = this.patientFilter === 'all' || p.status === this.patientFilter;
                 const q = this.searchTerm.trim().toLowerCase();
                 const matchesSearch = !q ||
                     p.name.toLowerCase().includes(q) ||
                     p.id.toLowerCase().includes(q) ||
                     p.diagnosis.toLowerCase().includes(q) ||
                     p.room.toLowerCase().includes(q);
                 return matchesStatus && matchesSearch;
             });
         },
         badgeClass(type) {
             const styles = {
                 stable: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                 critical: 'bg-red-50 text-red-700 border-red-200',
                 caution: 'bg-amber-50 text-amber-700 border-amber-200',
                 info: 'bg-sky-50 text-sky-700 border-sky-200'
             };
             return styles[type] || styles.info;
         }
     }">

    <!-- Portal Subheader & Role Switcher Pill -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-8 border-b border-slate-200">
        <div>
            <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-[#0284C7] mb-1">
                <span>SISTEMA CLÍNICO INTEGRADO v4.2</span>
                <span>•</span>
                <span>{{ strtoupper($panel['rol_etiqueta'] ?? 'CONSOLA OPERATIVA') }}</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl md:text-4xl text-[#0F172A]">
                @if ($vista === 'medico')
                    Consola Médica y Seguimiento de Pacientes
                @elseif ($vista === 'recepcion')
                    Estación de Enfermería, Recepción y Gestión de Camas
                @elseif ($vista === 'paciente')
                    Portal Personal del Paciente y Familia
                @else
                    Dirección Médica, Seguridad y Reportes Ejecutivos
                @endif
            </h1>
        </div>

        <!-- Quick Role Switcher Segmented Pill -->
        <div class="inline-flex flex-wrap bg-slate-200/80 p-1 rounded-xl border border-slate-300/60 self-start">
            <a href="{{ route('portal', ['rol' => 'medico']) }}"
               class="px-3.5 py-2 rounded-lg text-xs font-semibold transition-all {{ $vista === 'medico' ? 'bg-white text-[#0284C7] shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                Médico (CU15/16)
            </a>
            <a href="{{ route('portal', ['rol' => 'enfermeria']) }}"
               class="px-3.5 py-2 rounded-lg text-xs font-semibold transition-all {{ $vista === 'recepcion' ? 'bg-white text-[#0284C7] shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                Enfermería / Recepción (CU14)
            </a>
            <a href="{{ route('portal', ['rol' => 'paciente']) }}"
               class="px-3.5 py-2 rounded-lg text-xs font-semibold transition-all {{ $vista === 'paciente' ? 'bg-white text-[#0284C7] shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                Paciente (CU08/18)
            </a>
            <a href="{{ route('portal', ['rol' => 'administrador']) }}"
               class="px-3.5 py-2 rounded-lg text-xs font-semibold transition-all {{ $vista === 'administrador' ? 'bg-white text-[#0284C7] shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                Admin (CU03–19)
            </a>
        </div>
    </div>

    {{-- ====================================================================== --}}
    {{-- ROLE 1: MÉDICO ESPECIALISTA (Clinical Patient List, Vitals & CU15/CU16) --}}
    {{-- ====================================================================== --}}
    @if ($vista === 'medico')
        <div class="mt-8 space-y-8">
            <!-- Top Metric Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-xs font-semibold text-slate-500">Pacientes / Citas Asignadas</span>
                        <span class="p-2 rounded-lg bg-sky-50 text-[#0284C7]">
                            <x-icon name="users" class="w-4 h-4" />
                        </span>
                    </div>
                    <span class="text-2xl font-bold text-[#0F172A] block">{{ count($pacientesMonitoreo) }}</span>
                    <span class="text-[11px] text-emerald-600 font-medium">
                        {{ $panel['metricas']['hoy'] ?? 0 }} citas hoy • {{ $panel['metricas']['por_atender'] ?? 0 }} por atender
                    </span>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-xs font-semibold text-slate-500">Pabellones Quirúrgicos</span>
                        <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                            <x-icon name="activity" class="w-4 h-4" />
                        </span>
                    </div>
                    <span class="text-2xl font-bold text-[#0F172A] block">10 / 12 Activos</span>
                    <span class="text-[11px] text-slate-500 font-medium">Pabellón 4 reservado 14:30 hrs</span>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-xs font-semibold text-slate-500">Alertas Críticas</span>
                        <span class="p-2 rounded-lg bg-red-50 text-red-600">
                            <x-icon name="alert-circle" class="w-4 h-4" />
                        </span>
                    </div>
                    <span class="text-2xl font-bold text-red-600 block">02</span>
                    <span class="text-[11px] text-red-600 font-medium">Requieren evaluación inmediata</span>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-xs font-semibold text-slate-500">Atenciones del Mes (CU15)</span>
                        <span class="p-2 rounded-lg bg-amber-50 text-amber-600">
                            <x-icon name="clock" class="w-4 h-4" />
                        </span>
                    </div>
                    <span class="text-2xl font-bold text-[#0F172A] block">{{ max(4, $panel['metricas']['atendidas_mes'] ?? 4) }}</span>
                    <span class="text-[11px] text-slate-500 font-medium">Cardiología y Medicina Interna</span>
                </div>
            </div>

            <!--Accesos Rápidos a Casos de Uso del Médico -->
            <div class="flex flex-wrap items-center justify-between gap-3 bg-sky-50/70 border border-sky-200/70 rounded-2xl px-5 py-3.5">
                <div class="flex items-center space-x-2.5 text-xs font-semibold text-slate-700">
                    <x-icon name="stethoscope" class="w-4 h-4 text-[#0284C7]" />
                    <span>
                        Especialista activo:
                        <strong class="text-[#0F172A]">
                            {{ $panel['medico'] ? 'Dr(a). ' . $panel['medico']->nombres . ' ' . $panel['medico']->apellidos : 'Dr. Matías Lagos' }}
                        </strong>
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ url('/atencion-medica') }}" class="px-3.5 py-1.5 rounded-lg bg-[#0284C7] text-white text-xs font-semibold hover:bg-[#0369A1] transition-colors">
                        CU15 Registrar Cita Atendida
                    </a>
                    <a href="{{ url('/agenda-medica') }}" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:border-[#0284C7] transition-colors">
                        CU16 Consultar Agenda Médica
                    </a>
                    <a href="{{ url('/horarios') }}" class="px-3.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-semibold hover:border-[#0284C7] transition-colors">
                        CU06 Mis Horarios
                    </a>
                </div>
            </div>

            <!-- Interactive Patient List with Search & Status Filter -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-lg text-[#0F172A]">Monitoreo Clínico de Pacientes y Consultas</h3>
                        <p class="text-xs text-slate-500">Signos vitales en tiempo real, estado de triage y registro de atención médica (CU15)</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <!-- Search Input -->
                        <div class="relative">
                            <x-icon name="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input type="text"
                                   placeholder="Buscar paciente, cama o ID..."
                                   x-model="searchTerm"
                                   class="pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:border-[#0284C7] w-full sm:w-64" />
                        </div>

                        <!-- Filter Buttons -->
                        <div class="flex items-center space-x-1 bg-slate-100 p-1 rounded-xl border border-slate-200">
                            <button type="button"
                                    @click="patientFilter = 'all'"
                                    :class="patientFilter === 'all' ? 'bg-white text-[#0284C7] shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-all">
                                Todos
                            </button>
                            <button type="button"
                                    @click="patientFilter = 'critical'"
                                    :class="patientFilter === 'critical' ? 'bg-white text-[#0284C7] shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-all">
                                Críticos
                            </button>
                            <button type="button"
                                    @click="patientFilter = 'caution'"
                                    :class="patientFilter === 'caution' ? 'bg-white text-[#0284C7] shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-all">
                                Observación
                            </button>
                            <button type="button"
                                    @click="patientFilter = 'stable'"
                                    :class="patientFilter === 'stable' ? 'bg-white text-[#0284C7] shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-all">
                                Estables
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200">
                                <th class="py-3.5 px-6">ID / Paciente</th>
                                <th class="py-3.5 px-6">Ubicación</th>
                                <th class="py-3.5 px-6">Diagnóstico Clínico / Motivo</th>
                                <th class="py-3.5 px-6">Signos Vitales</th>
                                <th class="py-3.5 px-6">Estado / Triage</th>
                                <th class="py-3.5 px-6 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70 text-sm">
                            <template x-for="p in filteredPatients" :key="p.id">
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-4 px-6">
                                        <span class="text-[11px] font-bold text-[#0284C7] block" x-text="p.id"></span>
                                        <span class="font-semibold text-[#0F172A]" x-text="p.name"></span>
                                        <span class="text-xs text-slate-400 ml-2" x-text="`(${p.age} años)`"></span>
                                    </td>
                                    <td class="py-4 px-6 font-medium text-slate-700" x-text="p.room"></td>
                                    <td class="py-4 px-6 text-slate-600">
                                        <span x-text="p.diagnosis"></span>
                                        <span class="block text-[11px] text-slate-400" x-text="p.doctor"></span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="text-xs space-y-0.5">
                                            <span class="block text-slate-700 font-medium">
                                                PA: <strong x-text="p.bp"></strong>
                                            </span>
                                            <span class="block text-slate-500">
                                                FC: <span x-text="p.hr"></span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold border"
                                              :class="badgeClass(p.status)"
                                              x-text="p.triage">
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <template x-if="p.id_cita && (p.id_estado_cita == 1 || p.id_estado_cita == 2)">
                                            <button type="button"
                                                    @click="citaAtender = p"
                                                    class="text-xs font-semibold text-white bg-[#0284C7] hover:bg-[#0369A1] px-3 py-1.5 rounded-lg transition-colors">
                                                Atender Cita (CU15) →
                                            </button>
                                        </template>
                                        <template x-if="!p.id_cita || (p.id_estado_cita != 1 && p.id_estado_cita != 2)">
                                            <button type="button"
                                                    @click="fichaPaciente = p"
                                                    class="text-xs font-semibold text-[#0284C7] hover:text-[#0369A1] bg-sky-50 hover:bg-sky-100 px-3 py-1.5 rounded-lg transition-colors">
                                                Ver Ficha →
                                            </button>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredPatients.length === 0">
                                <td colspan="6" class="py-10 text-center text-slate-400 text-sm">
                                    No se encontraron pacientes con los criterios seleccionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Ficha Clínica del Paciente -->
        <div x-show="fichaPaciente !== null"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
            <div class="bg-white border border-slate-200 max-w-lg w-full rounded-2xl p-6 md:p-8 relative shadow-2xl"
                 @click.outside="fichaPaciente = null">
                <button type="button"
                        @click="fichaPaciente = null"
                        class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 p-2 rounded-full hover:bg-slate-100">
                    <x-icon name="x" class="w-5 h-5" />
                </button>
                <span class="text-xs font-bold uppercase tracking-wider text-[#0284C7] block mb-1"
                      x-text="fichaPaciente?.id + ' • FICHA CLÍNICA ELECTRÓNICA'"></span>
                <h4 class="font-serif text-2xl text-[#0F172A] mb-1" x-text="fichaPaciente?.name"></h4>
                <p class="text-xs text-slate-500 mb-5" x-text="`Ubicación: ${fichaPaciente?.room} • Médico tratante: ${fichaPaciente?.doctor}`"></p>

                <div class="grid grid-cols-2 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200 mb-5 text-xs">
                    <div>
                        <span class="text-slate-400 font-bold uppercase block">Presión Arterial</span>
                        <span class="text-base font-bold text-[#0F172A]" x-text="fichaPaciente?.bp"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-bold uppercase block">Frecuencia Cardíaca</span>
                        <span class="text-base font-bold text-[#0F172A]" x-text="fichaPaciente?.hr"></span>
                    </div>
                    <div class="col-span-2 pt-2 border-t border-slate-200">
                        <span class="text-slate-400 font-bold uppercase block">Diagnóstico / Evolución</span>
                        <span class="text-sm font-semibold text-slate-800" x-text="fichaPaciente?.diagnosis"></span>
                    </div>
                </div>

                <div class="flex justify-end space-x-2.5">
                    <button type="button"
                            @click="fichaPaciente = null"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                        Cerrar
                    </button>
                    <a href="{{ url('/atencion-medica') }}"
                       class="px-4 py-2 rounded-xl bg-[#0284C7] text-white text-xs font-semibold hover:bg-[#0369A1]">
                        Ir a Registro Clínico (CU15)
                    </a>
                </div>
            </div>
        </div>

        <!-- Modal CU15 Registrar Cita Atendida -->
        <div x-show="citaAtender !== null"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
            <div class="bg-white border border-slate-200 max-w-lg w-full rounded-2xl p-6 md:p-8 relative shadow-2xl"
                 @click.outside="citaAtender = null">
                <button type="button"
                        @click="citaAtender = null"
                        class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 p-2 rounded-full hover:bg-slate-100">
                    <x-icon name="x" class="w-5 h-5" />
                </button>
                <span class="text-xs font-bold uppercase tracking-wider text-[#0284C7] block mb-1">CU15 • REGISTRAR ATENCIÓN MÉDICA</span>
                <h4 class="font-serif text-2xl text-[#0F172A] mb-1" x-text="citaAtender?.name"></h4>
                <p class="text-xs text-slate-500 mb-4" x-text="citaAtender?.diagnosis"></p>

                <form method="POST" :action="`/citas/${citaAtender?.id_cita}/atender`" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Diagnóstico Clínico</label>
                        <input type="text" name="diagnostico" required minlength="5"
                               placeholder="Ej. Hipertensión arterial esencial controlada (CIE-10: I10)"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#0284C7]" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tratamiento e Indicaciones</label>
                        <textarea name="tratamiento" rows="2" required
                                  placeholder="Indicar esquema terapéutico y control..."
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#0284C7]"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Observaciones Adicionales</label>
                        <input type="text" name="observaciones"
                               placeholder="Signos vitales estables, control en 15 días."
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-[#0284C7]" />
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="citaAtender = null" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">
                            Cancelar
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white text-xs font-semibold shadow-md">
                            Guardar Atención Médica (CU15)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ====================================================================== --}}
    {{-- ROLE 2: ENFERMERÍA, RECEPCIÓN Y GESTIÓN (Triage, Bed Occupancy & CU14) --}}
    {{-- ====================================================================== --}}
    @if ($vista === 'recepcion')
        <div class="mt-8 space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Triage Status Column -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-base text-[#0F172A]">Flujo de Triage de Urgencia</h3>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200">
                            Tiempo espera: 4 min
                        </span>
                    </div>

                    <div class="space-y-4">
                        <div class="p-4 rounded-xl bg-red-50/70 border border-red-200 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-red-700 block">Nivel 1 - Resucitación Inmediata</span>
                                <span class="text-[11px] text-red-600">Shock Trauma Boxes 01 y 02</span>
                            </div>
                            <span class="text-xl font-bold text-red-700">2 Pacientes</span>
                        </div>

                        <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-amber-800 block">Nivel 2 - Emergencia Mayor</span>
                                <span class="text-[11px] text-amber-700">Boxes de Observación Aguda</span>
                            </div>
                            <span class="text-xl font-bold text-amber-800">{{ max(4, $panel['metricas']['por_confirmar'] ?? 4) }} Pacientes</span>
                        </div>

                        <div class="p-4 rounded-xl bg-sky-50/70 border border-sky-200 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-sky-800 block">Nivel 3 - Urgencia Estándar</span>
                                <span class="text-[11px] text-sky-700">Sala de Procedimientos</span>
                            </div>
                            <span class="text-xl font-bold text-sky-800">{{ max(7, $panel['metricas']['en_sala'] ?? 7) }} Pacientes</span>
                        </div>
                    </div>

                    <button type="button"
                            @click="$dispatch('abrir-reserva')"
                            class="mt-6 w-full py-3 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white text-xs font-semibold transition-all">
                        + Nuevo Ingreso de Triage / Cita (CU11)
                    </button>
                </div>

                <!-- Bed Occupancy & Ward Management -->
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
                        <div>
                            <h3 class="font-bold text-base text-[#0F172A]">Censo de Camas y Disponibilidad</h3>
                            <p class="text-xs text-slate-500">Monitoreo de unidades de hospitalización, aislamiento y limpieza</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ url('/pacientes') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                                CU07 Pacientes
                            </a>
                            <a href="{{ url('/medicos') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                                CU05 Médicos
                            </a>
                            <a href="{{ url('/historial-paciente') }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                                CU17 Historial
                            </a>
                        </div>
                    </div>

                    @php
                        $unidadesCamas = [
                            ['unit' => 'UCI Adultos (Piso 3)', 'used' => 30, 'total' => 32, 'percent' => 94, 'color' => 'bg-red-500'],
                            ['unit' => 'Suites Hospitalarias (Pisos 4-6)', 'used' => 58, 'total' => 64, 'percent' => 90, 'color' => 'bg-[#0284C7]'],
                            ['unit' => 'Pabellones Quirúrgicos', 'used' => 10, 'total' => 12, 'percent' => 83, 'color' => 'bg-emerald-500'],
                            ['unit' => 'Maternidad y LDRP', 'used' => 7, 'total' => 10, 'percent' => 70, 'color' => 'bg-sky-400'],
                        ];
                    @endphp
                    <div class="space-y-5">
                        @foreach ($unidadesCamas as $ward)
                            <div>
                                <div class="flex justify-between text-xs font-semibold mb-1.5">
                                    <span class="text-slate-700">{{ $ward['unit'] }}</span>
                                    <span class="text-slate-500">{{ $ward['used'] }} / {{ $ward['total'] }} ocupadas ({{ $ward['percent'] }}%)</span>
                                </div>
                                <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full {{ $ward['color'] }} rounded-full" style="width: {{ $ward['percent'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- CU14, CU12 & CU13: Control de Asistencia, Llegadas, Reprogramación y Cancelación en Vivo -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#0284C7]">CU12 • CU13 • CU14 — RECEPCIÓN Y ADMISIÓN HOSPITALARIA</span>
                        <h3 class="font-bold text-lg text-[#0F172A]">Control de Confirmación, Llegada a Sala e Inasistencia</h3>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ url('/control-atencion') }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                            Ver Módulo CU14 Completo
                        </a>
                        <a href="{{ url('/citas') }}" class="px-3.5 py-2 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white text-xs font-semibold">
                            + Programar Cita (CU11)
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200">
                                <th class="py-3.5 px-6">Cita / Fecha y Hora</th>
                                <th class="py-3.5 px-6">Paciente</th>
                                <th class="py-3.5 px-6">Médico / Especialidad</th>
                                <th class="py-3.5 px-6">Estado Actual</th>
                                <th class="py-3.5 px-6 text-right">Acciones Operativas (CU12 / CU13 / CU14)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70 text-sm">
                            @forelse ($panel['citas'] ?? [] as $cita)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-4 px-6">
                                        <span class="text-[11px] font-bold text-[#0284C7] block">#CITA-{{ $cita->id_cita }}</span>
                                        <span class="font-semibold text-[#0F172A]">{{ \Illuminate\Support\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }}</span>
                                        <span class="text-xs text-slate-500 ml-1">{{ substr((string) $cita->hora_cita, 0, 5) }} hrs</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="font-semibold text-[#0F172A] block">{{ $cita->paciente?->nombres }} {{ $cita->paciente?->apellidos }}</span>
                                        <span class="text-xs text-slate-400">CI: {{ $cita->paciente?->ci ?? 'S/D' }}</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="font-medium text-slate-700 block">Dr(a). {{ $cita->medico?->nombres }} {{ $cita->medico?->apellidos }}</span>
                                        <span class="text-xs text-[#0284C7] font-semibold">{{ $cita->especialidad?->nombre_especialidad }}</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        @if ((int) $cita->id_estado_cita === 1)
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold border bg-amber-50 text-amber-700 border-amber-200">
                                                Programada
                                            </span>
                                        @elseif ((int) $cita->id_estado_cita === 2)
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold border bg-sky-50 text-sky-700 border-sky-200">
                                                {{ $cita->fecha_hora_llegada ? 'En Sala de Espera' : 'Confirmada' }}
                                            </span>
                                        @else
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200">
                                                {{ $cita->estado?->nombre_estado ?? 'Activa' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="inline-flex flex-wrap justify-end gap-1.5">
                                            @if ((int) $cita->id_estado_cita === 1)
                                                <form method="POST" action="{{ url("/control-atencion/citas/{$cita->id_cita}/confirmar") }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold border border-emerald-200">
                                                        Confirmar (CU14)
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ url("/control-atencion/citas/{$cita->id_cita}/llegada") }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-sky-50 hover:bg-sky-100 text-[#0284C7] text-xs font-semibold border border-sky-200">
                                                    Llegada
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ url("/control-atencion/citas/{$cita->id_cita}/inasistencia") }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-semibold border border-amber-200">
                                                    Inasistencia
                                                </button>
                                            </form>
                                            <a href="{{ url("/citas/{$cita->id_cita}/reprogramar") }}"
                                               class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                                                CU12 Reprogramar
                                            </a>
                                            <a href="{{ url("/citas/{$cita->id_cita}/cancelar") }}"
                                               class="px-2.5 py-1.5 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold border border-red-200">
                                                CU13 Cancelar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400 text-sm">
                                        No hay citas pendientes en recepción en este momento. Puedes programar una nueva cita en CU11.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ====================================================================== --}}
    {{-- ROLE 3: PACIENTE / FAMILIA (Appointments CU18, Lab Results & Profile CU08) --}}
    {{-- ====================================================================== --}}
    @if ($vista === 'paciente')
        @php
            $pac = $panel['paciente'] ?? null;
        @endphp
        <div class="mt-8 space-y-6">
            <!-- Patient Welcome Banner -->
            <div class="bg-gradient-to-r from-[#0284C7] to-[#0369A1] rounded-2xl p-6 md:p-8 text-white flex flex-col md:flex-row items-start md:items-center justify-between gap-6 shadow-lg">
                <div>
                    <span class="text-xs uppercase tracking-widest text-sky-200 font-semibold block mb-1">
                        EXPEDIENTE PERSONAL • {{ $pac ? 'PAC-' . str_pad((string) $pac->id_paciente, 4, '0', STR_PAD_LEFT) : 'PAC-1082' }}
                    </span>
                    <h3 class="font-serif text-2xl md:text-3xl mb-1">
                        Bienvenido(a), {{ $pac ? $pac->nombres . ' ' . $pac->apellidos : ' a su espacio de salud' }}
                    </h3>
                    <p class="text-sky-100 text-xs sm:text-sm">
                        Revise sus próximas consultas médicas (CU18), descargue resultados de laboratorio e imagenología o actualice su perfil (CU08).
                    </p>
                </div>
                <div class="flex flex-wrap gap-3 shrink-0">
                    <button type="button"
                            @click="$dispatch('abrir-reserva')"
                            class="px-5 py-3 bg-white text-[#0284C7] rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-sky-50 transition-all shadow-md">
                        + Agendar Nueva Consulta (CU11)
                    </button>
                    <a href="{{ url('/perfil-paciente') }}"
                       class="px-4 py-3 bg-white/15 hover:bg-white/25 border border-white/30 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                        Mi Perfil (CU08)
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Upcoming Appointments (CU18) -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <x-icon name="calendar" class="w-4 h-4 text-[#0284C7]" />
                            <h4 class="font-bold text-base text-[#0F172A]">Próximas Citas y Controles (CU18)</h4>
                        </div>
                        <a href="{{ url('/mis-citas') }}" class="text-xs font-semibold text-[#0284C7] hover:underline">
                            Ver todas →
                        </a>
                    </div>

                    <div class="space-y-3">
                        @forelse ($panel['proximas'] ?? [] as $cita)
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex justify-between items-center">
                                <div>
                                    <span class="text-xs font-bold text-[#0284C7] block">
                                        {{ $cita->especialidad?->nombre_especialidad ?? 'Consulta Especializada' }}
                                    </span>
                                    <span class="font-semibold text-sm text-[#0F172A]">
                                        Dr(a). {{ $cita->medico?->nombres }} {{ $cita->medico?->apellidos }}
                                    </span>
                                    <span class="text-xs text-slate-500 block mt-0.5">
                                        {{ $cita->medico?->consultorio ?? 'Consultorio Principal' }} • {{ $cita->motivo_consulta }}
                                    </span>
                                </div>
                                <div class="text-right shrink-0 ml-3">
                                    <span class="text-xs font-bold text-slate-800 block">
                                        {{ \Illuminate\Support\Carbon::parse($cita->fecha_cita)->format('d/m/Y') }}
                                    </span>
                                    <span class="text-xs text-emerald-600 font-semibold">
                                        {{ substr((string) $cita->hora_cita, 0, 5) }} hrs
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex justify-between items-center">
                                <div>
                                    <span class="text-xs font-bold text-[#0284C7] block">Control Postoperatorio • Traumatología</span>
                                    <span class="font-semibold text-sm text-[#0F172A]">Dr. Matías Lagos</span>
                                    <span class="text-xs text-slate-500 block mt-0.5">Torre Consultorios A, Piso 4</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-slate-800 block">Jueves 18</span>
                                    <span class="text-xs text-emerald-600 font-semibold">10:30 AM</span>
                                </div>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex justify-between items-center">
                                <div>
                                    <span class="text-xs font-bold text-[#0284C7] block">Chequeo Cardiológico Preventivo</span>
                                    <span class="font-semibold text-sm text-[#0F172A]">Dra. Carolina Ríos</span>
                                    <span class="text-xs text-slate-500 block mt-0.5">Centro Cardiovascular, Piso 2</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-slate-800 block">Martes 23</span>
                                    <span class="text-xs text-emerald-600 font-semibold">16:00 PM</span>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Medical Reports & Lab Results -->
                <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <x-icon name="file-text" class="w-4 h-4 text-[#0284C7]" />
                            <h4 class="font-bold text-base text-[#0F172A]">Exámenes y Resultados Disponibles</h4>
                        </div>
                        <a href="{{ url('/mis-citas') }}" class="text-xs font-semibold text-[#0284C7] hover:underline">
                            Historial (CU18)
                        </a>
                    </div>
                    <div class="space-y-3">
                        @php
                            $examenes = [
                                ['exam' => 'Hemograma Completo y Perfil Bioquímico', 'date' => 'Hace 2 días', 'unit' => 'Laboratorio Central'],
                                ['exam' => 'Resonancia Magnética Lumbar 3.0T', 'date' => '10 Mar 2026', 'unit' => 'Imagenología Avanzada'],
                                ['exam' => 'Electrocardiograma de Reposo', 'date' => '02 Feb 2026', 'unit' => 'Cardiología'],
                            ];
                        @endphp
                        @foreach ($examenes as $item)
                            <div class="p-3.5 rounded-xl border border-slate-200/80 hover:border-[#0284C7]/50 transition-all flex justify-between items-center">
                                <div>
                                    <span class="font-semibold text-sm text-[#0F172A] block">{{ $item['exam'] }}</span>
                                    <span class="text-xs text-slate-400">{{ $item['unit'] }} • {{ $item['date'] }}</span>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200">
                                    Disponible
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ====================================================================== --}}
    {{-- ROLE 4: ADMINISTRADOR / DIRECCIÓN (KPIs, 19 CUs & Security Audit)       --}}
    {{-- ====================================================================== --}}
    @if ($vista === 'administrador')
        <div class="mt-8 space-y-8">
            <!-- Global KPIs -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 block mb-1">Usuarios Sistema (CU03)</span>
                    <span class="text-2xl font-bold text-[#0F172A]">{{ $panel['totales']['usuarios'] ?? 0 }}</span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 block mb-1">Médicos Activos (CU05)</span>
                    <span class="text-2xl font-bold text-[#0284C7]">{{ $panel['totales']['medicos'] ?? 0 }}</span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 block mb-1">Pacientes (CU07)</span>
                    <span class="text-2xl font-bold text-emerald-600">{{ $panel['totales']['pacientes'] ?? 0 }}</span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 block mb-1">Especialidades (CU04)</span>
                    <span class="text-2xl font-bold text-[#0F172A]">{{ $panel['totales']['especialidades'] ?? 0 }}</span>
                </div>
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 block mb-1">Citas del Mes (CU19)</span>
                    <span class="text-2xl font-bold text-[#0284C7]">{{ $panel['totales']['citas_mes'] ?? 0 }}</span>
                </div>
            </div>

            <!-- Panel Completo de los 19 Casos de Uso -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-[#0284C7]">ARQUITECTURA MODULAR DE 7 PAQUETES</span>
                        <h3 class="font-bold text-lg text-[#0F172A]">Centro de Control de los 19 Casos de Uso (CU01 a CU19)</h3>
                    </div>
                    <a href="{{ url('/reportes-administrativos') }}"
                       class="px-4 py-2 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white text-xs font-semibold">
                        Abrir Reportes Ejecutivos (CU19) →
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach (collect(config('clinica.modulos'))->groupBy('grupo') as $grupo => $items)
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-[#0284C7] mb-3">{{ $grupo }}</h4>
                            <div class="space-y-2">
                                @foreach ($items as $item)
                                    <a href="{{ url($item['ruta']) }}"
                                       class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200/80 hover:border-[#0284C7] transition-all group">
                                        <div>
                                            <span class="text-xs font-bold text-[#0F172A] group-hover:text-[#0284C7] block">{{ $item['titulo'] }}</span>
                                            <span class="text-[11px] text-slate-500">{{ $item['resumen'] }}</span>
                                        </div>
                                        <span class="text-[10px] font-bold text-[#0284C7] bg-sky-50 px-2 py-0.5 rounded shrink-0 ml-2">
                                            {{ $item['cu'] }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Bitácora de Auditoría en Tiempo Real -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-base text-[#0F172A]">Bitácora de Auditoría y Trazabilidad Clínica</h3>
                        <p class="text-xs text-slate-500">Últimos eventos registrados en PostgreSQL por los casos de uso</p>
                    </div>
                    <a href="{{ url('/usuarios') }}" class="text-xs font-semibold text-[#0284C7] hover:underline">
                        Gestionar Usuarios y Roles (CU03) →
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200">
                                <th class="py-3 px-6">ID</th>
                                <th class="py-3 px-6">Tabla / Entidad</th>
                                <th class="py-3 px-6">Operación</th>
                                <th class="py-3 px-6">Descripción</th>
                                <th class="py-3 px-6">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70 text-xs">
                            @forelse ($panel['auditoria'] ?? [] as $log)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-3 px-6 font-bold text-[#0284C7]">#{{ $log->id_auditoria }}</td>
                                    <td class="py-3 px-6 font-semibold text-slate-700">{{ $log->tabla_afectada }}</td>
                                    <td class="py-3 px-6">
                                        <span class="px-2 py-0.5 rounded bg-sky-50 text-[#0284C7] font-semibold">{{ $log->operacion }}</span>
                                    </td>
                                    <td class="py-3 px-6 text-slate-600">{{ $log->descripcion }}</td>
                                    <td class="py-3 px-6 text-slate-400">{{ $log->fecha_evento }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-400">Sin registros recientes en auditoría.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

