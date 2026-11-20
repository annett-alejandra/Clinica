@extends('layouts.app')

@section('title', 'Mi Perfil de Paciente — Clínica Vida Salud')

@section('content')
<div class="container py-12"
     x-data="{ seccion: '{{ ($seccionActiva ?? 'datos') === 'analisis' ? 'analisis' : 'datos' }}' }">

    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-[#0284C7]">PERFIL DEL PACIENTE</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Mi Perfil y Estudios Clínicos</h1>
            <p class="text-sm text-slate-500 mt-1">Actualiza tus datos de contacto, números de emergencia y consulta los análisis clínicos realizados en la clínica.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if(isset($paciente))
                <button type="button"
                        @click="seccion = 'analisis'"
                        :class="seccion === 'analisis'
                            ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-md shadow-[#0284C7]/20'
                            : 'bg-sky-50 hover:bg-sky-100 text-[#0284C7] border-sky-200'"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border text-xs font-bold transition-all cursor-pointer">
                    <x-icon name="activity" class="w-4 h-4" />
                    <span>Ver Mis Análisis Realizados ({{ count($analisis ?? []) }})</span>
                </button>
            @endif
            <span class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                Ficha Médica Activa
            </span>
        </div>
    </div>

    @if(isset($paciente))
        <!-- Pestañas Principales del Perfil -->
        <div class="max-w-5xl mx-auto mb-6 flex flex-wrap items-center gap-2 print:hidden">
            <button type="button"
                    @click="seccion = 'datos'"
                    :class="seccion === 'datos'
                        ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
                        : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                    class="px-4 py-2.5 rounded-xl border text-xs font-bold flex items-center gap-2 transition-all">
                <x-icon name="user-check" class="w-4 h-4" />
                <span>Datos Personales y Contacto</span>
            </button>
            <button type="button"
                    @click="seccion = 'analisis'"
                    :class="seccion === 'analisis'
                        ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-sm'
                        : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                    class="px-4 py-2.5 rounded-xl border text-xs font-bold flex items-center gap-2 transition-all">
                <x-icon name="activity" class="w-4 h-4" />
                <span>Mis Análisis Realizados</span>
                <span class="px-2 py-0.5 rounded-full text-[10px]"
                      :class="seccion === 'analisis' ? 'bg-white/20 text-white' : 'bg-sky-50 text-[#0284C7]'">
                    {{ count($analisis ?? []) }}
                </span>
            </button>
            <a href="{{ url('/mis-citas') }}"
               class="ml-auto px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center gap-1.5 transition-colors">
                <x-icon name="calendar" class="w-4 h-4 text-[#0284C7]" />
                <span>Ir a Mis Citas</span>
            </a>
        </div>

        <!-- SECCIÓN 1: DATOS PERSONALES Y CONTACTO -->
        <div x-show="seccion === 'datos'" class="max-w-5xl mx-auto space-y-6">
            <!-- Acceso Directo a Análisis Clínicos dentro del Perfil -->
            <div class="bg-gradient-to-r from-sky-50 via-white to-emerald-50 border border-sky-200/80 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
                <div class="flex items-start gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-[#0284C7] text-white flex items-center justify-center shrink-0 shadow-sm">
                        <x-icon name="activity" class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-bold text-slate-900">Resultados de Laboratorio y Estudios Diagnósticos</h2>
                        <p class="text-xs text-slate-600 mt-0.5">
                            Tienes <strong class="text-[#0284C7]">{{ count($analisis ?? []) }} análisis clínicos realizados</strong> disponibles para revisar o imprimir con valores de referencia.
                        </p>
                    </div>
                </div>
                <button type="button"
                        @click="seccion = 'analisis'"
                        class="px-4 py-2.5 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white text-xs font-bold shrink-0 shadow-sm transition-colors cursor-pointer">
                    Ver mis análisis realizados →
                </button>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-6 md:p-8">
                <!-- Ficha de Datos Inmutables -->
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 mb-6">
                    <h3 class="text-xs font-mono font-semibold uppercase tracking-wider text-slate-500 mb-3">Datos de Identidad Registrados</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-xs text-slate-400 block">Nombre Completo</span>
                            <strong class="text-slate-800">{{ $paciente->nombre_completo }}</strong>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 block">Cédula (CI)</span>
                            <strong class="text-slate-800 font-mono">{{ $paciente->ci }}</strong>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 block">Fecha de Nacimiento</span>
                            <span class="text-slate-700">{{ \Carbon\Carbon::parse($paciente->fecha_nacimiento)->format('d/m/Y') }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 block">Género</span>
                            <span class="text-slate-700">{{ in_array(strtoupper(substr((string)$paciente->genero, 0, 1)), ['M']) ? 'Masculino' : (in_array(strtoupper(substr((string)$paciente->genero, 0, 1)), ['F']) ? 'Femenino' : 'Otro') }}</span>
                        </div>
                    </div>
                </div>

                @if($paciente->alergias || $paciente->informacion_medica_basica)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        @if($paciente->alergias)
                            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-xs">
                                <span class="font-bold text-rose-800 uppercase tracking-wider text-[10px] block mb-1">Alergias Registradas</span>
                                <p class="text-rose-900 font-semibold">{{ $paciente->alergias }}</p>
                            </div>
                        @endif
                        @if($paciente->informacion_medica_basica)
                            <div class="p-3.5 rounded-xl bg-sky-50 border border-sky-200 text-xs">
                                <span class="font-bold text-sky-800 uppercase tracking-wider text-[10px] block mb-1">Información Médica Base</span>
                                <p class="text-slate-700">{{ $paciente->informacion_medica_basica }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Formulario de Actualización de Contacto -->
                <form action="{{ url('/perfil-paciente') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id_paciente" value="{{ $paciente->id_paciente }}">

                    <div>
                        <h3 class="text-base font-semibold text-slate-900 pb-2 border-b border-slate-100 mb-4">Información de Contacto Directo</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Teléfono Móvil / WhatsApp *</label>
                                <input type="text" name="telefono" value="{{ old('telefono', $paciente->telefono) }}" class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Correo Electrónico *</label>
                                <input type="email" name="correo" value="{{ old('correo', $paciente->correo) }}" class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" required>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Dirección de Domicilio</label>
                            <input type="text" name="direccion_domicilio" value="{{ old('direccion_domicilio', $paciente->direccion_domicilio) }}" class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-slate-900 pb-2 border-b border-slate-100 mb-4">Contacto de Emergencia</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nombre de Referencia</label>
                                <input type="text" name="contacto_emergencia" value="{{ old('contacto_emergencia', $paciente->contacto_emergencia) }}" class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" placeholder="Familiar o apoderado">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Teléfono de Emergencia</label>
                                <input type="text" name="telefono_emergencia" value="{{ old('telefono_emergencia', $paciente->telefono_emergencia) }}" class="w-full text-sm border-slate-200 rounded-xl p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-100">
                        <button type="submit" class="btn-primary">
                            Guardar Cambios de Perfil
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- SECCIÓN 2: MIS ANÁLISIS CLÍNICOS REALIZADOS -->
        <div x-show="seccion === 'analisis'" x-cloak class="max-w-5xl mx-auto space-y-6">
            <x-analisis-clinicos
                :analisis="$analisis ?? []"
                :paciente="$paciente"
                titulo="Mis Análisis Clínicos Realizados"
                subtitulo="Consulta el detalle de tus exámenes de laboratorio, perfiles bioquímicos y estudios diagnósticos." />
        </div>
    @else
        <div class="max-w-3xl mx-auto bg-white rounded-2xl border border-slate-200 p-10 text-center text-slate-500">
            Inicia sesión con tu cuenta de paciente para ver tu perfil y tus análisis clínicos realizados.
        </div>
    @endif
</div>
@endsection
