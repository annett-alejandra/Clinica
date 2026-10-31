import { useState, useEffect, useCallback } from 'react';
import { PacienteModel } from '../../../models/Paquetes/P3_GestionPacientes/PacienteModel';

export function usePacienteController() {
  const [pacientes, setPacientes] = useState([]);
  const [busqueda, setBusqueda] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  const cargar = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await PacienteModel.listar(busqueda);
      setPacientes(data || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [busqueda]);

  useEffect(() => {
    cargar();
  }, [cargar]);

  const crearPaciente = async (datos) => {
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const nuevo = await PacienteModel.crear(datos);
      setPacientes((prev) => [nuevo, ...prev]);
      setSuccess('Ficha de paciente creada exitosamente.');
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
      await PacienteModel.toggleEstado(id);
      setPacientes((prev) =>
        prev.map((p) => (p.id_paciente === id ? { ...p, estado: !p.estado } : p))
      );
      setSuccess('Estado del paciente actualizado.');
    } catch (err) {
      setError(err.message);
    }
  };

  return {
    pacientes,
    busqueda,
    setBusqueda,
    loading,
    error,
    success,
    cargar,
    crearPaciente,
    toggleEstado,
    setError,
    setSuccess,
  };
}

