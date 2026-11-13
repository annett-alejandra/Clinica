@extends('layouts.app')

@section('title', 'Iniciar Sesión — Clínica Vida Salud')

@section('content')
<div class="py-12 px-4 max-w-xl mx-auto"
     x-data="{
         credencial: '{{ old('credencial', 'admin@clinica.com') }}',
         password: '{{ old('password', 'clinica123') }}',
         rolSeleccionado: 'Administrador',
         cargar(usuario, pass, rol) {
             this.credencial = usuario;
             this.password = pass;
             this.rolSeleccionado = rol;
         }
     }">
    <div class="text-center mb-8">
        <span class="text-xs font-bold uppercase tracking-widest text-[#0284C7]">SEGURIDAD Y ACCESO CLÍNICO</span>
        <h1 class="text-3xl md:text-4xl font-serif text-[#0F172A] mt-1">Iniciar Sesión en el Sistema</h1>
        <p class="text-sm text-slate-500 mt-1">Ingresa con tus credenciales según tu rol oficial (Administrador, Médico, Recepcionista o Paciente).</p>
    </div>

    <!-- Selector de Credenciales Rápidas para Demo / Evaluación -->
    <div class="mb-6 bg-slate-50 border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-600">
                1-Click: Cargar Cuenta Oficial de Prueba
            </span>
            <span class="text-[11px] font-bold text-[#0284C7] bg-sky-50 px-2 py-0.5 rounded-full" x-text="'Rol: ' + rolSeleccionado"></span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <button type="button"
                    @click="cargar('admin@clinica.com', 'clinica123', 'Administrador')"
                    :class="rolSeleccionado === 'Administrador' ? 'border-[#0284C7] bg-white text-[#0284C7] shadow-sm' : 'border-slate-200 bg-white text-slate-700'"
                    class="p-2.5 rounded-xl border text-left transition-all hover:border-[#0284C7]">
                <span class="text-[10px] font-bold uppercase text-amber-600 block">Admin</span>
                <span class="text-xs font-semibold block truncate">admin</span>
            </button>

            <button type="button"
                    @click="cargar('roberto.valenzuela@clinica.com', 'clinica123', 'Médico')"
                    :class="rolSeleccionado === 'Médico' ? 'border-[#0284C7] bg-white text-[#0284C7] shadow-sm' : 'border-slate-200 bg-white text-slate-700'"
                    class="p-2.5 rounded-xl border text-left transition-all hover:border-[#0284C7]">
                <span class="text-[10px] font-bold uppercase text-[#0284C7] block">Médico</span>
                <span class="text-xs font-semibold block truncate">dr_valenzuela</span>
            </button>

            <button type="button"
                    @click="cargar('recepcion@clinica.com', 'clinica123', 'Recepcionista')"
                    :class="rolSeleccionado === 'Recepcionista' ? 'border-[#0284C7] bg-white text-[#0284C7] shadow-sm' : 'border-slate-200 bg-white text-slate-700'"
                    class="p-2.5 rounded-xl border text-left transition-all hover:border-[#0284C7]">
                <span class="text-[10px] font-bold uppercase text-emerald-600 block">Recepción</span>
                <span class="text-xs font-semibold block truncate">recepcion</span>
            </button>

            <button type="button"
                    @click="cargar('fernando.justiniano@mail.com', 'clinica123', 'Paciente')"
                    :class="rolSeleccionado === 'Paciente' ? 'border-[#0284C7] bg-white text-[#0284C7] shadow-sm' : 'border-slate-200 bg-white text-slate-700'"
                    class="p-2.5 rounded-xl border text-left transition-all hover:border-[#0284C7]">
                <span class="text-[10px] font-bold uppercase text-indigo-600 block">Paciente</span>
                <span class="text-xs font-semibold block truncate">paciente_fer</span>
            </button>
        </div>
    </div>

    <!-- Formulario Oficial de Autenticación CU01 -->
    <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/90 shadow-sm">
        <form action="{{ url('/login') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Usuario o Correo Electrónico *
                </label>
                <div class="relative">
                    <input type="text"
                           name="credencial"
                           x-model="credencial"
                           class="w-full text-sm border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none"
                           placeholder="admin@clinica.com o usuario" required autofocus>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Contraseña *
                </label>
                <input type="password"
                       name="password"
                       x-model="password"
                       class="w-full text-sm border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none"
                       placeholder="••••••••" required>
            </div>

            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500">Contraseña demo: <strong class="text-slate-700">clinica123</strong></span>
                <a href="{{ url('/registro-paciente') }}" class="font-semibold text-[#0284C7] hover:underline">
                    ¿Nuevo paciente? Registrarse
                </a>
            </div>

            <button type="submit" class="w-full bg-[#0284C7] hover:bg-[#0369A1] text-white font-semibold py-3.5 rounded-xl shadow-md shadow-[#0284C7]/20 transition-all text-sm flex items-center justify-center space-x-2">
                <x-icon name="log-in" class="w-4 h-4" />
                <span>Ingresar al Sistema Clínico</span>
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
            <span>Autenticación segura con Hash Bcrypt</span>
            <a href="{{ route('inicio') }}" class="text-slate-600 hover:text-slate-900 font-medium">← Volver a la portada</a>
        </div>
    </div>
</div>
@endsection
