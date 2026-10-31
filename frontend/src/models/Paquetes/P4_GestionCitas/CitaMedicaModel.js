import { apiClient } from '../../../services/apiClient';

export const CitaMedicaModel = {
  async listar(filtros = {}) {
    try {
      const res = await apiClient.get('/citas', filtros);
      return res.data;
    } catch {
      return [
        {
          id_cita: 1,
          codigo_unico: 'e4a1a79f-26b2-4d82-829f-3bb2c49987a1',
          fecha_cita: '2026-11-02',
          hora_cita: '08:30:00',
          duracion_minutos: 30,
          motivo: 'Evaluación cardiológica preventiva',
          paciente: { id_paciente: 1, nombre_completo: 'Carlos Eduardo Mendoza P.', ci: '6829104' },
          medico: { id_medico: 1, nombre_completo: 'Dr. Roberto Valenzuela M.' },
          especialidad: { id_especialidad: 1, nombre_especialidad: 'Cardiología' },
          estado_cita: { id_estado_cita: 1, nombre_estado: 'Programada' },
        },
      ];
    }
  },

  async programar(datos) {
    try {
      const res = await apiClient.post('/citas', datos);
      return res.data;
    } catch {
      return {
        id_cita: Date.now(),
        codigo_unico: 'demo-uuid-' + Math.random().toString(36).substring(2, 10),
        fecha_cita: datos.fecha_cita,
        hora_cita: datos.hora_cita,
        duracion_minutos: datos.duracion_minutos || 30,
        motivo: datos.motivo,
        paciente: { id_paciente: datos.id_paciente, nombre_completo: 'Paciente Seleccionado' },
        medico: { id_medico: datos.id_medico, nombre_completo: 'Médico Asignado' },
        especialidad: { id_especialidad: datos.id_especialidad, nombre_especialidad: 'Especialidad' },
        estado_cita: { id_estado_cita: 1, nombre_estado: 'Programada' },
      };
    }
  },

  async consultarPorCodigo(codigo) {
    try {
      const res = await apiClient.get(`/citas/${codigo}`);
      return res.data;
    } catch {
      return null;
    }
  },
};

