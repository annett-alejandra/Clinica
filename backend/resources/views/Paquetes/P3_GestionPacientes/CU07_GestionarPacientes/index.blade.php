@extends('layouts.app')

@section('title', 'Directorio de Pacientes — Clínica Vida Salud')

@section('content')
<div class="container py-12"
     x-data="{
        busqueda: '{{ $termino ?? '' }}',
        filtroEstado: 'todos',
        ordenar: 'recientes',
        coincide(nombre, ci, correo, telefono, estado) {
            const term = this.busqueda.toLowerCase().trim();
            const estadoVal = (estado == '1' || estado === true || estado === 'true');
            
            // Filtro por estado
            if (this.filtroEstado === 'activos' && !estadoVal) return false;
            if (this.filtroEstado === 'inactivos' && estadoVal) return false;

            // Filtro por término
            if (!term) return true;
            return (nombre && nombre.toLowerCase().includes(term)) ||
                   (ci && ci.toLowerCase().includes(term)) ||
                   (correo && correo.toLowerCase().includes(term)) ||
                   (telefono && telefono.toLowerCase().includes(term));
        }
     }">

    <!-- Cabecera -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">GESTIÓN DE PACIENTES</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Directorio de Pacientes</h1>
            <p class="text-sm text-slate-500 mt-1">Control de filiaciones clínicas, unicidad de cédula (CI) y búsqueda avanzada en tiempo real.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-sky-50 text-[#0284C7] font-bold text-xs border border-sky-200/80">
                Total Registrados: {{ count($pacientes ?? []) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Formulario de Registro de Paciente -->
        <div class="lg:col-span-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm h-fit">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">Registrar Paciente</h2>
                <span class="text-[11px] text-slate-400 font-medium">Ficha Médica</span>
            </div>
            <form action="{{ url('/pacientes') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Cédula de Identidad (CI) *</label>
                    <input type="text" name="ci" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" placeholder="10203040" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nombre Completo *</label>
                    <input type="text" name="nombre_completo" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" placeholder="Nombres y Apellidos" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">F. Nacimiento *</label>
                        <input type="date" name="fecha_nacimiento" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Género *</label>
                        <select name="genero" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" required>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                            <option value="O">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Teléfono *</label>
                        <input type="text" name="telefono" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" placeholder="71234567" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Correo *</label>
                        <input type="email" name="correo" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" placeholder="paciente@mail.com" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Dirección</label>
                    <input type="text" name="direccion_domicilio" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none" placeholder="Zona, Calle #">
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    Guardar Ficha
                </button>
            </form>
        </div>

        <!-- Listado de Pacientes con Búsqueda y Filtros Dinámicos -->
        <div class="lg:col-span-8 space-y-4">
            
            <!-- Barra de Búsqueda y Filtro de Estados -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-3 items-center justify-between">
                
                <!-- Input de Búsqueda Dinámica -->
                <div class="relative w-full sm:flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                    </span>
                    <input type="text"
                           x-model="busqueda"
                           class="w-full pl-10 pr-8 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50 focus:bg-white focus:border-[#0284C7] focus:outline-none transition-all"
                           placeholder="Buscar por Nombre, CI, Teléfono o Correo (@gmail.com)...">
                    <button type="button" x-show="busqueda.length > 0" @click="busqueda = ''"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                        ×
                    </button>
                </div>

                <!-- Filtro por Estado -->
                <div class="flex items-center gap-1.5 w-full sm:w-auto shrink-0">
                    <button type="button"
                            @click="filtroEstado = 'todos'"
                            :class="filtroEstado === 'todos' ? 'bg-[#0284C7] text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                            class="px-3 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition-all">
                        Todos
                    </button>
                    <button type="button"
                            @click="filtroEstado = 'activos'"
                            :class="filtroEstado === 'activos' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                            class="px-3 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition-all">
                        Activos
                    </button>
                    <button type="button"
                            @click="filtroEstado = 'inactivos'"
                            :class="filtroEstado === 'inactivos' ? 'bg-slate-700 text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'"
                            class="px-3 py-2 rounded-lg text-xs font-semibold border border-slate-200 transition-all">
                        Inactivos
                    </button>
                </div>
            </div>

            <!-- Tabla de Pacientes -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4">CI / Nombre Completo</th>
                                <th class="px-6 py-4">Contacto</th>
                                <th class="px-6 py-4">Nacimiento</th>
                                <th class="px-6 py-4">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($pacientes ?? [] as $pac)
                                <tr x-show="coincide('{{ addslashes($pac->nombre_completo) }}', '{{ $pac->ci }}', '{{ $pac->correo }}', '{{ $pac->telefono }}', '{{ $pac->estado ? 1 : 0 }}')"
                                    class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-xs font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded border border-sky-100">
                                                CI: {{ $pac->ci }}
                                            </span>
                                            <span class="text-[10px] text-slate-400">#{{ $pac->id_paciente }}</span>
                                        </div>
                                        <strong class="text-slate-900 block mt-1 text-sm">{{ $pac->nombre_completo }}</strong>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-600">
                                        <div class="font-medium text-slate-700">{{ $pac->telefono ?? '—' }}</div>
                                        <div class="text-slate-400 hover:text-[#0284C7] truncate max-w-[200px]">
                                            @if($pac->correo)
                                                <a href="mailto:{{ $pac->correo }}" class="hover:underline">{{ $pac->correo }}</a>
                                            @else
                                                <span>—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-600">
                                        @if($pac->fecha_nacimiento)
                                            <span class="font-medium">{{ \Carbon\Carbon::parse($pac->fecha_nacimiento)->format('d/m/Y') }}</span>
                                            <span class="text-[10px] text-slate-400 block">
                                                ({{ \Carbon\Carbon::parse($pac->fecha_nacimiento)->age }} años)
                                            </span>
                                        @else
                                            <span>—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $pac->estado ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $pac->estado ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                            {{ $pac->estado ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                        No hay pacientes registrados en el sistema.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 text-xs text-slate-500 flex items-center justify-between">
                    <span>Ordenado por los más recientes primero</span>
                    <span class="font-semibold text-slate-700">Filtro instantáneo activo</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
