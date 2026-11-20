{{-- Banner global en tiempo real de Alertas Rojas de Emergencia (visible solo para Administrador y Recepcionista). --}}
@props(['activo' => false])

@if ($activo)
<div x-data="{
        alertas: [],
        silenciado: false,
        intervalId: null,
        async consultar() {
            try {
                const resp = await fetch('{{ route('triage.alertas.activas', [], false) }}', {
                    headers: { Accept: 'application/json' }
                });
                if (!resp.ok) return;
                const json = await resp.json();
                if (json.success && Array.isArray(json.data)) {
                    this.alertas = json.data;
                }
            } catch (e) {}
        },
        async atender(id) {
            try {
                const resp = await fetch('{{ url('/triage-inteligente/alertas') }}/' + id + '/atender', {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                if (resp.ok) {
                    this.alertas = this.alertas.filter(a => a.id_triage !== id);
                }
            } catch (e) {}
        },
        init() {
            this.consultar();
            this.intervalId = setInterval(() => this.consultar(), 12000);
        }
     }"
     x-show="alertas.length > 0 && !silenciado"
     x-cloak
     class="fixed top-[72px] left-0 right-0 z-40 px-4 sm:px-6 md:px-12 pointer-events-none print:hidden">
    <div class="max-w-7xl mx-auto pointer-events-auto">
        <div class="rounded-2xl bg-gradient-to-r from-rose-700 via-red-600 to-rose-700 text-white border-2 border-rose-300 shadow-2xl shadow-rose-700/40 p-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/15 border border-white/30 flex items-center justify-center shrink-0 animate-pulse">
                        <x-icon name="alert-triangle" class="w-5 h-5 text-white" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2 py-0.5 rounded bg-white text-rose-700 text-[10px] font-extrabold uppercase tracking-wider">
                                ALERTA ROJA TRIAGE IA (<span x-text="alertas.length"></span>)
                            </span>
                            <span class="text-xs font-bold text-white" x-text="alertas[0]?.paciente_nombre"></span>
                            <span class="text-[11px] text-rose-200" x-text="alertas[0]?.hace"></span>
                        </div>
                        <p class="text-xs sm:text-sm text-white/95 font-medium mt-0.5 truncate"
                           x-text="'Síntomas: ' + (alertas[0]?.sintomas_texto || '')"></p>
                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                            <template x-for="pal in (alertas[0]?.palabras_detectadas || []).slice(0, 4)" :key="pal">
                                <span class="px-2 py-0.5 rounded-full bg-black/25 border border-white/20 text-[10px] font-bold" x-text="pal"></span>
                            </template>
                            <span class="text-[11px] text-rose-100 ml-1"
                                  x-text="'Especialidad sugerida: ' + (alertas[0]?.especialidad_sugerida || 'Urgencias')"></span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <button type="button"
                            @click="atender(alertas[0].id_triage)"
                            class="px-3.5 py-2 rounded-xl bg-white hover:bg-rose-50 text-rose-700 text-xs font-extrabold shadow-sm transition-colors">
                        Atender Alerta
                    </button>
                    <a href="{{ route('triage.alertas') }}"
                       class="px-3.5 py-2 rounded-xl bg-rose-900/60 hover:bg-rose-900 border border-white/25 text-white text-xs font-bold transition-colors">
                        Ver Panel Completo
                    </a>
                    <button type="button"
                            @click="silenciado = true"
                            aria-label="Minimizar alerta"
                            class="p-2 rounded-lg text-rose-200 hover:text-white hover:bg-white/10">
                        <x-icon name="x" class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
