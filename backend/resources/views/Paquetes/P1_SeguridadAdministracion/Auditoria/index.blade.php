@extends('layouts.app')

@section('title', 'Auditoría y Seguridad del Sistema — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-[#0284C7] block">SEGURIDAD Y COMPLIANCE MÉDICO</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Registro Inmutable de Auditoría</h1>
            <p class="text-sm text-slate-500 mt-1">Trazabilidad completa de operaciones clínicas, inicios de sesión, cambios de estados y gestión de datos.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('portal') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-xs flex items-center gap-1.5 transition-colors">
                <span>←</span>
                <span>Volver al Portal</span>
            </a>
            <a href="{{ url('/usuarios') }}" class="btn-primary py-2 text-xs">
                Gestionar Usuarios
            </a>
        </div>
    </div>

    <!-- Filtros de Auditoría -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6">
        <form action="{{ route('auditoria') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
            <!-- Búsqueda General -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Búsqueda General</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                    </span>
                    <input type="text" name="q" value="{{ $busqueda }}"
                           class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50 focus:bg-white focus:border-[#0284C7] focus:outline-none"
                           placeholder="Buscar por usuario, correo, tabla o acción...">
                </div>
            </div>

            <!-- Filtro por Acción -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Acción Realizada</label>
                <select name="accion" class="w-full text-xs font-medium border-slate-200 rounded-xl p-2 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none">
                    <option value="todas">Todas las acciones</option>
                    @foreach($acciones as $act)
                        <option value="{{ $act }}" {{ ($accion == $act) ? 'selected' : '' }}>
                            {{ $act }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro por Tabla Afectada -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tabla Afectada</label>
                <select name="tabla" class="w-full text-xs font-medium border-slate-200 rounded-xl p-2 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none">
                    <option value="todas">Todas las tablas</option>
                    @foreach($tablas as $tbl)
                        <option value="{{ $tbl }}" {{ ($tabla == $tbl) ? 'selected' : '' }}>
                            {{ $tbl }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-4 flex items-center justify-between pt-2 border-t border-slate-100">
                <span class="text-xs text-slate-400">
                    Total Registros: <strong class="text-slate-700">{{ $auditorias->total() }} eventos de auditoría</strong>
                </span>
                <div class="flex items-center gap-2">
                    @if($busqueda || ($accion && $accion !== 'todas') || ($tabla && $tabla !== 'todas'))
                        <a href="{{ route('auditoria') }}" class="px-3.5 py-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100 text-xs font-medium">
                            Limpiar Filtros
                        </a>
                    @endif
                    <button type="submit" class="btn-primary py-1.5 px-4 text-xs">
                        Aplicar Filtros
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabla Inmutable de Auditoría -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5">ID / Fecha y Hora</th>
                        <th class="px-5 py-3.5">Operador / Usuario</th>
                        <th class="px-5 py-3.5">Acción</th>
                        <th class="px-5 py-3.5">Tabla / Registro</th>
                        <th class="px-5 py-3.5">Metadatos / Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($auditorias as $aud)
                        @php
                            $colorAccion = match(true) {
                                str_contains($aud->accion, 'LOGIN') => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                str_contains($aud->accion, 'PASSWORD') => 'bg-purple-50 text-purple-800 border-purple-200',
                                str_contains($aud->accion, 'CANCELAR') => 'bg-rose-50 text-rose-800 border-rose-200',
                                str_contains($aud->accion, 'REPROGRAMAR') => 'bg-amber-50 text-amber-800 border-amber-200',
                                str_contains($aud->accion, 'ATENDER') => 'bg-sky-50 text-sky-800 border-sky-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="font-mono text-[10px] text-slate-400 block">#{{ $aud->id_auditoria }}</span>
                                <strong class="text-slate-800 font-medium">
                                    {{ \Carbon\Carbon::parse($aud->fecha_hora)->format('d/m/Y') }}
                                </strong>
                                <span class="text-slate-400 text-[11px] block">
                                    {{ \Carbon\Carbon::parse($aud->fecha_hora)->format('H:i:s') }}
                                </span>
                            </td>

                            <td class="px-5 py-3.5">
                                @if($aud->usuario)
                                    <strong class="text-slate-900 block font-semibold">{{ $aud->usuario->nombre_usuario }}</strong>
                                    <span class="text-[11px] text-slate-500">{{ $aud->usuario->correo }}</span>
                                @else
                                    <span class="text-slate-400 italic">Sistema Clínico / Anónimo</span>
                                @endif
                            </td>

                            <td class="px-5 py-3.5">
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider border {{ $colorAccion }}">
                                    {{ $aud->accion }}
                                </span>
                            </td>

                            <td class="px-5 py-3.5 font-mono text-[11px]">
                                <span class="text-slate-700 font-semibold block">{{ $aud->tabla_afectada }}</span>
                                <span class="text-slate-400">ID Reg: {{ $aud->id_registro_afectado ?? '—' }}</span>
                            </td>

                            <td class="px-5 py-3.5 max-w-xs">
                                @if($aud->detalle)
                                    <div class="bg-slate-50 p-2 rounded-lg border border-slate-200 font-mono text-[10px] text-slate-600 truncate max-w-sm"
                                         title="{{ json_encode($aud->detalle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}">
                                        {{ json_encode($aud->detalle, JSON_UNESCAPED_UNICODE) }}
                                    </div>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">
                                No se encontraron registros de auditoría para los criterios seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación con Anterior y Siguiente -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
            <div>
                Página <strong class="text-slate-900">{{ $auditorias->currentPage() }}</strong> de <strong class="text-slate-900">{{ $auditorias->lastPage() }}</strong>
            </div>

            <div class="flex items-center gap-2">
                @if($auditorias->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-400 cursor-not-allowed bg-white">
                        ← Anterior
                    </span>
                @else
                    <a href="{{ $auditorias->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 font-semibold bg-white transition-colors">
                        ← Anterior
                    </a>
                @endif

                @if($auditorias->hasMorePages())
                    <a href="{{ $auditorias->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 font-semibold bg-white transition-colors">
                        Siguiente →
                    </a>
                @else
                    <span class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-400 cursor-not-allowed bg-white">
                        Siguiente →
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
