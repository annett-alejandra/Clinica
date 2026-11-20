@props([
    'analisis' => [],
    'paciente' => null,
    'titulo' => 'Análisis Clínicos y Estudios Realizados',
    'subtitulo' => 'Resultados verificados de laboratorio clínico, química sanguínea e imagenología.',
])

@php
    $listaAnalisis = is_array($analisis) ? $analisis : [];
    $categorias = collect($listaAnalisis)->pluck('categoria')->filter()->unique()->values()->all();
    $componentId = 'analisis-data-' . ($paciente?->id_paciente ?? '0') . '-' . substr(md5($titulo), 0, 6);
@endphp

<div x-data="{
        categoriaActiva: 'todas',
        seleccionado: null,
        items: [],
        init() {
            const el = document.getElementById('{{ $componentId }}');
            if (el) {
                try { this.items = JSON.parse(el.textContent || '[]'); } catch (e) { this.items = []; }
            }
        },
        get filtrados() {
            if (this.categoriaActiva === 'todas') return this.items;
            return this.items.filter(a => a.categoria === this.categoriaActiva);
        },
        abrirInforme(item) {
            this.seleccionado = item;
        },
        cerrarInforme() {
            this.seleccionado = null;
        }
     }"
     class="space-y-5">

    <script type="application/json" id="{{ $componentId }}">@json($listaAnalisis)</script>

    <!-- Cabecera y Filtros por Categoría -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-sky-50 text-[#0284C7] border border-sky-200 flex items-center justify-center shrink-0">
                    <x-icon name="activity" class="w-4 h-4" />
                </span>
                <span>{{ $titulo }}</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ $subtitulo }}</p>
        </div>

        @if(count($categorias) > 0)
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                <button type="button"
                        @click="categoriaActiva = 'todas'"
                        :class="categoriaActiva === 'todas' ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                        class="px-3 py-1.5 rounded-lg border font-semibold transition-all">
                    Todos ({{ count($listaAnalisis) }})
                </button>
                @foreach($categorias as $cat)
                    <button type="button"
                            data-cat="{{ $cat }}"
                            @click="categoriaActiva = $el.dataset.cat"
                            :class="categoriaActiva === $el.dataset.cat ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                            class="px-3 py-1.5 rounded-lg border font-semibold transition-all">
                        {{ $cat }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Grilla de Tarjetas de Análisis -->
    @if(count($listaAnalisis) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="item in filtrados" :key="item.codigo">
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-sky-200 transition-all flex flex-col justify-between gap-4">
                    <div class="space-y-3">
                        <!-- Top badges -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-sky-50 text-[#0284C7] border border-sky-200/80" x-text="item.categoria"></span>
                                    <span class="font-mono text-[10px] text-slate-400" x-text="item.codigo"></span>
                                </div>
                                <h4 class="text-sm sm:text-base font-bold text-slate-900 leading-snug" x-text="item.titulo"></h4>
                            </div>

                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider shrink-0 border"
                                  :class="item.tipo_badge === 'atencion'
                                      ? 'bg-amber-50 text-amber-800 border-amber-200'
                                      : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                  x-text="item.estado_general"></span>
                        </div>

                        <!-- Metadatos -->
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
                            <span>Fecha: <strong class="text-slate-700" x-text="item.fecha"></strong></span>
                            <span>Solicitó: <strong class="text-slate-700" x-text="item.medico_solicitante"></strong></span>
                        </div>

                        <!-- Resumen clínico -->
                        <p class="text-xs text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-100" x-text="item.resumen"></p>

                        <!-- Vista rápida de los primeros 3 parámetros -->
                        <div class="space-y-1">
                            <template x-for="(p, idx) in (item.parametros || []).slice(0, 3)" :key="idx">
                                <div class="flex items-center justify-between text-xs py-1 border-b border-slate-100 last:border-0">
                                    <span class="text-slate-600" x-text="p.nombre"></span>
                                    <span class="font-mono font-bold flex items-center gap-1.5"
                                          :class="p.estado === 'atencion' ? 'text-amber-700' : 'text-slate-800'">
                                        <span x-text="p.valor + (p.unidad && p.unidad !== '—' ? ' ' + p.unidad : '')"></span>
                                        <span class="w-1.5 h-1.5 rounded-full"
                                              :class="p.estado === 'atencion' ? 'bg-amber-500' : 'bg-emerald-500'"></span>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <span class="text-[11px] text-slate-400" x-text="(item.parametros || []).length + ' parámetros medidos'"></span>
                        <button type="button"
                                @click="abrirInforme(item)"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-sky-50 hover:bg-[#0284C7] text-[#0284C7] hover:text-white border border-sky-200 text-xs font-bold transition-colors">
                            <x-icon name="file-text" class="w-3.5 h-3.5" />
                            <span>Ver Informe Completo</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center text-slate-400 text-sm">
            No existen análisis clínicos registrados todavía para este paciente.
        </div>
    @endif

    <!-- Modal de Informe Clínico Detallado -->
    <div x-show="seleccionado !== null"
         x-cloak
         x-transition.opacity.duration.150ms
         @keydown.escape.window="cerrarInforme()"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
        <div class="bg-white border border-slate-200 max-w-2xl w-full rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col"
             @click.outside="cerrarInforme()">
            <!-- Cabecera del Modal -->
            <div class="px-6 py-4 bg-slate-900 text-white flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-sky-500/20 text-sky-300 border border-sky-400/30"
                              x-text="seleccionado?.categoria"></span>
                        <span class="font-mono text-[11px] text-slate-400" x-text="seleccionado?.codigo"></span>
                    </div>
                    <h4 class="text-lg font-bold leading-snug" x-text="seleccionado?.titulo"></h4>
                    <p class="text-xs text-slate-300 mt-0.5" x-text="seleccionado?.laboratorio"></p>
                </div>
                <button type="button"
                        @click="cerrarInforme()"
                        aria-label="Cerrar informe"
                        class="p-1.5 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800 transition-colors print:hidden">
                    <x-icon name="x" class="w-5 h-5" />
                </button>
            </div>

            <!-- Cuerpo del Informe -->
            <div class="p-6 overflow-y-auto space-y-5">
                <!-- Datos de cabecera del paciente y orden -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Paciente</span>
                        <strong class="text-slate-800">{{ $paciente?->nombre_completo ?? 'Paciente' }}</strong>
                        @if($paciente?->ci)
                            <span class="block font-mono text-[11px] text-slate-500">CI: {{ $paciente->ci }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Médico Solicitante</span>
                        <strong class="text-slate-800" x-text="seleccionado?.medico_solicitante"></strong>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Fecha de Emisión</span>
                        <strong class="text-slate-800" x-text="seleccionado?.fecha"></strong>
                        <span class="mt-0.5 inline-block px-2 py-0.5 rounded-full text-[10px] font-bold"
                              :class="seleccionado?.tipo_badge === 'atencion' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700'"
                              x-text="seleccionado?.estado_general"></span>
                    </div>
                </div>

                <!-- Tabla de Parámetros Clínicos -->
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100 text-slate-600 uppercase text-[10px] tracking-wider">
                                <th class="py-2.5 px-3.5 font-bold">Parámetro / Estudio</th>
                                <th class="py-2.5 px-3.5 font-bold">Resultado</th>
                                <th class="py-2.5 px-3.5 font-bold">Rango de Referencia</th>
                                <th class="py-2.5 px-3.5 font-bold text-right">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(p, i) in (seleccionado?.parametros || [])" :key="i">
                                <tr class="hover:bg-slate-50/80">
                                    <td class="py-2.5 px-3.5 font-semibold text-slate-800" x-text="p.nombre"></td>
                                    <td class="py-2.5 px-3.5 font-mono font-bold"
                                        :class="p.estado === 'atencion' ? 'text-amber-700' : 'text-slate-900'">
                                        <span x-text="p.valor"></span>
                                        <span class="text-[11px] font-normal text-slate-500" x-show="p.unidad && p.unidad !== '—'" x-text="' ' + p.unidad"></span>
                                    </td>
                                    <td class="py-2.5 px-3.5 font-mono text-slate-500" x-text="p.referencia"></td>
                                    <td class="py-2.5 px-3.5 text-right">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                              :class="p.estado === 'atencion' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                                              x-text="p.estado === 'atencion' ? 'Atención' : 'Normal'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Interpretación clínica -->
                <div class="p-4 rounded-xl bg-sky-50/70 border border-sky-200 text-xs space-y-1">
                    <p class="font-bold text-sky-900 uppercase tracking-wider text-[10px]">Observación e Interpretación Clínica</p>
                    <p class="text-slate-700 leading-relaxed" x-text="seleccionado?.observacion_medica"></p>
                </div>
            </div>

            <!-- Pie del Modal -->
            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3 print:hidden">
                <span class="text-[11px] text-slate-400">Documento clínico verificado · Clínica Vida Salud</span>
                <div class="flex items-center gap-2">
                    <button type="button"
                            onclick="window.print()"
                            class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-colors">
                        Imprimir Resultado
                    </button>
                    <button type="button"
                            @click="cerrarInforme()"
                            class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
