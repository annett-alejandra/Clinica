import { useState, useEffect, useCallback } from 'react';
import { MedicoModel } from '../../../models/Paquetes/P2_PersonalMedico/MedicoModel';
import { EspecialidadModel } from '../../../models/Paquetes/P1_SeguridadAdministracion/EspecialidadModel';

export function useMedicoController() {
  const [medicos, setMedicos] = useState([]);
  const [especialidades, setEspecialidades] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  const cargar = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [m, esp] = await Promise.all([
        MedicoModel.listar(),
        EspecialidadModel.listar(true),
      ]);
      setMedicos(m || []);
      setEspecialidades(esp || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    cargar();
  }, [cargar]);

  const crearMedico = async (datos) => {
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const nuevo = await MedicoModel.crear(datos);
      setMedicos((prev) => [...prev, nuevo]);
      setSuccess('Médico registrado exitosamente con sus especialidades.');
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
      await MedicoModel.toggleEstado(id);
      setMedicos((prev) =>
        prev.map((m) => (m.id_medico === id ? { ...m, estado: !m.estado } : m))
      );
      setSuccess('Estado del médico modificado.');
    } catch (err) {
      setError(err.message);
    }
  };

  return {
    medicos,
    especialidades,
    loading,
    error,
    success,
    cargar,
    crearMedico,
    toggleEstado,
    setError,
    setSuccess,
  };
}

