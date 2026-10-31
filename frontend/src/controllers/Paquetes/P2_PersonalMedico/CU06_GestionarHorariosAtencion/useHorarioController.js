import { useState, useEffect, useCallback } from 'react';
import { HorarioModel } from '../../../../models/Paquetes/P2_PersonalMedico/HorarioModel';
import { MedicoModel } from '../../../../models/Paquetes/P2_PersonalMedico/MedicoModel';

export function useHorarioController() {
  const [horarios, setHorarios] = useState([]);
  const [medicos, setMedicos] = useState([]);
  const [medicoSeleccionado, setMedicoSeleccionado] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  const cargar = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [h, m] = await Promise.all([
        HorarioModel.listar(medicoSeleccionado || null),
        MedicoModel.listar(true),
      ]);
      setHorarios(h || []);
      setMedicos(m || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [medicoSeleccionado]);

  useEffect(() => {
    cargar();
  }, [cargar]);

  const crearHorario = async (datos) => {
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const nuevo = await HorarioModel.crear(datos);
      setHorarios((prev) => [...prev, nuevo]);
      setSuccess('Horario de atención configurado exitosamente.');
      return nuevo;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  const eliminarHorario = async (id) => {
    try {
      await HorarioModel.eliminar(id);
      setHorarios((prev) => prev.filter((h) => h.id_horario !== id));
      setSuccess('Horario eliminado correctamente.');
    } catch (err) {
      setError(err.message);
    }
  };

  return {
    horarios,
    medicos,
    medicoSeleccionado,
    setMedicoSeleccionado,
    loading,
    error,
    success,
    cargar,
    crearHorario,
    eliminarHorario,
    setError,
    setSuccess,
  };
}

