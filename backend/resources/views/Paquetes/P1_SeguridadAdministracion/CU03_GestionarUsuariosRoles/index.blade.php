@extends('layouts.app')

@section('title', 'Gestión de Usuarios y Roles — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-[#0284C7] block">SEGURIDAD Y CONTROL DE ACCESO</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Gestión de Usuarios y Roles</h1>
            <p class="text-sm text-slate-500 mt-1">Administración de cuentas de usuario clasificadas por categorías, credenciales y estados de acceso.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('portal') }}" class="px-3.5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold text-xs flex items-center gap-1.5 transition-colors">
                <span>←</span>
                <span>Volver al Portal</span>
            </a>
            <a href="{{ route('auditoria') }}" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs flex items-center gap-1.5 shadow-sm transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
                </svg>
                <span>Auditoría de Seguridad</span>
            </a>
        </div>
    </div>

    <!-- Pestañas de Categorías por Rol -->
    <div class="flex flex-wrap items-center gap-2 mb-6">
        @php
            $qParam = $busqueda ? '&q='.$busqueda : '';
        @endphp
        <a href="{{ url('/usuarios?rol=todos'.$qParam) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-semibold border transition-all {{ ($rolFiltro === 'todos' || !$rolFiltro) ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            <span>Todos</span>
            <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ ($rolFiltro === 'todos' || !$rolFiltro) ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                {{ $conteos['total'] }}
            </span>
        </a>

        <a href="{{ url('/usuarios?rol=Administrador'.$qParam) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-semibold border transition-all {{ $rolFiltro === 'Administrador' ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            <span>Administradores</span>
            <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ $rolFiltro === 'Administrador' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                {{ $conteos['admin'] }}
            </span>
        </a>

        <a href="{{ url('/usuarios?rol=Médico'.$qParam) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-semibold border transition-all {{ $rolFiltro === 'Médico' ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            <span>Médicos</span>
            <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ $rolFiltro === 'Médico' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                {{ $conteos['medico'] }}
            </span>
        </a>

        <a href="{{ url('/usuarios?rol=Recepcionista'.$qParam) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-semibold border transition-all {{ $rolFiltro === 'Recepcionista' ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            <span>Recepcionistas</span>
            <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ $rolFiltro === 'Recepcionista' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                {{ $conteos['recepcion'] }}
            </span>
        </a>

        <a href="{{ url('/usuarios?rol=Paciente'.$qParam) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-semibold border transition-all {{ $rolFiltro === 'Paciente' ? 'bg-[#0284C7] text-white border-[#0284C7] shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            <span>Pacientes</span>
            <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ $rolFiltro === 'Paciente' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                {{ $conteos['paciente'] }}
            </span>
        </a>
    </div>

    <!-- Barra de Búsqueda Dinámica -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm mb-6 flex flex-col sm:flex-row gap-3 items-center justify-between">
        <form action="{{ url('/usuarios') }}" method="GET" class="flex-1 flex gap-2 w-full">
            <input type="hidden" name="rol" value="{{ $rolFiltro }}">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                </span>
                <input type="text" name="q" value="{{ $busqueda }}"
                       class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50 focus:bg-white focus:border-[#0284C7] focus:outline-none"
                       placeholder="Buscar por nombre de usuario o correo (@gmail.com)...">
            </div>
            <button type="submit" class="btn-primary px-5 py-2.5 text-xs font-semibold">
                Buscar
            </button>
            @if($busqueda)
                <a href="{{ url('/usuarios?rol='.$rolFiltro) }}" class="px-3.5 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-500 text-xs flex items-center">
                    Limpiar
                </a>
            @endif
        </form>

        <span class="text-xs text-slate-400 font-medium shrink-0">
            Mostrando {{ $usuarios->firstItem() ?? 0 }} - {{ $usuarios->lastItem() ?? 0 }} de {{ $usuarios->total() }} usuarios
        </span>
    </div>

    <!-- Tabla Editorial de Usuarios -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Nombre de Usuario</th>
                        <th class="px-6 py-4">Correo Electrónico</th>
                        <th class="px-6 py-4">Rol Asignado</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Vínculo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($usuarios as $usuario)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-slate-400">#{{ $usuario->id_usuario }}</td>
                            <td class="px-6 py-4">
                                <strong class="text-slate-900 block font-semibold">{{ $usuario->nombre_usuario }}</strong>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-slate-600">
                                <a href="mailto:{{ $usuario->correo }}" class="hover:text-[#0284C7] hover:underline">{{ $usuario->correo }}</a>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $rolNombre = $usuario->rol?->nombre_rol ?? 'Sin rol';
                                    $rolClase = match($rolNombre) {
                                        'Administrador' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'Médico' => 'bg-sky-50 text-[#0284C7] border-sky-200',
                                        'Recepcionista' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'Paciente' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        default => 'bg-slate-50 text-slate-600 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold border {{ $rolClase }}">
                                    {{ $rolNombre }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $usuario->estado ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $usuario->estado ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $usuario->estado ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-xs text-slate-500 font-mono">
                                @if($usuario->id_paciente)
                                    <span class="bg-slate-100 px-2 py-0.5 rounded text-[10px]">Pac: #{{ $usuario->id_paciente }}</span>
                                @elseif($usuario->id_medico)
                                    <span class="bg-sky-50 text-[#0284C7] px-2 py-0.5 rounded text-[10px]">Med: #{{ $usuario->id_medico }}</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                No se encontraron usuarios para los criterios seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación con Anterior y Siguiente -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
            <div>
                Página <strong class="text-slate-900">{{ $usuarios->currentPage() }}</strong> de <strong class="text-slate-900">{{ $usuarios->lastPage() }}</strong>
            </div>

            <div class="flex items-center gap-2">
                @if($usuarios->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-400 cursor-not-allowed bg-white">
                        ← Anterior
                    </span>
                @else
                    <a href="{{ $usuarios->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 font-semibold bg-white transition-colors">
                        ← Anterior
                    </a>
                @endif

                @if($usuarios->hasMorePages())
                    <a href="{{ $usuarios->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 font-semibold bg-white transition-colors">
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
