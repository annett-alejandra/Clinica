@extends('layouts.app')

@section('title', 'Asistente Virtual Clínico IA — ' . ($clinica['nombre'] ?? 'Clínica Vida Salud'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-16">
    <!-- Encabezado CU20 -->
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
        <div>
            <div class="inline-flex items-center space-x-2 bg-sky-50 border border-sky-200 text-[#0284C7] px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider mb-2">
                <x-icon name="sparkles" class="w-3.5 h-3.5" />
                <span>Paquete 8 · CU20 Asistencia Inteligente</span>
            </div>
            <h1 class="font-serif text-3xl sm:text-4xl font-semibold text-[#0F172A] tracking-tight">
                Asistente Virtual Clínico IA
            </h1>
            <p class="text-sm text-slate-600 mt-1.5 max-w-2xl">
                Consulta especialidades médicas, médicos de planta, horarios de atención, pasos para agendar o reprogramar citas e información institucional en tiempo real.
            </p>
        </div>

        <div class="flex items-center gap-2.5 self-start md:self-auto">
            <a href="{{ url('/disponibilidad') }}"
               class="inline-flex items-center space-x-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition-colors">
                <x-icon name="calendar" class="w-3.5 h-3.5 text-[#0284C7]" />
                <span>Ver Disponibilidad</span>
            </a>
            <button type="button"
                    @click="$dispatch('abrir-reserva')"
                    class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white text-xs font-semibold shadow-sm transition-colors">
                <x-icon name="stethoscope" class="w-3.5 h-3.5" />
                <span>Agendar Cita</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6"
         x-data="chatbotClinica()"
         data-endpoint-mensaje="{{ route('asistente-ia.mensaje', [], false) }}"
         data-endpoint-estado="{{ route('asistente-ia.estado', [], false) }}"
         data-configurado="{{ !empty($estadoMotor['configurado']) ? '1' : '0' }}"
         data-proveedor="{{ $estadoMotor['proveedor'] ?? 'asistente_clinico_local' }}"
         data-modelo="{{ $estadoMotor['modelo'] ?? 'Base de Conocimiento Clínica' }}"
         data-modo="{{ $estadoMotor['modo'] ?? 'local_fallback' }}">

        <!-- Área Principal de Conversación -->
        <section class="lg:col-span-8 bg-white border border-slate-200 rounded-2xl shadow-sm flex flex-col overflow-hidden">
            <!-- Barra superior del Chat -->
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/80 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0284C7] text-white flex items-center justify-center shadow-sm">
                        <x-icon name="sparkles" class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-[#0F172A]">Orientador Virtual · Clínica Vida Salud</h2>
                        <div class="flex items-center space-x-2 mt-0.5">
                            <span class="inline-flex items-center space-x-1.5 text-[11px] font-semibold px-2 py-0.5 rounded-full"
                                  :class="motor.modo === 'ia_api' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-sky-50 text-[#0284C7] border border-sky-200'">
                                <span class="w-1.5 h-1.5 rounded-full"
                                      :class="motor.modo === 'ia_api' ? 'bg-emerald-500' : 'bg-[#0284C7]'"></span>
                                <span x-text="motor.modo === 'ia_api' ? ('IA Conectada: ' + motor.proveedor + ' (' + motor.modelo + ')') : 'Asistente Clínico Local (BD en vivo)'"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <button type="button"
                        @click="limpiar()"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-red-600 hover:bg-red-50 border border-slate-200 bg-white transition-colors">
                    <x-icon name="trash" class="w-3.5 h-3.5" />
                    <span>Nueva conversación</span>
                </button>
            </div>

            <!-- Preguntas frecuentes sugeridas -->
            <div class="px-5 py-3 border-b border-slate-100 bg-white flex flex-wrap gap-2">
                <button type="button"
                        @click="preguntar('¿Qué especialidades médicas atienden en la clínica?')"
                        :disabled="cargando"
                        class="text-xs px-3 py-1.5 rounded-full bg-slate-100 hover:bg-sky-50 hover:text-[#0284C7] text-slate-700 font-medium border border-slate-200/80 transition-colors disabled:opacity-50">
                    Especialidades disponibles
                </button>
                <button type="button"
                        @click="preguntar('¿Qué médicos y horarios de atención tienen?')"
                        :disabled="cargando"
                        class="text-xs px-3 py-1.5 rounded-full bg-slate-100 hover:bg-sky-50 hover:text-[#0284C7] text-slate-700 font-medium border border-slate-200/80 transition-colors disabled:opacity-50">
                    Médicos y horarios
                </button>
                <button type="button"
                        @click="preguntar('¿Cómo puedo agendar, reprogramar o cancelar una cita?')"
                        :disabled="cargando"
                        class="text-xs px-3 py-1.5 rounded-full bg-slate-100 hover:bg-sky-50 hover:text-[#0284C7] text-slate-700 font-medium border border-slate-200/80 transition-colors disabled:opacity-50">
                    Agendar o reprogramar cita
                </button>
                <button type="button"
                        @click="preguntar('¿Cuál es la dirección, teléfono y horario de urgencias?')"
                        :disabled="cargando"
                        class="text-xs px-3 py-1.5 rounded-full bg-slate-100 hover:bg-sky-50 hover:text-[#0284C7] text-slate-700 font-medium border border-slate-200/80 transition-colors disabled:opacity-50">
                    Ubicación y urgencias
                </button>
            </div>

            <!-- Historial de mensajes -->
            <div x-ref="chatMensajes"
                 class="flex-1 h-[440px] overflow-y-auto p-5 space-y-4 bg-[#F8FAFC]">
                <template x-for="m in mensajes" :key="m.id">
                    <div class="flex flex-col" :class="m.rol === 'user' ? 'items-end' : 'items-start'">
                        <div class="max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-relaxed shadow-sm"
                             :class="m.rol === 'user'
                                ? 'bg-[#0284C7] text-white rounded-br-sm'
                                : 'bg-white border border-slate-200 text-slate-800 rounded-bl-sm'">
                            <p class="whitespace-pre-line" x-text="m.contenido"></p>

                            <!-- Enlaces contextuales accionables -->
                            <template x-if="m.rol === 'assistant' && m.enlaces && m.enlaces.length > 0">
                                <div class="mt-3 pt-2.5 border-t border-slate-100 flex flex-wrap gap-2">
                                    <template x-for="(enlace, idx) in m.enlaces" :key="idx">
                                        <a :href="enlace.url"
                                           class="inline-flex items-center space-x-1 text-[11px] font-bold text-[#0284C7] bg-sky-50 hover:bg-sky-100 px-2.5 py-1 rounded-lg border border-sky-200/70 transition-colors">
                                            <span x-text="enlace.titulo"></span>
                                            <x-icon name="chevron-right" class="w-3 h-3" />
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center space-x-2 mt-1 px-1 text-[10px] text-slate-400">
                            <span x-text="m.rol === 'user' ? 'Tú' : 'Asistente'"></span>
                            <template x-if="m.hora">
                                <span x-text="'· ' + m.hora"></span>
                            </template>
                            <template x-if="m.rol === 'assistant' && m.modo">
                                <span x-text="m.modo === 'ia_api' ? ('· IA (' + (m.proveedor || 'api') + ')') : '· Motor local'"></span>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Indicador de carga -->
                <div x-show="cargando" x-cloak class="flex items-start">
                    <div class="bg-white border border-slate-200 rounded-2xl rounded-bl-sm px-4 py-3 shadow-sm">
                        <div class="flex items-center space-x-2 text-xs text-slate-500 font-medium">
                            <span class="w-2 h-2 rounded-full bg-[#0284C7] animate-bounce"></span>
                            <span>Consultando información clínica...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerta de error -->
            <div x-show="error" x-cloak class="px-5 py-2.5 bg-red-50 border-t border-red-200 text-xs text-red-700 font-medium flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <x-icon name="alert-circle" class="w-4 h-4 shrink-0" />
                    <span x-text="error"></span>
                </div>
                <button type="button" @click="error = ''" class="text-red-500 hover:text-red-700 font-bold ml-3">Cerrar</button>
            </div>

            <!-- Caja de envío -->
            <form @submit.prevent="enviar()" class="p-4 bg-white border-t border-slate-200">
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <label for="chat-input-principal" class="sr-only">Escribe tu consulta sobre la clínica</label>
                        <input id="chat-input-principal"
                               type="text"
                               x-model="entrada"
                               maxlength="1000"
                               :disabled="cargando"
                               placeholder="Pregunta sobre especialidades, médicos, horarios, citas o trámites..."
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-sm text-slate-800 focus:outline-none focus:border-[#0284C7] transition-colors disabled:opacity-60">
                    </div>
                    <button type="submit"
                            :disabled="cargando || !entrada.trim()"
                            class="inline-flex items-center space-x-2 px-5 py-3 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white text-xs font-bold shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed shrink-0">
                        <x-icon name="send" class="w-4 h-4" />
                        <span>Enviar</span>
                    </button>
                </div>
                <p class="text-[11px] text-slate-400 mt-2 flex items-center justify-between">
                    <span>Orientación institucional y operativa. Ante emergencias acuda a Urgencias 24/7.</span>
                    <span x-text="(entrada || '').length + '/1000'"></span>
                </p>
            </form>
        </section>

        <!-- Barra Lateral de Contexto y Seguridad -->
        <aside class="lg:col-span-4 space-y-5">
            <!-- Aviso Clínico -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center space-x-2.5 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-200/70">
                        <x-icon name="shield-check" class="w-4 h-4" />
                    </div>
                    <h3 class="text-sm font-bold text-[#0F172A]">Alcance y Seguridad Clínica</h3>
                </div>
                <ul class="space-y-2 text-xs text-slate-600 leading-relaxed">
                    <li class="flex items-start space-x-2">
                        <span class="text-[#0284C7] font-bold">•</span>
                        <span>Responde con datos reales de especialidades, médicos y horarios registrados en el sistema.</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <span class="text-[#0284C7] font-bold">•</span>
                        <span>No emite diagnósticos ni prescribe medicamentos.</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <span class="text-amber-600 font-bold">•</span>
                        <span>Emergencias: <strong>{{ $clinica['urgencias'] ?? 'Urgencias 24/7' }}</strong> · Tel: <strong>{{ $clinica['telefono'] ?? '+591 3 300 0000' }}</strong>.</span>
                    </li>
                </ul>
            </div>

            <!-- Especialidades Activas -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-bold text-[#0F172A] mb-3 flex items-center space-x-2">
                    <x-icon name="heart-pulse" class="w-4 h-4 text-[#0284C7]" />
                    <span>Especialidades Consultables</span>
                </h3>
                @if ($especialidades->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($especialidades as $esp)
                            <button type="button"
                                    data-pregunta="{{ '¿Qué horarios y médicos tienen para ' . $esp->nombre_especialidad . '?' }}"
                                    @click="preguntar($el.dataset.pregunta)"
                                    class="text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-sky-50 hover:text-[#0284C7] text-slate-700 border border-slate-200/70 transition-colors">
                                {{ $esp->nombre_especialidad }}
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-500">
                        Catálogo institucional disponible al consultar en línea o desde el módulo de Disponibilidad.
                    </p>
                @endif
            </div>

            <!-- Accesos Directos -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-2.5">
                <h3 class="text-sm font-bold text-[#0F172A] mb-1">Trámites Rápidos</h3>
                <a href="{{ url('/disponibilidad') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-50 hover:bg-sky-50 text-xs font-semibold text-slate-700 hover:text-[#0284C7] border border-slate-200/70 transition-colors">
                    <span>Consultar Disponibilidad (CU10)</span>
                    <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
                <a href="{{ url('/citas') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-50 hover:bg-sky-50 text-xs font-semibold text-slate-700 hover:text-[#0284C7] border border-slate-200/70 transition-colors">
                    <span>Programar Nueva Cita (CU11)</span>
                    <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
                <a href="{{ url('/mis-citas') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-slate-50 hover:bg-sky-50 text-xs font-semibold text-slate-700 hover:text-[#0284C7] border border-slate-200/70 transition-colors">
                    <span>Mis Citas / Reprogramar (CU18)</span>
                    <x-icon name="chevron-right" class="w-4 h-4" />
                </a>
            </div>

            <!-- Guía de API Key (solo visible para Administrador) -->
            @if ($esAdmin ?? false)
                <div class="bg-slate-900 text-slate-100 rounded-2xl p-5 shadow-sm border border-slate-800">
                    <div class="flex items-center space-x-2 mb-2">
                        <x-icon name="info" class="w-4 h-4 text-sky-400" />
                        <h3 class="text-xs font-bold uppercase tracking-wider text-sky-300">Configuración Motor IA (Admin)</h3>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed mb-3">
                        Estado actual:
                        <strong class="text-white">{{ !empty($estadoMotor['configurado']) ? 'API Externa Activa' : 'Fallback Local Activo (sin API key externa)' }}</strong>.
                    </p>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        Para habilitar IA generativa en <code class="text-sky-300">.env</code> o <code class="text-sky-300">docker-compose.yml</code>, define una llave en:
                        <code class="text-sky-300">GEMINI_API_KEY</code>, <code class="text-sky-300">GROQ_API_KEY</code>, <code class="text-sky-300">OPENAI_API_KEY</code> u <code class="text-sky-300">OPENROUTER_API_KEY</code>
                        (con <code class="text-sky-300">IA_PROVIDER=auto</code>).
                    </p>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
