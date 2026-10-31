import { useState, useEffect, useCallback } from 'react';
import { EspecialidadModel } from '../../../../models/Paquetes/P1_SeguridadAdministracion/EspecialidadModel';

export function useEspecialidadController() {
  const [especialidades, setEspecialidades] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  const cargar = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await EspecialidadModel.listar();
      setEspecialidades(data || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    cargar();
  }, [cargar]);

  const crearEspecialidad = async (datos) => {
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const nueva = await EspecialidadModel.crear(datos);
      setEspecialidades((prev) => [...prev, nueva]);
      setSuccess('Especialidad médica registrada con éxito.');
      return nueva;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  const toggleEstado = async (id) => {
    try {
      await EspecialidadModel.toggleEstado(id);
      setEspecialidades((prev) =>
        prev.map((e) => (e.id_especialidad === id ? { ...e, estado: !e.estado } : e))
      );
      setSuccess('Estado de la especialidad actualizado.');
    } catch (err) {
      setError(err.message);
    }
  };

  return {
    especialidades,
    loading,
    error,
    success,
    cargar,
    crearEspecialidad,
    toggleEstado,
    setError,
    setSuccess,
  };
}

