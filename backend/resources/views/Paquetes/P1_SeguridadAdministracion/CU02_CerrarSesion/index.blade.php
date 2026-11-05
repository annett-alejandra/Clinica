@extends('layouts.app')

@section('title', 'Sesión Finalizada — Clínica Vida Salud')

@section('content')
<div class="container py-20 max-w-md mx-auto text-center">
    <div class="w-16 h-16 bg-slate-100 text-slate-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
        ✓
    </div>
    <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">PAQUETE 01 · CU02</span>
    <h1 class="text-3xl font-serif text-slate-900 mt-2 mb-3">Sesión Cerrada con Éxito</h1>
    <p class="text-sm text-slate-500 mb-8">Has salido de tu cuenta de forma segura. El evento fue registrado en la bitácora de auditoría.</p>

    <a href="{{ url('/login') }}" class="btn-primary inline-flex justify-center">
        Volver a Iniciar Sesión
    </a>
</div>
@endsection

