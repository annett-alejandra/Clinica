@extends('layouts.app')

@section('title', 'Iniciar Sesión (CU01) — Clínica Vida Salud')

@section('content')
<div class="py-12 px-4 max-w-xl mx-auto">
    <div class="text-center mb-8">
        <span class="text-xs font-bold uppercase tracking-widest text-[#0284C7]">PAQUETE 01 · CU01 SEGURIDAD CLÍNICA</span>
        <h1 class="text-3xl md:text-4xl font-serif text-[#0F172A] mt-1">Acceso al Sistema Hospitalario</h1>
        <p class="text-sm text-slate-500 mt-1">Ingresa tus credenciales institucionales o selecciona un perfil operativo del portal.</p>
    </div>

    <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/90 shadow-sm">
        <form action="{{ url('/login') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Usuario o Correo Electrónico *</label>
                <input type="text" name="credencial" value="{{ old('credencial') }}"
                       class="w-full text-sm border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none"
                       placeholder="correo@clinicavidasalud.bo o usuario" required autofocus>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Contraseña *</label>
                <input type="password" name="password"
                       class="w-full text-sm border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none"
                       placeholder="••••••••" required>
            </div>

            <button type="submit" class="w-full bg-[#0284C7] hover:bg-[#0369A1] text-white font-semibold py-3 rounded-xl shadow-md shadow-[#0284C7]/20 transition-all text-sm">
                Iniciar Sesión Oficial (CU01)
            </button>
        </form>

        <!-- Acceso directo por rol al Portal Clínico -->
        <div class="mt-6 pt-6 border-t border-slate-200/80">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-3 text-center">
                O accede directamente a la consola por perfil:
            </span>
            <div class="grid grid-cols-2 gap-2.5">
                <a href="{{ route('portal', ['rol' => 'medico']) }}"
                   class="flex items-center space-x-2 p-2.5 rounded-xl border border-slate-200 hover:border-[#0284C7] hover:bg-sky-50/50 text-xs font-semibold text-slate-700 transition-all">
                    <x-icon name="stethoscope" class="w-4 h-4 text-[#0284C7]" />
                    <span>Médico Especialista</span>
                </a>
                <a href="{{ route('portal', ['rol' => 'enfermeria']) }}"
                   class="flex items-center space-x-2 p-2.5 rounded-xl border border-slate-200 hover:border-[#0284C7] hover:bg-sky-50/50 text-xs font-semibold text-slate-700 transition-all">
                    <x-icon name="activity" class="w-4 h-4 text-emerald-600" />
                    <span>Enfermería / Recepción</span>
                </a>
                <a href="{{ route('portal', ['rol' => 'paciente']) }}"
                   class="flex items-center space-x-2 p-2.5 rounded-xl border border-slate-200 hover:border-[#0284C7] hover:bg-sky-50/50 text-xs font-semibold text-slate-700 transition-all">
                    <x-icon name="users" class="w-4 h-4 text-indigo-600" />
                    <span>Portal Paciente</span>
                </a>
                <a href="{{ route('portal', ['rol' => 'administrador']) }}"
                   class="flex items-center space-x-2 p-2.5 rounded-xl border border-slate-200 hover:border-[#0284C7] hover:bg-sky-50/50 text-xs font-semibold text-slate-700 transition-all">
                    <x-icon name="shield-check" class="w-4 h-4 text-amber-600" />
                    <span>Dirección / Admin</span>
                </a>
            </div>
        </div>

        <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-slate-500">
            ¿Eres paciente nuevo?
            <a href="{{ url('/registro-paciente') }}" class="font-semibold text-[#0284C7] hover:underline">
                Regístrate aquí en línea (CU09)
            </a>
        </div>
    </div>
</div>
@endsection
