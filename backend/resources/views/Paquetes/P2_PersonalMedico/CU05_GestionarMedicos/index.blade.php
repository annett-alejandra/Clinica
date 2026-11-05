@extends('layouts.app')

@section('title', 'Cuerpo Médico — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">PAQUETE 02 · CU05</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Gestión de Personal Médico</h1>
            <p class="text-sm text-slate-500 mt-1">Registro de facultativos, matrícula profesional, consultorios y especialidades adscritas.</p>
        </div>
    </div>

    @include('components.alerts')

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Registro de Médico -->
        <div class="lg:col-span-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100">Registrar Médico</h2>
            <form action="{{ url('/medicos') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nombre Completo *</label>
                    <input type="text" name="nombre_completo" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="Dr(a). Nombre y Apellido" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Matrícula / Reg. Profesional *</label>
                    <input type="text" name="registro_profesional" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="MED-12345" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Consultorio *</label>
                        <input type="text" name="numero_consultorio" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="Cons. 204" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Teléfono *</label>
                        <input type="text" name="telefono" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="70012345" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Correo Institucional *</label>
                    <input type="email" name="correo" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="doctor@clinica.com" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Especialidades Asignadas *</label>
                    <select name="especialidades[]" multiple class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none h-24" required>
                        @foreach($especialidades ?? [] as $esp)
                            <option value="{{ $esp->id_especialidad }}">{{ $esp->nombre_especialidad }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    Guardar Médico
                </button>
            </form>
        </div>

        <!-- Directorio Médico -->
        <div class="lg:col-span-8 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="w-full text-left text-sm text-slate-700">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Médico</th>
                        <th class="px-6 py-4">Consultorio / Reg.</th>
                        <th class="px-6 py-4">Especialidades</th>
                        <th class="px-6 py-4">Contacto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($medicos ?? [] as $m)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <strong class="text-slate-900 block">{{ $m->nombre_completo }}</strong>
                                <span class="text-xs text-slate-400">ID #{{ $m->id_medico }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-800 block">{{ $m->numero_consultorio }}</span>
                                <span class="text-xs font-mono text-slate-500">{{ $m->registro_profesional }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($m->especialidades ?? [] as $e)
                                        <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-sky-50 text-sky-700 border border-sky-100">
                                            {{ $e->nombre_especialidad }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400">Sin asignar</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                <div>{{ $m->telefono }}</div>
                                <div class="text-slate-400">{{ $m->correo }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400">No hay médicos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
