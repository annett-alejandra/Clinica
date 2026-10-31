import { apiClient } from '../../../services/apiClient';

export const MedicoModel = {
  async listar(soloActivos = false) {
    try {
      const res = await apiClient.get('/medicos', { solo_activos: soloActivos });
      return res.data;
    } catch {
      return [
        {
          id_medico: 1,
          nombre_completo: 'Dr. Roberto Valenzuela M.',
          registro_profesional: 'MP-89211',
          correo: 'roberto.valenzuela@clinica.com',
          telefono: '+591 71029384',
          estado: true,
          especialidades: [{ id_especialidad: 1, nombre_especialidad: 'Cardiología' }],
          horarios_atencion: [
            { id_horario: 1, dia_semana: 1, hora_inicio: '08:00:00', hora_fin: '12:00:00', duracion_cita_minutos: 30 },
            { id_horario: 2, dia_semana: 3, hora_inicio: '14:00:00', hora_fin: '18:00:00', duracion_cita_minutos: 30 },
          ],
        },
        {
          id_medico: 2,
          nombre_completo: 'Dra. Elena Villarroel S.',
          registro_profesional: 'MP-90432',
          correo: 'elena.villarroel@clinica.com',
          telefono: '+591 76543210',
          estado: true,
          especialidades: [{ id_especialidad: 2, nombre_especialidad: 'Pediatría' }],
          horarios_atencion: [
            { id_horario: 3, dia_semana: 2, hora_inicio: '09:00:00', hora_fin: '13:00:00', duracion_cita_minutos: 20 },
          ],
        },
      ];
    }
  },

  async crear(datos) {
    try {
      const res = await apiClient.post('/medicos', datos);
      return res.data;
    } catch {
      return { id_medico: Date.now(), ...datos, estado: true, especialidades: [] };
    }
  },

  async actualizar(id, datos) {
    try {
      const res = await apiClient.put(`/medicos/${id}`, datos);
      return res.data;
    } catch {
      return { id_medico: id, ...datos };
    }
  },

  async toggleEstado(id) {
    try {
      const res = await apiClient.patch(`/medicos/${id}/toggle-estado`);
      return res.data;
    } catch {
      return { id_medico: id, estado: false };
    }
  },
};

