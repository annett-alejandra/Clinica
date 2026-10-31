import { useState, useEffect, useCallback } from 'react';
import { DisponibilidadModel } from '../../../models/Paquetes/P4_GestionCitas/DisponibilidadModel';
import { EspecialidadModel } from '../../../models/Paquetes/P1_SeguridadAdministracion/EspecialidadModel';
import { MedicoModel } from '../../../models/Paquetes/P2_PersonalMedico/MedicoModel';

export function useDisponibilidadController() {
  const [especialidades, setEspecialidades] = useState([]);
  const [medicos, setMedicos] = useState([]);
  const [idEspecialidad, setIdEspecialidad] = useState('');
  const [idMedico, setIdMedico] = useState('');
  const [fecha, setFecha] = useState(() => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
  });
  const [oferta, setOferta] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    async function initCatalogos() {
      try {
        const [esp, med] = await Promise.all([
          EspecialidadModel.listar(true),
          MedicoModel.listar(true),
        ]);
        setEspecialidades(esp || []);
        setMedicos(med || []);
        if (esp && esp.length > 0) {
          setIdEspecialidad(esp[0].id_especialidad);
        }
      } catch (err) {
        console.error(err);
      }
    }
    initCatalogos();
  }, []);

  const consultar = useCallback(async () => {
    if (!idEspecialidad || !fecha) return;

    setLoading(true);
    setError(null);
    try {
      const data = await DisponibilidadModel.consultar(
        Number(idEspecialidad),
        idMedico ? Number(idMedico) : null,
        fecha
      );
      setOferta(data || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [idEspecialidad, idMedico, fecha]);

  return {
    especialidades,
    medicos,
    idEspecialidad,
    setIdEspecialidad,
    idMedico,
    setIdMedico,
    fecha,
    setFecha,
    oferta,
    loading,
    error,
    consultar,
    setError,
  };
}

