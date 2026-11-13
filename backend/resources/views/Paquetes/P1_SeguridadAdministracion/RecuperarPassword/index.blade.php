@extends('layouts.app')

@section('title', 'Recuperar Contraseña — Clínica Vida Salud')

@section('content')
<div class="py-12 px-4 max-w-xl mx-auto"
     x-data="{
        paso: {{ $paso }},
        correo: '{{ old('correo', $correo) }}',
        codigo: '{{ old('codigo', '') }}',
        password: '',
        password_confirmation: '',
        verPass: false,
        codigoDemo: '{{ $codigoDemo }}',
        
        cargarEmailDemo(email) {
            this.correo = email;
        },
        usarCodigoDemo() {
            this.codigo = this.codigoDemo;
        },
        get tieneMinimo() { return this.password.length >= 8; },
        get tieneMayuscula() { return /[A-Z]/.test(this.password); },
        get tieneMinuscula() { return /[a-z]/.test(this.password); },
        get tieneNumero() { return /[0-9]/.test(this.password); },
        get tieneSimbolo() { return /[!@#$%^&*(),.?\":{}|<>_\-]/.test(this.password); },
        get coinciden() { return this.password.length > 0 && this.password === this.password_confirmation; },
        get esValida() {
            return this.tieneMinimo && this.tieneMayuscula && this.tieneMinuscula && this.tieneNumero && this.tieneSimbolo && this.coinciden;
        }
     }">

    <!-- Cabecera -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-sky-50 text-[#0284C7] mb-3 shadow-inner border border-sky-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                <circle cx="12" cy="16" r="1"/>
            </svg>
        </div>
        <span class="text-xs font-mono font-semibold uppercase tracking-widest text-[#0284C7] block">SEGURIDAD Y ACCESO</span>
        <h1 class="text-2xl sm:text-3xl font-serif text-slate-900 mt-1">Recuperación de Contraseña</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-md mx-auto">
            Restablece el acceso a tu cuenta mediante verificación de seguridad y políticas de contraseñas robustas.
        </p>
    </div>

    <!-- Indicador de Pasos -->
    <div class="flex items-center justify-center gap-3 mb-6">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                  :class="paso === 1 ? 'bg-[#0284C7] text-white shadow-sm' : 'bg-emerald-100 text-emerald-800'">
                <template x-if="paso > 1"><span>✓</span></template>
                <template x-if="paso === 1"><span>1</span></template>
            </span>
            <span class="text-xs font-semibold" :class="paso === 1 ? 'text-[#0284C7]' : 'text-slate-600'">Verificar Correo</span>
        </div>
        <div class="w-8 h-0.5" :class="paso > 1 ? 'bg-emerald-400' : 'bg-slate-200'"></div>
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                  :class="paso === 2 ? 'bg-[#0284C7] text-white shadow-sm' : 'bg-slate-100 text-slate-400'">2</span>
            <span class="text-xs font-semibold" :class="paso === 2 ? 'text-[#0284C7]' : 'text-slate-400'">Nueva Clave</span>
        </div>
    </div>

    <!-- Contenedor Principal -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
        
        <!-- PASO 1: Ingreso de correo de recuperación -->
        <template x-if="paso === 1">
            <form action="{{ route('recuperar.solicitar') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Correo de Recuperación o Usuario *
                    </label>
                    <input type="text" name="correo" x-model="correo"
                           class="w-full text-sm border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none"
                           placeholder="ejemplo@clinica.com o nombre de usuario" required autofocus>
                    <p class="text-[11px] text-slate-400 mt-1.5">
                        Te enviaremos un código de seguridad de 6 dígitos para validar tu identidad.
                    </p>
                </div>

                <!-- Cuentas de Demostración Rápidas -->
                <div class="pt-2 border-t border-slate-100">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Selección Rápida de Cuenta Demo:</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                        <button type="button" @click="cargarEmailDemo('admin@clinica.com')"
                                class="p-2 rounded-lg border border-slate-200 text-left hover:border-[#0284C7] hover:bg-sky-50/50 transition-colors">
                            <strong class="text-slate-800 block text-[11px]">Administrador</strong>
                            <span class="text-slate-500 text-[10px]">admin@clinica.com</span>
                        </button>
                        <button type="button" @click="cargarEmailDemo('cruzrojasannett@gmail.com')"
                                class="p-2 rounded-lg border border-amber-300 bg-amber-50/50 text-left hover:border-[#0284C7] transition-colors">
                            <strong class="text-slate-800 block text-[11px]">Annett Cruz (CI 13730890)</strong>
                            <span class="text-slate-500 text-[10px]">cruzrojasannett@gmail.com</span>
                        </button>
                        <button type="button" @click="cargarEmailDemo('fernando.justiniano@mail.com')"
                                class="p-2 rounded-lg border border-slate-200 text-left hover:border-[#0284C7] hover:bg-sky-50/50 transition-colors">
                            <strong class="text-slate-800 block text-[11px]">Paciente Demo</strong>
                            <span class="text-slate-500 text-[10px]">fernando.justiniano@mail.com</span>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-[#0284C7] hover:bg-[#0369A1] text-white font-semibold text-sm transition-all shadow-md shadow-[#0284C7]/20 flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    <span>Enviar Código de Seguridad</span>
                </button>
            </form>
        </template>

        <!-- PASO 2: Verificación de código y cambio de clave con políticas estrictas -->
        <template x-if="paso === 2">
            <form action="{{ route('recuperar.confirmar') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Correo activo -->
                <div class="p-3 rounded-xl bg-sky-50/70 border border-sky-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-sky-700 block">Restableciendo cuenta de:</span>
                        <strong class="text-xs text-slate-800" x-text="correo"></strong>
                    </div>
                    <a href="{{ route('recuperar.reiniciar') }}" class="text-[11px] font-semibold text-[#0284C7] hover:underline">
                        Cambiar correo
                    </a>
                </div>

                <!-- Código de seguridad -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Código de Seguridad (6 dígitos) *
                        </label>
                        <button type="button" @click="usarCodigoDemo()"
                                class="text-[11px] text-[#0284C7] hover:underline font-semibold flex items-center gap-1">
                            <span>Usar código recibido (<span x-text="codigoDemo"></span>)</span>
                        </button>
                    </div>
                    <input type="text" name="codigo" x-model="codigo" maxlength="6"
                           class="w-full text-center tracking-[0.3em] font-mono text-lg font-bold border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none"
                           placeholder="123456" required autofocus>
                </div>

                <!-- Nueva Contraseña -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nueva Contraseña *
                    </label>
                    <div class="relative">
                        <input :type="verPass ? 'text' : 'password'" name="password" x-model="password"
                               class="w-full text-sm border-slate-200 rounded-xl pl-3.5 pr-10 py-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none"
                               placeholder="Mínimo 8 caracteres, mayúscula, número y símbolo" required>
                        <button type="button" @click="verPass = !verPass"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <template x-if="!verPass">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </template>
                            <template x-if="verPass">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                            </template>
                        </button>
                    </div>
                </div>

                <!-- Confirmar Contraseña -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Confirmar Nueva Contraseña *
                    </label>
                    <input :type="verPass ? 'text' : 'password'" name="password_confirmation" x-model="password_confirmation"
                           class="w-full text-sm border-slate-200 rounded-xl px-3.5 py-2.5 bg-slate-50 border focus:bg-white focus:border-[#0284C7] focus:outline-none"
                           placeholder="Repite la nueva contraseña" required>
                </div>

                <!-- Lista de Requisitos Dinámica en Vivo -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5 text-xs">
                    <p class="font-bold text-slate-600 uppercase tracking-wider text-[10px] mb-1">Políticas de Seguridad Requeridas:</p>
                    
                    <div class="flex items-center gap-2" :class="tieneMinimo ? 'text-emerald-700 font-semibold' : 'text-slate-400'">
                        <span x-text="tieneMinimo ? '✓' : '○'"></span>
                        <span>Mínimo 8 caracteres de longitud</span>
                    </div>
                    <div class="flex items-center gap-2" :class="tieneMayuscula ? 'text-emerald-700 font-semibold' : 'text-slate-400'">
                        <span x-text="tieneMayuscula ? '✓' : '○'"></span>
                        <span>Al menos una letra MAYÚSCULA (A-Z)</span>
                    </div>
                    <div class="flex items-center gap-2" :class="tieneMinuscula ? 'text-emerald-700 font-semibold' : 'text-slate-400'">
                        <span x-text="tieneMinuscula ? '✓' : '○'"></span>
                        <span>Al menos una letra minúscula (a-z)</span>
                    </div>
                    <div class="flex items-center gap-2" :class="tieneNumero ? 'text-emerald-700 font-semibold' : 'text-slate-400'">
                        <span x-text="tieneNumero ? '✓' : '○'"></span>
                        <span>Al menos un número (0-9)</span>
                    </div>
                    <div class="flex items-center gap-2" :class="tieneSimbolo ? 'text-emerald-700 font-semibold' : 'text-slate-400'">
                        <span x-text="tieneSimbolo ? '✓' : '○'"></span>
                        <span>Al menos un símbolo especial (!@#$%^&*.,-_)</span>
                    </div>
                    <div class="flex items-center gap-2" :class="coinciden ? 'text-emerald-700 font-semibold' : 'text-slate-400'">
                        <span x-text="coinciden ? '✓' : '○'"></span>
                        <span>Ambas contraseñas coinciden exactamente</span>
                    </div>
                </div>

                <button type="submit"
                        :disabled="!esValida || codigo.length < 6"
                        :class="esValida && codigo.length >= 6
                            ? 'bg-[#0284C7] hover:bg-[#0369A1] text-white shadow-md shadow-[#0284C7]/20 cursor-pointer'
                            : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                        class="w-full py-3 px-4 rounded-xl font-semibold text-sm transition-all flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                    <span>Guardar y Restablecer Contraseña</span>
                </button>
            </form>
        </template>

        <!-- Pie con enlace a Login -->
        <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-between text-xs">
            <a href="{{ route('login') }}" class="font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
                <span>←</span>
                <span>Volver a Iniciar Sesión</span>
            </a>
            <span class="text-slate-400">Cifrado seguro bcrypt</span>
        </div>
    </div>
</div>
@endsection

