{{-- Widget flotante global del Asistente Virtual IA (sin consultas SQL directas para compatibilidad con vistas sin BD). --}}
<div class="fixed bottom-5 right-5 z-40 print:hidden no-print"
     x-data="chatbotClinica()"
     data-endpoint-mensaje="{{ route('asistente-ia.mensaje', [], false) }}"
     data-endpoint-estado="{{ route('asistente-ia.estado', [], false) }}">

    <!-- Panel flotante del chat -->
    <div x-show="abierto"
         x-cloak
         x-transition.opacity.duration.150ms
         @click.outside="abierto = false"
         class="mb-3 w-[calc(100vw-2.5rem)] sm:w-96 bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[75vh]">
        <!-- Cabecera -->
        <div class="px-4 py-3 bg-[#0F172A] text-white flex items-center justify-between gap-2">
            <div class="flex items-center space-x-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-[#0284C7] flex items-center justify-center shrink-0">
                    <x-icon name="sparkles" class="w-4 h-4 text-white" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-bold leading-tight truncate">Asistente Clínica Vida Salud</p>
                    <p class="text-[10px] text-sky-300 truncate"
                       x-text="motor.modo === 'ia_api' ? ('IA Activa · ' + motor.proveedor) : 'Asistente Clínico Virtual'"></p>
                </div>
            </div>
            <div class="flex items-center space-x-1 shrink-0">
                <a href="{{ route('asistente-ia') }}"
                   title="Abrir pantalla completa"
                   class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                    <x-icon name="maximize" class="w-3.5 h-3.5" />
                </a>
                <button type="button"
                        @click="limpiar()"
                        title="Reiniciar chat"
                        class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                    <x-icon name="trash" class="w-3.5 h-3.5" />
                </button>
                <button type="button"
                        @click="abierto = false"
                        aria-label="Cerrar asistente virtual"
                        class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                    <x-icon name="x" class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Mensajes -->
        <div x-ref="chatMensajes"
             class="flex-1 h-72 overflow-y-auto p-3.5 space-y-3 bg-[#F8FAFC]">
            <template x-for="m in mensajes" :key="m.id">
                <div class="flex flex-col" :class="m.rol === 'user' ? 'items-end' : 'items-start'">
                    <div class="max-w-[88%] rounded-2xl px-3.5 py-2.5 text-xs leading-relaxed shadow-sm"
                         :class="m.rol === 'user'
                            ? 'bg-[#0284C7] text-white rounded-br-sm'
                            : 'bg-white border border-slate-200 text-slate-800 rounded-bl-sm'">
                        <p class="whitespace-pre-line" x-text="m.contenido"></p>

                        <template x-if="m.rol === 'assistant' && m.enlaces && m.enlaces.length > 0">
                            <div class="mt-2 pt-2 border-t border-slate-100 flex flex-wrap gap-1.5">
                                <template x-for="(enlace, idx) in m.enlaces" :key="idx">
                                    <a :href="enlace.url"
                                       class="inline-flex items-center space-x-1 text-[10px] font-bold text-[#0284C7] bg-sky-50 hover:bg-sky-100 px-2 py-0.5 rounded-md border border-sky-200/70 transition-colors">
                                        <span x-text="enlace.titulo"></span>
                                        <x-icon name="chevron-right" class="w-2.5 h-2.5" />
                                    </a>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Chips rápidos iniciales -->
            <div x-show="mensajes.length <= 1 && !cargando" class="flex flex-wrap gap-1.5 pt-1">
                <a href="{{ route('triage.index') }}"
                   class="inline-flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-full bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold transition-colors">
                    <x-icon name="heart-pulse" class="w-3 h-3" />
                    <span>Triage por Síntomas IA</span>
                </a>
                <button type="button"
                        @click="preguntar('¿Qué especialidades tienen disponibles?')"
                        class="text-[11px] px-2.5 py-1 rounded-full bg-white hover:bg-sky-50 text-[#0284C7] border border-sky-200 font-medium transition-colors">
                    Especialidades
                </button>
                <button type="button"
                        @click="preguntar('¿Qué horarios atienden los médicos?')"
                        class="text-[11px] px-2.5 py-1 rounded-full bg-white hover:bg-sky-50 text-[#0284C7] border border-sky-200 font-medium transition-colors">
                    Horarios médicos
                </button>
                <button type="button"
                        @click="preguntar('¿Cómo agendo o reprogramo una cita?')"
                        class="text-[11px] px-2.5 py-1 rounded-full bg-white hover:bg-sky-50 text-[#0284C7] border border-sky-200 font-medium transition-colors">
                    Cómo agendar cita
                </button>
            </div>

            <div x-show="cargando" x-cloak class="flex items-start">
                <div class="bg-white border border-slate-200 rounded-xl px-3 py-2 text-[11px] text-slate-500 shadow-sm">
                    Escribiendo respuesta...
                </div>
            </div>
        </div>

        <div x-show="error" x-cloak class="px-3.5 py-2 bg-red-50 border-t border-red-200 text-[11px] text-red-700" x-text="error"></div>

        <!-- Formulario -->
        <form @submit.prevent="enviar()" class="p-3 bg-white border-t border-slate-200 flex items-center gap-2">
            <label for="chatbot-widget-input" class="sr-only">Consulta al asistente</label>
            <input id="chatbot-widget-input"
                   type="text"
                   x-model="entrada"
                   maxlength="1000"
                   :disabled="cargando"
                   placeholder="Escribe tu pregunta..."
                   class="flex-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-xs text-slate-800 focus:outline-none focus:border-[#0284C7]">
            <button type="submit"
                    :disabled="cargando || !entrada.trim()"
                    aria-label="Enviar consulta"
                    class="p-2 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white transition-colors disabled:opacity-50 shrink-0">
                <x-icon name="send" class="w-4 h-4" />
            </button>
        </form>
    </div>

    <!-- Botón flotante de apertura -->
    <div class="flex justify-end">
        <button type="button"
                @click="toggle()"
                aria-label="Abrir asistente virtual clínico"
                class="flex items-center space-x-2 bg-[#0284C7] hover:bg-[#0369A1] text-white px-4 py-3 rounded-full shadow-lg shadow-[#0284C7]/30 transition-all hover:scale-[1.02]">
            <x-icon name="message-circle" class="w-5 h-5" />
            <span class="text-xs font-bold hidden sm:inline" x-text="abierto ? 'Cerrar Chat' : 'Asistente IA'">Asistente IA</span>
        </button>
    </div>
</div>
