@extends('layouts.app')

@section('title', 'Triage Clínico Inteligente (IA) — Clínica Vida Salud')

@section('content')
<div class="pt-6 pb-20 px-4 sm:px-6 md:px-12 max-w-7xl mx-auto"
     x-data="triageInteligente({
         urlAnalizar: '{{ route('triage.analizar', [], false) }}',
         urlCitas: '{{ url('/citas') }}',
         csrfToken: '{{ csrf_token() }}'
     })">

    <!-- Cabecera del Módulo de Triage Clínico Inteligente -->
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 pb-8 border-b border-slate-200">
        <div>
            <div class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#0284C7] mb-1.5">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-sky-50 border border-sky-200">
                    <x-icon name="sparkles" class="w-3.5 h-3.5" />
                    <span>NLP VECTORIAL + MATCHING CLÍNICO</span>
                </span>
                <span class="text-slate-300">•</span>
                <span class="text-slate-500">TRIAGE PRELIMINAR ASISTIDO POR IA</span>
            </div>
            <h1 class="font-serif text-3xl md:text-4xl text-[#0F172A]">
                Asignación Inteligente de Especialistas
            </h1>
            <p class="text-sm text-slate-600 mt-1.5 max-w-2xl">
                Describe libremente lo que sientes en tus propias palabras. Nuestro motor analiza tus síntomas mediante
                vectores semánticos y criterios médicos para recomendarte al especialista ideal y detectar señales de emergencia.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <div class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 shadow-sm flex items-center gap-2 text-xs">
                <x-icon name="cpu" class="w-4 h-4 text-[#0284C7]" />
                <div>
                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold block leading-none">Motor Activo</span>
                    <span class="font-bold text-slate-800">
                        {{ $proveedorActivo === 'local_tfidf' ? 'Vectorial Híbrido Local (JSONB)' : strtoupper($proveedorActivo).' Embeddings + LLM' }}
                    </span>
                </div>
            </div>
            @if ($usuario && in_array($usuario->nombreRol(), ['Administrador', 'Recepcionista'], true))
                <a href="{{ route('triage.alertas') }}"
                   class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm transition-colors inline-flex items-center gap-1.5">
                    <x-icon name="bell" class="w-3.5 h-3.5" />
                    <span>Panel de Alertas Rojas</span>
                </a>
            @endif
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- COLUMNA IZQUIERDA: Entrada en Lenguaje Natural + Resultados -->
        <div class="lg:col-span-8 space-y-6">

            <!-- Tarjeta de Entrada en Lenguaje Natural -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 md:p-7">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-sky-50 text-[#0284C7] border border-sky-100 flex items-center justify-center">
                            <x-icon name="heart-pulse" class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="font-bold text-base text-[#0F172A]">¿Qué síntomas sientes hoy?</h2>
                            <p class="text-xs text-slate-500">Escribe con libertad desde cuándo empezaron, dónde sientes la molestia y su intensidad.</p>
                        </div>
                    </div>
                    <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ $totalMedicos }} médicos · {{ $totalEspecialidades }} especialidades</span>
                    </span>
                </div>

                <form @submit.prevent="analizar()" class="space-y-4">
                    <div>
                        <label for="sintomas-input" class="sr-only">Describe tus síntomas</label>
                        <textarea id="sintomas-input"
                                  x-model="sintomas"
                                  rows="4"
                                  maxlength="1500"
                                  :disabled="cargando"
                                  placeholder="Ejemplo: Siento un dolor punzante en el pecho al respirar hondo y mareos leves desde ayer..."
                                  class="w-full rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/15 p-4 text-sm text-slate-800 placeholder:text-slate-400 transition-all resize-none focus:outline-none"></textarea>
                        <div class="mt-1.5 flex items-center justify-between text-[11px] text-slate-400">
                            <span>Incluye síntomas principales, duración y zona afectada para mayor precisión.</span>
                            <span x-text="sintomas.length + ' / 1500'"></span>
                        </div>
                    </div>

                    <!-- Ejemplos rápidos de prueba clínica -->
                    <div class="space-y-2">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Casos de ejemplo para probar el motor:</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="ej in ejemplos" :key="ej.titulo">
                                <button type="button"
                                        @click="usarEjemplo(ej.texto)"
                                        :class="ej.critico
                                            ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200'
                                            : 'bg-slate-50 hover:bg-sky-50 text-slate-700 hover:text-[#0284C7] border-slate-200 hover:border-sky-200'"
                                        class="px-3 py-1.5 rounded-full border text-xs font-semibold transition-colors cursor-pointer">
                                    <span x-text="ej.titulo"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div x-show="error" x-cloak class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium" x-text="error"></div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                        <p class="text-[11px] text-slate-400 flex items-center gap-1.5">
                            <x-icon name="shield-check" class="w-4 h-4 text-emerald-600 shrink-0" />
                            <span>Orientación preliminar confidencial · No reemplaza diagnóstico médico presencial.</span>
                        </p>
                        <button type="submit"
                                :disabled="cargando || sintomas.trim().length < 6"
                                class="px-6 py-3 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white text-sm font-bold shadow-lg shadow-[#0284C7]/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center gap-2 shrink-0">
                            <x-icon name="sparkles" class="w-4 h-4" />
                            <span x-text="cargando ? 'Analizando síntomas...' : 'Analizar y Recomendar Especialista'"></span>
                        </button>
                    </div>
                </form>

                <!-- Indicador de Pipeline NLP durante la ejecución -->
                <div x-show="cargando" x-cloak class="mt-5 p-4 rounded-xl bg-sky-50/70 border border-sky-200/80 space-y-2">
                    <div class="flex items-center gap-2.5 text-xs font-bold text-[#0284C7]">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#0284C7] animate-ping"></span>
                        <span>Ejecutando pipeline de Triage Clínico Vectorial...</span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] text-slate-600">
                        <div class="bg-white/90 rounded-lg px-2.5 py-1.5 border border-sky-100">1. Detección de Emergencias</div>
                        <div class="bg-white/90 rounded-lg px-2.5 py-1.5 border border-sky-100">2. Embeddings / Vectores</div>
                        <div class="bg-white/90 rounded-lg px-2.5 py-1.5 border border-sky-100">3. Similitud Coseno + Criterios</div>
                        <div class="bg-white/90 rounded-lg px-2.5 py-1.5 border border-sky-100">4. Cupos en Agenda (CU10)</div>
                    </div>
                </div>
            </div>

            <!-- RESULTADOS DEL ANÁLISIS IA -->
            <template x-if="resultado">
                <div class="space-y-6">

                    <!-- ALERTA ROJA DE EMERGENCIA PARA EL PACIENTE -->
                    <template x-if="resultado.es_emergencia && resultado.protocolo_emergencia">
                        <div class="rounded-2xl border-2 border-rose-600 bg-gradient-to-br from-rose-600 via-red-600 to-rose-700 text-white p-6 md:p-7 shadow-xl shadow-rose-600/25">
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-5 border-b border-white/20">
                                <div class="flex items-start gap-3.5">
                                    <div class="w-12 h-12 rounded-2xl bg-white/15 border border-white/30 flex items-center justify-center shrink-0 animate-pulse">
                                        <x-icon name="alert-triangle" class="w-6 h-6 text-white" />
                                    </div>
                                    <div>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white text-rose-700 text-[10px] font-extrabold uppercase tracking-widest mb-1.5">
                                            <span class="w-2 h-2 rounded-full bg-rose-600 animate-ping"></span>
                                            <span>CÓDIGO ROJO · ALERTA DE URGENCIA</span>
                                        </span>
                                        <h3 class="font-serif text-xl sm:text-2xl font-bold leading-tight"
                                            x-text="resultado.protocolo_emergencia.titulo"></h3>
                                        <p class="text-xs sm:text-sm text-rose-100 mt-1"
                                           x-text="resultado.protocolo_emergencia.subtitulo"></p>
                                    </div>
                                </div>
                                <div class="px-3 py-2 rounded-xl bg-black/25 border border-white/20 text-[11px] font-semibold shrink-0">
                                    <span class="block text-rose-200 uppercase text-[10px] tracking-wider">Estado Notificación</span>
                                    <span class="flex items-center gap-1.5 text-white font-bold mt-0.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-300"></span>
                                        <span>Alerta Roja enviada a Dirección</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Señales detectadas -->
                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-rose-200">Señales detectadas:</span>
                                <template x-for="pal in resultado.palabras_emergencia_detectadas" :key="pal">
                                    <span class="px-2.5 py-0.5 rounded-full bg-white/20 border border-white/30 text-xs font-bold" x-text="pal"></span>
                                </template>
                            </div>

                            <!-- Pasos del protocolo de urgencias -->
                            <div class="mt-5 bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                                <p class="text-xs font-bold uppercase tracking-wider text-rose-100 mb-2.5">Protocolo Inmediato Recomendado:</p>
                                <ol class="space-y-2 text-xs sm:text-sm text-white">
                                    <template x-for="(paso, idx) in resultado.protocolo_emergencia.pasos" :key="idx">
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-white text-rose-700 font-extrabold text-[11px] flex items-center justify-center shrink-0 mt-0.5" x-text="idx + 1"></span>
                                            <span x-text="paso"></span>
                                        </li>
                                    </template>
                                </ol>
                            </div>

                            <!-- Botones de contacto de emergencia -->
                            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <a :href="'tel:' + resultado.protocolo_emergencia.telefono_urgencias"
                                   class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-white text-rose-700 hover:bg-rose-50 font-extrabold text-xs sm:text-sm shadow-md transition-colors">
                                    <x-icon name="phone-call" class="w-4 h-4" />
                                    <span>Urgencias 24/7: <span x-text="resultado.protocolo_emergencia.telefono_urgencias"></span></span>
                                </a>
                                <div class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-rose-900/50 border border-white/25 text-white font-bold text-xs sm:text-sm">
                                    <x-icon name="activity" class="w-4 h-4 text-rose-200" />
                                    <span>Ambulancia: <span x-text="resultado.protocolo_emergencia.linea_ambulancia"></span></span>
                                </div>
                            </div>
                            <p class="mt-3 text-[11px] text-rose-100 text-center" x-text="resultado.protocolo_emergencia.ubicacion_urgencias"></p>
                        </div>
                    </template>

                    <!-- BANNER PRIORITARIO (CÓDIGO AMARILLO) -->
                    <template x-if="!resultado.es_emergencia && resultado.nivel_urgencia === 'PRIORITARIO'">
                        <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                <x-icon name="alert-circle" class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-wider text-amber-800">Prioridad Clínica Alta (Código Amarillo)</p>
                                <p class="text-xs text-amber-900 mt-0.5">
                                    Tus síntomas ameritan valoración médica prioritaria dentro de las próximas 24 horas. Si los síntomas empeoran súbitamente, acude a Urgencias 24/7.
                                </p>
                            </div>
                        </div>
                    </template>

                    <!-- RECOMENDACIÓN PRINCIPAL DE ESPECIALISTA -->
                    <template x-if="resultado.recomendacion_principal">
                        <div class="bg-white rounded-2xl border-2 border-[#0284C7]/30 shadow-md overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-[#0F172A] to-[#1E293B] text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2.5 py-1 rounded-lg bg-[#0284C7] text-white text-[10px] font-extrabold uppercase tracking-wider">
                                        ESPECIALISTA IDEAL
                                    </span>
                                    <span class="text-xs text-sky-200 font-medium" x-text="resultado.modo_vectorial"></span>
                                </div>
                                <div class="inline-flex items-center gap-2 bg-emerald-500/20 border border-emerald-400/40 px-3 py-1 rounded-full">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                    <span class="text-sm font-extrabold text-emerald-200"
                                          x-text="resultado.recomendacion_principal.especialidad.nombre_especialidad + ' — ' + resultado.recomendacion_principal.porcentaje_afinidad + '% de afinidad'"></span>
                                </div>
                            </div>

                            <div class="p-6 space-y-5">
                                <div class="flex flex-col md:flex-row md:items-start justify-between gap-5">
                                    <div class="space-y-2 flex-1">
                                        <span class="text-xs font-bold uppercase tracking-wider text-[#0284C7]"
                                              x-text="resultado.recomendacion_principal.especialidad.nombre_especialidad"></span>
                                        <h3 class="font-serif text-2xl text-[#0F172A]"
                                            x-text="resultado.recomendacion_principal.medico.nombre_completo"></h3>
                                        <p class="text-xs text-slate-500">
                                            Registro Profesional: <strong class="text-slate-700" x-text="resultado.recomendacion_principal.medico.registro_profesional || 'Certificado'"></strong>
                                            <template x-if="resultado.recomendacion_principal.proximo_cupo">
                                                <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold">
                                                    <x-icon name="clock" class="w-3 h-3" />
                                                    <span>Próximo turno: <span x-text="resultado.recomendacion_principal.proximo_cupo.fecha_legible + ' · ' + resultado.recomendacion_principal.proximo_cupo.hora"></span></span>
                                                </span>
                                            </template>
                                        </p>

                                        <!-- Subespecialidades -->
                                        <div class="flex flex-wrap gap-1.5 pt-1">
                                            <template x-for="sub in resultado.recomendacion_principal.subespecialidades" :key="sub">
                                                <span class="px-2.5 py-0.5 rounded-full bg-sky-50 text-[#0284C7] border border-sky-200/70 text-[11px] font-semibold" x-text="sub"></span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Indicador visual de porcentaje de afinidad -->
                                    <div class="shrink-0 flex md:flex-col items-center justify-between md:justify-center gap-2 bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 min-w-[140px]">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Afinidad IA</span>
                                        <span class="text-3xl font-extrabold text-[#0284C7]"
                                              x-text="resultado.recomendacion_principal.porcentaje_afinidad + '%'"></span>
                                        <div class="w-24 h-2 bg-slate-200 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-[#0284C7] to-emerald-500 rounded-full transition-all duration-500"
                                                 :style="'width: ' + Math.min(100, resultado.recomendacion_principal.porcentaje_afinidad) + '%'"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Explicación sencilla para el paciente -->
                                <div class="p-4 rounded-xl bg-sky-50/70 border border-sky-100">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-[#0284C7] mb-1">¿Por qué te sugerimos este especialista?</p>
                                    <p class="text-sm text-slate-700 leading-relaxed" x-text="resultado.explicacion_clinica"></p>
                                </div>

                                <!-- Criterios clínicos coincidentes -->
                                <template x-if="resultado.recomendacion_principal.criterios_coincidentes && resultado.recomendacion_principal.criterios_coincidentes.length > 0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mr-1">Criterios detectados:</span>
                                        <template x-for="crit in resultado.recomendacion_principal.criterios_coincidentes" :key="crit">
                                            <span class="px-2.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-semibold" x-text="crit"></span>
                                        </template>
                                    </div>
                                </template>

                                <!-- Desglose transparente del algoritmo de scoring -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Similitud Vectorial</span>
                                        <span class="text-base font-extrabold text-slate-800"
                                              x-text="resultado.recomendacion_principal.desglose.similitud_vectorial_pct + '%'"></span>
                                    </div>
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Matching Criterios</span>
                                        <span class="text-base font-extrabold text-slate-800"
                                              x-text="resultado.recomendacion_principal.desglose.matching_criterios_pct + '%'"></span>
                                    </div>
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Efectividad Clínica</span>
                                        <span class="text-base font-extrabold text-emerald-600"
                                              x-text="resultado.recomendacion_principal.desglose.efectividad_historica_pct + '%'"></span>
                                    </div>
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Disponibilidad</span>
                                        <span class="text-base font-extrabold text-[#0284C7]"
                                              x-text="resultado.recomendacion_principal.desglose.disponibilidad_pct + '%'"></span>
                                    </div>
                                </div>

                                <!-- Acción directa: Agendar cita prellenada -->
                                <div class="pt-2 flex flex-col sm:flex-row gap-3">
                                    <a :href="urlReserva(resultado.recomendacion_principal)"
                                       class="flex-1 py-3 px-5 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white text-sm font-bold shadow-md shadow-[#0284C7]/20 transition-all text-center inline-flex items-center justify-center gap-2">
                                        <x-icon name="calendar" class="w-4 h-4" />
                                        <span>Agendar Cita con este Especialista →</span>
                                    </a>
                                    <a href="{{ url('/disponibilidad') }}"
                                       class="px-5 py-3 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold inline-flex items-center justify-center">
                                        Ver Disponibilidad General
                                    </a>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- OTROS ESPECIALISTAS SUGERIDOS (RANKING SECUNDARIO) -->
                    <template x-if="resultado.recomendaciones && resultado.recomendaciones.length > 1">
                        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6">
                            <h4 class="font-bold text-sm uppercase tracking-wider text-slate-500 mb-4">
                                Otras Opciones Clínicas Compatibles
                            </h4>
                            <div class="space-y-3">
                                <template x-for="item in resultado.recomendaciones.slice(1)" :key="item.medico.id_medico + '-' + item.especialidad.id_especialidad">
                                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 hover:border-[#0284C7]/50 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="space-y-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-xs font-bold text-[#0284C7]" x-text="item.especialidad.nombre_especialidad"></span>
                                                <span class="px-2 py-0.5 rounded-full bg-white border border-slate-200 text-[11px] font-extrabold text-slate-700"
                                                      x-text="item.porcentaje_afinidad + '% afinidad'"></span>
                                            </div>
                                            <p class="font-bold text-sm text-[#0F172A]" x-text="item.medico.nombre_completo"></p>
                                            <p class="text-xs text-slate-500" x-text="item.explicacion_individual"></p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <template x-if="item.proximo_cupo">
                                                <span class="text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg font-semibold"
                                                      x-text="item.proximo_cupo.fecha_legible + ' ' + item.proximo_cupo.hora"></span>
                                            </template>
                                            <a :href="urlReserva(item)"
                                               class="px-3.5 py-2 rounded-lg bg-white hover:bg-[#0284C7] text-[#0284C7] hover:text-white border border-sky-200 text-xs font-bold transition-colors">
                                                Agendar
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <p class="text-[11px] text-slate-400 text-center px-4" x-text="resultado.aviso_legal"></p>
                </div>
            </template>
        </div>

        <!-- COLUMNA DERECHA: Cómo funciona + Historial reciente -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 space-y-4">
                <h3 class="font-bold text-sm uppercase tracking-wider text-[#0284C7]">¿Cómo funciona el Motor IA?</h3>
                <ol class="space-y-3.5 text-xs text-slate-600">
                    <li class="flex items-start gap-3">
                        <span class="w-6 h-6 rounded-lg bg-sky-50 text-[#0284C7] font-bold flex items-center justify-center shrink-0">1</span>
                        <div>
                            <strong class="text-slate-800 block">NLP Vectorial Híbrido</strong>
                            Convierte tus síntomas en un vector semántico (API de Embeddings OpenAI/Gemini/Mistral o motor vectorial local JSONB).
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="w-6 h-6 rounded-lg bg-sky-50 text-[#0284C7] font-bold flex items-center justify-center shrink-0">2</span>
                        <div>
                            <strong class="text-slate-800 block">Matching de Criterios Médicos</strong>
                            Compara tu consulta contra subespecialidades, etiquetas de patologías, efectividad histórica y disponibilidad real en agenda.
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="w-6 h-6 rounded-lg bg-rose-50 text-rose-600 font-bold flex items-center justify-center shrink-0">3</span>
                        <div>
                            <strong class="text-slate-800 block">Escudo de Alertas Rojas</strong>
                            Si detecta signos de alarma vital (dolor torácico intenso, falta de aire súbita, déficit neurológico), activa el protocolo de urgencias y alerta en tiempo real al Administrador.
                        </div>
                    </li>
                </ol>
            </div>

            <!-- Historial reciente de evaluaciones -->
            @if ($historialReciente->isNotEmpty())
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6">
                    <h3 class="font-bold text-sm uppercase tracking-wider text-slate-500 mb-3.5">Evaluaciones Recientes</h3>
                    <div class="space-y-2.5">
                        @foreach ($historialReciente as $item)
                            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-xs space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-[#0284C7] truncate">
                                        {{ $item->especialidadRecomendada?->nombre_especialidad ?? 'Evaluación Clínica' }}
                                    </span>
                                    @if ($item->es_emergencia)
                                        <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 font-extrabold text-[10px]">EMERGENCIA</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[10px]">
                                            {{ number_format((float) $item->puntaje_afinidad, 0) }}%
                                        </span>
                                    @endif
                                </div>
                                <p class="text-slate-600 line-clamp-2">"{{ $item->sintomas_texto }}"</p>
                                <p class="text-[10px] text-slate-400">
                                    {{ $item->medicoRecomendado?->nombre_completo ?? 'Especialista sugerido' }} ·
                                    {{ \Carbon\Carbon::parse($item->fecha_registro)->timezone('America/La_Paz')->format('d/m H:i') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('triageInteligente', (cfg) => ({
        sintomas: '',
        cargando: false,
        error: '',
        resultado: null,
        ejemplos: [
            {
                titulo: 'Dolor pecho + mareos (Alerta Roja)',
                texto: 'Siento un dolor punzante en el pecho al respirar hondo y mareos leves desde ayer',
                critico: true,
            },
            {
                titulo: 'Fiebre infantil y tos',
                texto: 'Mi hijo de 4 años tiene fiebre desde anoche, tos con flema y dolor de oído',
                critico: false,
            },
            {
                titulo: 'Dolor de rodilla deportiva',
                texto: 'Me caí jugando fútbol y tengo dolor fuerte e inflamación en la rodilla al caminar',
                critico: false,
            },
            {
                titulo: 'Migraña y hormigueo',
                texto: 'Tengo dolor de cabeza intenso tipo migraña con sensibilidad a la luz y hormigueo en la mano desde hace 2 días',
                critico: false,
            },
            {
                titulo: 'Sarpullido en la piel',
                texto: 'Me salieron ronchas rojas y sarpullido con mucha picazón en los brazos después de cambiar de crema',
                critico: false,
            },
        ],

        usarEjemplo(texto) {
            this.sintomas = texto;
            this.error = '';
        },

        async analizar() {
            this.error = '';
            if (this.sintomas.trim().length < 6) {
                this.error = 'Por favor describe tus síntomas con al menos 6 caracteres.';
                return;
            }

            this.cargando = true;
            try {
                const resp = await fetch(cfg.urlAnalizar, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrfToken,
                    },
                    body: JSON.stringify({ sintomas: this.sintomas.trim() }),
                });

                const json = await resp.json();
                if (!resp.ok || !json.success) {
                    const primerError = json.errors ? Object.values(json.errors).flat()[0] : null;
                    this.error = primerError || json.message || 'No se pudo procesar el análisis de síntomas.';
                    return;
                }

                this.resultado = json.data;
            } catch (e) {
                this.error = 'Error de comunicación con el servidor de Triage IA. Intenta nuevamente.';
            } finally {
                this.cargando = false;
            }
        },

        urlReserva(item) {
            const params = new URLSearchParams({
                id_especialidad: String(item.especialidad.id_especialidad),
                id_medico: String(item.medico.id_medico),
                motivo: (this.resultado?.sintomas_analizados || this.sintomas || '').substring(0, 240),
            });
            if (item.proximo_cupo) {
                params.set('fecha_cita', item.proximo_cupo.fecha);
                params.set('hora_cita', item.proximo_cupo.hora);
            }
            return `${cfg.urlCitas}?${params.toString()}`;
        },
    }));
});
</script>
@endpush
@endsection
