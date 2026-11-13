@extends('layouts.app')

@section('title', 'Especialidades Médicas — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">ESPECIALIDADES MÉDICAS</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Especialidades Médicas</h1>
            <p class="text-sm text-slate-500 mt-1">Catálogo de ramas de atención clínica, aranceles y estado de disponibilidad.</p>
        </div>
    </div>

    @include('components.alerts')

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Formulario de Registro -->
        <div class="lg:col-span-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">Nueva Especialidad</h2>
            <form action="{{ url('/especialidades') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nombre Especialidad *</label>
                    <input type="text" name="nombre_especialidad" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="Ej: Cardiología" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Costo de Referencia (Bs.) *</label>
                    <input type="number" step="0.01" min="0" name="costo_referencia" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="150.00" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Descripción</label>
                    <textarea name="descripcion" rows="3" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="Alcance o indicaciones..."></textarea>
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    Guardar Especialidad
                </button>
            </form>
        </div>

        <!-- Listado de Especialidades -->
        <div class="lg:col-span-8 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Especialidad</th>
                        <th class="px-6 py-4">Descripción</th>
                        <th class="px-6 py-4">Costo Ref.</th>
                        <th class="px-6 py-4">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($especialidades ?? [] as $esp)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900">{{ $esp->nombre_especialidad }}</td>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ $esp->descripcion ?: 'Sin descripción' }}</td>
                            <td class="px-6 py-4 font-mono font-semibold text-sky-700">Bs. {{ number_format($esp->costo_referencia, 2) }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold {{ $esp->estado ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $esp->estado ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400">No hay especialidades registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
@endsection

