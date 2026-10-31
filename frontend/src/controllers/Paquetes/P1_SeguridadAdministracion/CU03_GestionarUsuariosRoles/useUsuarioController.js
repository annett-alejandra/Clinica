import { useState, useEffect, useCallback } from 'react';
import { UsuarioModel } from '../../../../models/Paquetes/P1_SeguridadAdministracion/UsuarioModel';

export function useUsuarioController() {
  const [usuarios, setUsuarios] = useState([]);
  const [roles, setRoles] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  const cargarDatos = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [u, r] = await Promise.all([UsuarioModel.listar(), UsuarioModel.listarRoles()]);
      setUsuarios(u || []);
      setRoles(r || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    cargarDatos();
  }, [cargarDatos]);

  const crearUsuario = async (datos) => {
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const nuevo = await UsuarioModel.crear(datos);
      setUsuarios((prev) => [nuevo, ...prev]);
      setSuccess('Usuario creado exitosamente.');
      return nuevo;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  const toggleEstado = async (id) => {
    try {
      await UsuarioModel.toggleEstado(id);
      setUsuarios((prev) =>
        prev.map((u) => (u.id_usuario === id ? { ...u, estado: !u.estado } : u))
      );
      setSuccess('Estado de usuario modificado.');
    } catch (err) {
      setError(err.message);
    }
  };

  return {
    usuarios,
    roles,
    loading,
    error,
    success,
    cargarDatos,
    crearUsuario,
    toggleEstado,
    setError,
    setSuccess,
  };
}

