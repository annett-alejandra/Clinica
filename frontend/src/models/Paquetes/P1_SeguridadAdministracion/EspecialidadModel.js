import { apiClient } from '../../../services/apiClient';

export const EspecialidadModel = {
  async listar(soloActivas = false) {
    try {
      const res = await apiClient.get('/especialidades', { solo_activas: soloActivas });
      return res.data;
    } catch {
      return [
        { id_especialidad: 1, nombre_especialidad: 'Cardiología', descripcion: 'Diagnóstico y tratamiento de afecciones cardíacas.', estado: true, medicos_count: 2 },
        { id_especialidad: 2, nombre_especialidad: 'Pediatría', descripcion: 'Atención integral del paciente infantil y juvenil.', estado: true, medicos_count: 3 },
        { id_especialidad: 3, nombre_especialidad: 'Traumatología', descripcion: 'Cirugía ortopédica y lesiones musculoesqueléticas.', estado: true, medicos_count: 1 },
        { id_especialidad: 4, nombre_especialidad: 'Neurología', descripcion: 'Enfermedades del sistema nervioso central y periférico.', estado: true, medicos_count: 1 },
        { id_especialidad: 5, nombre_especialidad: 'Dermatología', descripcion: 'Tratamientos clínicos y estéticos de la piel.', estado: true, medicos_count: 2 },
      ];
    }
  },

  async crear(datos) {
    try {
      const res = await apiClient.post('/especialidades', datos);
      return res.data;
    } catch {
      return { id_especialidad: Date.now(), ...datos, estado: true, medicos_count: 0 };
    }
  },

  async actualizar(id, datos) {
    try {
      const res = await apiClient.put(`/especialidades/${id}`, datos);
      return res.data;
    } catch {
      return { id_especialidad: id, ...datos };
    }
  },

  async toggleEstado(id) {
    try {
      const res = await apiClient.patch(`/especialidades/${id}/toggle-estado`);
      return res.data;
    } catch {
      return { id_especialidad: id, estado: false };
    }
  },
};

