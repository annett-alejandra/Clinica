import { useState } from 'react';
import { PacienteModel } from '../../../../models/Paquetes/P3_GestionPacientes/PacienteModel';

export function useRegistroPacienteController(onRegistroExitoso) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  const registrar = async (formulario) => {
    if (formulario.password !== formulario.password_confirmation) {
      setError('Las contraseñas ingresadas no coinciden.');
      return;
    }

    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const res = await PacienteModel.registrarsePublico(formulario);
      setSuccess('¡Registro completado con éxito! Bienvenido al portal de pacientes.');
      if (onRegistroExitoso) {
        onRegistroExitoso(res.usuario);
      }
      return res;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return {
    loading,
    error,
    success,
    registrar,
    setError,
    setSuccess,
  };
}

