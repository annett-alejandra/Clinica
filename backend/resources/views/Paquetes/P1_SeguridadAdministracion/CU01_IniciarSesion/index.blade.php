@extends('layouts.app')

@section('title', 'Iniciar Sesión — Clínica Vida Salud')

@section('content')
<div class="container py-16 max-w-md mx-auto">
    <div class="text-center mb-8">
        <span class="text-xs font-mono font-semibold uppercase tracking-widest text-sky-600">PAQUETE 01 · CU01</span>
        <h1 class="text-3xl font-serif text-slate-900 mt-1">Acceso al Sistema</h1>
        <p class="text-sm text-slate-500 mt-1">Ingresa tus credenciales institucionales o de paciente.</p>
    </div>

    @include('components.alerts')

    <div class="bg-white p-6 md:p-8 rounded-xl border border-slate-200 shadow-sm">
        <form action="{{ url('/login') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Usuario o Correo *</label>
                <input type="text" name="credencial" value="{{ old('credencial') }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" placeholder="correo@clinica.com o usuario" required autofocus>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Contraseña *</label>
                <input type="password" name="password" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-primary w-full justify-center py-2.5 text-sm">
                Iniciar Sesión
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-500">
            ¿Eres paciente nuevo?
            <a href="{{ url('/registro-paciente') }}" class="font-semibold text-sky-600 hover:text-sky-800">
                Regístrate aquí (CU09)
            </a>
        </div>
    </div>
</div>
@endsection
