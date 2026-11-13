@extends('layouts.app')

@section('title', 'Mi Perfil de Paciente — Clínica Vida Salud')

@section('content')
<div class="container py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 mb-8 border-b border-slate-200 gap-4">
        <div>
            <span class="text-xs font-mono font-semibold uppercase tracking-widest text-slate-500">PERFIL DEL PACIENTE</span>
            <h1 class="text-3xl font-serif text-slate-900 mt-1">Mi Perfil de Paciente</h1>
            <p class="text-sm text-slate-500 mt-1">Mantén actualizada tu información de contacto y números de emergencia sin necesidad de acudir a recepción.</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
            Ficha Médica Activa
        </span>
    </div>

    @include('components.alerts')

    <div class="max-w-3xl mx-auto bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden p-6 md:p-8">
        @if(isset($paciente))
            <!-- Ficha de Datos Inmutables -->
            <div class="bg-slate-50 border border-slate-100 rounded-lg p-5 mb-8">
                <h3 class="text-xs font-mono font-semibold uppercase tracking-wider text-slate-500 mb-3">Datos de Identidad Registrados</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-xs text-slate-400 block">Nombre Completo</span>
                        <strong class="text-slate-800">{{ $paciente->nombre_completo }}</strong>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Cédula (CI)</span>
                        <strong class="text-slate-800 font-mono">{{ $paciente->ci }}</strong>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Fecha de Nacimiento</span>
                        <span class="text-slate-700">{{ $paciente->fecha_nacimiento }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Género</span>
                        <span class="text-slate-700">{{ $paciente->genero === 'M' ? 'Masculino' : ($paciente->genero === 'F' ? 'Femenino' : 'Otro') }}</span>
                    </div>
                </div>
            </div>

            <!-- Formulario de Actualización de Contacto -->
            <form action="{{ url('/perfil-paciente') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="id_paciente" value="{{ $paciente->id_paciente }}">

                <div>
                    <h3 class="text-base font-semibold text-slate-900 pb-2 border-b border-slate-100 mb-4">Información de Contacto Directo</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Teléfono Móvil / WhatsApp *</label>
                            <input type="text" name="telefono" value="{{ old('telefono', $paciente->telefono) }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Correo Electrónico *</label>
                            <input type="email" name="correo" value="{{ old('correo', $paciente->correo) }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" required>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Dirección de Domicilio</label>
                        <input type="text" name="direccion_domicilio" value="{{ old('direccion_domicilio', $paciente->direccion_domicilio) }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-slate-900 pb-2 border-b border-slate-100 mb-4">Contacto de Emergencia</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nombre de Referencia</label>
                            <input type="text" name="contacto_emergencia" value="{{ old('contacto_emergencia', $paciente->contacto_emergencia) }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none" placeholder="Familiar o apoderado">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Teléfono de Emergencia</label>
                            <input type="text" name="telefono_emergencia" value="{{ old('telefono_emergencia', $paciente->telefono_emergencia) }}" class="w-full text-sm border-slate-200 rounded-lg p-2.5 bg-slate-50 border focus:bg-white focus:border-sky-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button type="submit" class="btn-primary">
                        Guardar Cambios de Perfil
                    </button>
                </div>
            </form>
        @else
            <p class="text-slate-500 text-center py-8">Inicia sesión con tu cuenta de paciente para ver y actualizar tu perfil.</p>
        @endif
    </div>
</div>
@endsection
