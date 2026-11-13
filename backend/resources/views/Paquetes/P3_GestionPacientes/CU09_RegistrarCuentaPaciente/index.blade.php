@extends('layouts.app')

@section('title', 'Registro de Cuenta Paciente — Clínica Vida Salud')

@section('content')
<div class="container py-16 max-w-xl mx-auto">
    <div class="text-center mb-8">
        <span class="text-xs font-mono font-semibold uppercase tracking-widest text-emerald-600">REGISTRO DE PACIENTE</span>
        <h1 class="text-3xl font-serif text-slate-900 mt-1">Auto-registro de Pacientes</h1>
        <p class="text-sm text-slate-500 mt-1">Crea tu cuenta web para agendar citas y consultar tu historial médico.</p>
    </div>

    @include('components.alerts')

    <div class="bg-white p-6 md:p-8 rounded-xl border border-slate-200 shadow-sm">
        <form action="{{ url('/registro-paciente') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Cédula de Identidad (CI) *</label>
                <input type="text" name="ci" value="{{ old('ci') }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="10203040" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nombre Completo *</label>
                <input type="text" name="nombre_completo" value="{{ old('nombre_completo') }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="Nombres y Apellidos" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">F. Nacimiento *</label>
                    <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" required>
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
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Teléfono / Celular *</label>
                    <input type="text" name="telefono" value="{{ old('telefono') }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="71234567" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Correo Electrónico *</label>
                    <input type="email" name="correo" value="{{ old('correo') }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="tu@correo.com" required>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nombre de Usuario *</label>
                    <input type="text" name="nombre_usuario" value="{{ old('nombre_usuario') }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="usuario_paciente" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Contraseña *</label>
                    <input type="password" name="password" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:outline-none" placeholder="Mínimo 6 caracteres" required>
                </div>
            </div>

            <button type="submit" class="btn-primary w-full justify-center py-2.5 mt-2">
                Crear Cuenta de Paciente
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-500">
            ¿Ya tienes una cuenta creada?
            <a href="{{ url('/login') }}" class="font-semibold text-sky-600 hover:text-sky-800">
                Inicia sesión aquí 
            </a>
        </div>
    </div>
</div>
@endsection

