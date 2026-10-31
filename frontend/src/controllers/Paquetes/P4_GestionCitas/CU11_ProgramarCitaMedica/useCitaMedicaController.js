import { useState, useEffect, useCallback } from 'react';
import { CitaMedicaModel } from '../../../models/Paquetes/P4_GestionCitas/CitaMedicaModel';
import { PacienteModel } from '../../../models/Paquetes/P3_GestionPacientes/PacienteModel';
import { MedicoModel } from '../../../models/Paquetes/P2_PersonalMedico/MedicoModel';
import { EspecialidadModel } from '../../../models/Paquetes/P1_SeguridadAdministracion/EspecialidadModel';

export function useCitaMedicaController() {
  const [citas, setCitas] = useState([]);
  const [pacientes, setPacientes] = useState([]);
  const [medicos, setMedicos] = useState([]);
  const [especialidades, setEspecialidades] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [reservaConfirmada, setReservaConfirmada] = useState(null);

  const cargarCitas = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [c, p, m, esp] = await Promise.all([
        CitaMedicaModel.listar(),
        PacienteModel.listar('', true),
        MedicoModel.listar(true),
        EspecialidadModel.listar(true),
      ]);
      setCitas(c || []);
      setPacientes(p || []);
      setMedicos(m || []);
      setEspecialidades(esp || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    cargarCitas();
  }, [cargarCitas]);

  const programarCita = async (datos) => {
    setLoading(true);
    setError(null);
    try {
      const nuevaCita = await CitaMedicaModel.programar(datos);
      setCitas((prev) => [nuevaCita, ...prev]);
      setReservaConfirmada(nuevaCita);
      return nuevaCita;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return {
    citas,
    pacientes,
    medicos,
    especialidades,
    loading,
    error,
    reservaConfirmada,
    setReservaConfirmada,
    cargarCitas,
    programarCita,
    setError,
  };
}

