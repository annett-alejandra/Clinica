import { apiClient } from '../../../services/apiClient';

export const DisponibilidadModel = {
  async consultar(idEspecialidad, idMedico, fecha) {
    try {
      const res = await apiClient.get('/disponibilidad', {
        id_especialidad: idEspecialidad,
        ...(idMedico ? { id_medico: idMedico } : {}),
        fecha: fecha,
      });
      return res.data;
    } catch {
      return [
        {
          medico: { id_medico: 1, nombre_completo: 'Dr. Roberto Valenzuela M.', registro_profesional: 'MP-89211' },
          especialidad: { id_especialidad: idEspecialidad, nombre_especialidad: 'Cardiología' },
          fecha: fecha,
          total_cupos: 4,
          cupos: [
            { hora_inicio: '08:00', hora_fin: '08:30', duracion_minutos: 30 },
            { hora_inicio: '08:30', hora_fin: '09:00', duracion_minutos: 30 },
            { hora_inicio: '10:00', hora_fin: '10:30', duracion_minutos: 30 },
            { hora_inicio: '11:00', hora_fin: '11:30', duracion_minutos: 30 },
          ],
        },
      ];
    }
  },
};

