@extends('layouts.app')

@section('title', 'Directorio de Pacientes — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">PAQUETE 03 · CU07</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Directorio de Pacientes</h1>
            <p class="text-sm text-slate-500 mt-1">Control de filiaciones clínicas, unicidad de cédula (CI) y datos de contacto.</p>
        </div>
    </div>

    @include('components.alerts')

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Registrar Paciente -->
        <div class="lg:col-span-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">Registrar Paciente</h2>
            <form action="{{ url('/pacientes') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Cédula de Identidad (CI) *</label>
                    <input type="text" name="ci" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="10203040" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nombre Completo *</label>
                    <input type="text" name="nombre_completo" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="Nombres y Apellidos" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">F. Nacimiento *</label>
                        <input type="date" name="fecha_nacimiento" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Género *</label>
                        <select name="genero" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" required>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                            <option value="O">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Teléfono *</label>
                        <input type="text" name="telefono" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="71234567" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Correo *</label>
                        <input type="email" name="correo" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="paciente@mail.com" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Dirección</label>
                    <input type="text" name="direccion_domicilio" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="Zona, Calle #">
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    Guardar Ficha
                </button>
            </form>
        </div>

        <!-- Listado de Pacientes -->
        <div class="lg:col-span-8 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">CI / Nombre</th>
                        <th class="px-6 py-4">Contacto</th>
                        <th class="px-6 py-4">Nacimiento</th>
                        <th class="px-6 py-4">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pacientes ?? [] as $pac)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs font-bold text-sky-700 block">CI: {{ $pac->ci }}</span>
                                <strong class="text-slate-900">{{ $pac->nombre_completo }}</strong>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                <div>{{ $pac->telefono }}</div>
                                <div class="text-slate-400">{{ $pac->correo }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ $pac->fecha_nacimiento }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold {{ $pac->estado ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $pac->estado ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400">No hay pacientes registrados en el sistema.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

