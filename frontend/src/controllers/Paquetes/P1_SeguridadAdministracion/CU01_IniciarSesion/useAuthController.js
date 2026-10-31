import { useState } from 'react';
import { AuthModel } from '../../../../models/Paquetes/P1_SeguridadAdministracion/AuthModel';

export function useAuthController(onLoginSuccess, onLogoutSuccess) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [usuario, setUsuario] = useState(null);

  const login = async (identificador, password) => {
    setLoading(true);
    setError(null);
    try {
      const data = await AuthModel.login(identificador, password);
      setUsuario(data.usuario);
      if (onLoginSuccess) onLoginSuccess(data.usuario);
      return data;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  const logout = async () => {
    setLoading(true);
    try {
      await AuthModel.logout(usuario?.id_usuario);
      setUsuario(null);
      if (onLogoutSuccess) onLogoutSuccess();
    } finally {
      setLoading(false);
    }
  };

  return {
    loading,
    error,
    usuario,
    login,
    logout,
    setError,
  };
}

