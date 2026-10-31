@extends('layouts.app')

@section('title', 'Gestión de Usuarios y Roles — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">PAQUETE 01 · CU03</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Gestión de Usuarios y Roles</h1>
            <p class="text-sm text-slate-500 mt-1">Administración de credenciales, roles institucionales y estados de acceso.</p>
        </div>
        <a href="#nuevo-usuario" class="btn-primary inline-flex items-center gap-2">
            <span>+ Nuevo Usuario</span>
        </a>
    </div>

    <!-- Tabla Editorial de Usuarios -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Nombre de Usuario</th>
                        <th class="px-6 py-4">Correo Institucional</th>
                        <th class="px-6 py-4">Rol Asignado</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($usuarios ?? [] as $usuario)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-slate-400">#{{ $usuario->id_usuario }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-900">{{ $usuario->nombre_usuario }}</td>
                            <td class="px-6 py-4">{{ $usuario->correo }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2.5 py-1 rounded text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-100">
                                    {{ $usuario->rol?->nombre_rol ?? 'Sin rol' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $usuario->estado ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $usuario->estado ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $usuario->estado ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" class="text-xs text-slate-600 hover:text-sky-600 font-medium">Modificar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                No se encontraron usuarios registrados en la base de datos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
