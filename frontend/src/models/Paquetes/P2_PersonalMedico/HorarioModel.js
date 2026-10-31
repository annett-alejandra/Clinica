import { apiClient } from '../../../services/apiClient';

export const HorarioModel = {
  async listar(idMedico = null) {
    try {
      const res = await apiClient.get('/horarios', idMedico ? { id_medico: idMedico } : {});
      return res.data;
    } catch {
      return [
        { id_horario: 1, id_medico: 1, dia_semana: 1, hora_inicio: '08:00:00', hora_fin: '12:00:00', duracion_cita_minutos: 30, medico: { nombre_completo: 'Dr. Roberto Valenzuela M.' } },
        { id_horario: 2, id_medico: 1, dia_semana: 3, hora_inicio: '14:00:00', hora_fin: '18:00:00', duracion_cita_minutos: 30, medico: { nombre_completo: 'Dr. Roberto Valenzuela M.' } },
        { id_horario: 3, id_medico: 2, dia_semana: 2, hora_inicio: '09:00:00', hora_fin: '13:00:00', duracion_cita_minutos: 20, medico: { nombre_completo: 'Dra. Elena Villarroel S.' } },
      ];
    }
  },

  async crear(datos) {
    try {
      const res = await apiClient.post('/horarios', datos);
      return res.data;
    } catch {
      return { id_horario: Date.now(), ...datos };
    }
  },

  async eliminar(id) {
    try {
      await apiClient.delete(`/horarios/${id}`);
      return true;
    } catch {
      return true;
    }
  },
};

