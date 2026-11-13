<?php

namespace App\Http\Controllers\Paquetes\P1_SeguridadAdministracion\CU01_IniciarSesion;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Paquetes\P1_SeguridadAdministracion\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RecuperarPasswordController extends Controller
{
    /**
     * Muestra la interfaz de recuperación de contraseña.
     */
    public function index(Request $request): View
    {
        $paso = session('recovery_step', 1);
        $correo = session('recovery_email', $request->query('correo', ''));
        $codigoDemo = session('recovery_code', '123456');

        return view('Paquetes.P1_SeguridadAdministracion.RecuperarPassword.index', [
            'paso' => $paso,
            'correo' => $correo,
            'codigoDemo' => $codigoDemo,
        ]);
    }

    /**
     * Paso 1: Valida el correo y genera el código de seguridad.
     */
    public function solicitar(Request $request): RedirectResponse
    {
        $request->validate([
            'correo' => 'required|string',
        ], [
            'correo.required' => 'Ingresa tu correo electrónico registrado.',
        ]);

        $identificador = trim($request->input('correo'));

        // Buscar usuario directo
        $usuario = User::query()
            ->where('correo', $identificador)
            ->orWhere('nombre_usuario', $identificador)
            ->first();

        // Si no se encuentra directo, verificar por alias de demostración
        if (!$usuario) {
            $aliasMap = [
                'admin@clinica.com' => 'admin_uagrm',
                'admin' => 'admin_uagrm',
                'recepcion@clinica.com' => 'recepcion_central',
                'recepcion' => 'recepcion_central',
                'roberto.valenzuela@clinica.com' => 'medico_gonzales',
                'dr_valenzuela' => 'medico_gonzales',
                'fernando.justiniano@mail.com' => 'paciente_fjustiniano',
                'paciente_fer' => 'paciente_fjustiniano',
                'cruzrojasannett@gmail.com' => 'annett_cruz',
                'annett_cruz' => 'annett_cruz',
                'annett' => 'annett_cruz',
                '13730890' => 'annett_cruz',
            ];

            if (isset($aliasMap[$identificador])) {
                $usuario = User::query()->where('nombre_usuario', $aliasMap[$identificador])->first();
            }
        }

        if (!$usuario) {
            return back()->withInput()->with('error', 'No encontramos ninguna cuenta asociada al correo o usuario indicado.');
        }

        // Generar código de seguridad de 6 dígitos
        $codigo = strval(random_int(100000, 999999));

        session([
            'recovery_email' => $usuario->correo,
            'recovery_user_id' => $usuario->id_usuario,
            'recovery_code' => $codigo,
            'recovery_step' => 2,
        ]);

        AuditService::registrar(
            $usuario->id_usuario,
            'SOLICITUD_RECUPERACION_PASSWORD',
            'usuarios',
            $usuario->id_usuario,
            ['correo' => $usuario->correo]
        );

        return redirect()->route('recuperar.password')
            ->with('status', "Código de seguridad generado para {$usuario->correo}: {$codigo}. Ingrésalo a continuación junto a tu nueva contraseña.");
    }

    /**
     * Paso 2: Valida el código de seguridad y aplica la nueva contraseña con políticas estrictas.
     */
    public function confirmar(Request $request): RedirectResponse
    {
        $request->validate([
            'codigo' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/', // Mayúscula
                'regex:/[a-z]/', // Minúscula
                'regex:/[0-9]/', // Número
                'regex:/[!@#$%^&*(),.?":{}|<>_\-]/', // Símbolo especial
            ],
        ], [
            'codigo.required' => 'Ingresa el código de seguridad de 6 dígitos.',
            'password.required' => 'Ingresa la nueva contraseña.',
            'password.min' => 'La contraseña debe tener un mínimo de 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.regex' => 'La contraseña debe incluir al menos una letra mayúscula, una minúscula, un número y un símbolo especial (!@#$%^&*).',
        ]);

        $codigoIngresado = trim($request->input('codigo'));
        $codigoSesion = session('recovery_code');

        // Aceptar el código generado en sesión o el código universal demo 123456
        if ($codigoIngresado !== $codigoSesion && $codigoIngresado !== '123456') {
            return back()->withInput()->with('error', 'El código de seguridad ingresado es incorrecto o ha caducado.');
        }

        $usuarioId = session('recovery_user_id');
        $correo = session('recovery_email');

        $usuario = null;
        if ($usuarioId) {
            $usuario = User::find($usuarioId);
        }
        if (!$usuario && $correo) {
            $usuario = User::where('correo', $correo)->first();
        }

        if (!$usuario) {
            return redirect()->route('recuperar.password')
                ->with('error', 'La sesión de recuperación caducó. Por favor inicia el proceso nuevamente.');
        }

        // Actualizar contraseña con hash seguro bcrypt
        $usuario->password_hash = Hash::make($request->input('password'));
        $usuario->save();

        AuditService::registrar(
            $usuario->id_usuario,
            'RESTABLECIMIENTO_PASSWORD_EXITOSO',
            'usuarios',
            $usuario->id_usuario,
            ['correo' => $usuario->correo]
        );

        // Limpiar datos temporales de la sesión
        session()->forget(['recovery_email', 'recovery_user_id', 'recovery_code', 'recovery_step']);

        return redirect()->route('login')
            ->with('status', '¡Contraseña restablecida exitosamente! Ya puedes iniciar sesión con tu nueva contraseña.');
    }

    /**
     * Reiniciar el flujo de recuperación.
     */
    public function reiniciar(): RedirectResponse
    {
        session()->forget(['recovery_email', 'recovery_user_id', 'recovery_code', 'recovery_step']);
        return redirect()->route('recuperar.password');
    }
}

