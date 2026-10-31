import { apiClient } from '../../../services/apiClient';

export const PacienteModel = {
  async listar(termino = '', soloActivos = false) {
    try {
      const res = await apiClient.get('/pacientes', { q: termino, solo_activos: soloActivos });
      return res.data;
    } catch {
      return [
        {
          id_paciente: 1,
          ci: '6829104',
          nombre_completo: 'Carlos Eduardo Mendoza P.',
          fecha_nacimiento: '1988-06-15',
          genero: 'Masculino',
          correo: 'carlos.mendoza@mail.com',
          telefono: '+591 78912345',
          direccion_domicilio: 'Av. Las Palmas #420',
          contacto_emergencia: 'María Mendoza (Madre)',
          telefono_emergencia: '+591 71234567',
          tipo_paciente: 'General',
          estado: true,
        },
        {
          id_paciente: 2,
          ci: '7912403',
          nombre_completo: 'Lucía Marcela Gómez S.',
          fecha_nacimiento: '1995-11-28',
          genero: 'Femenino',
          correo: 'lucia.gomez@mail.com',
          telefono: '+591 69081234',
          direccion_domicilio: 'Condominio Sevilla Real dpto 3B',
          contacto_emergencia: 'Jorge Gómez (Hermano)',
          telefono_emergencia: '+591 67890123',
          tipo_paciente: 'Asegurado',
          estado: true,
        },
      ];
    }
  },

  async crear(datos) {
    try {
      const res = await apiClient.post('/pacientes', datos);
      return res.data;
    } catch {
      return { id_paciente: Date.now(), ...datos, estado: true };
    }
  },

  async actualizar(id, datos) {
    try {
      const res = await apiClient.put(`/pacientes/${id}`, datos);
      return res.data;
    } catch {
      return { id_paciente: id, ...datos };
    }
  },

  async toggleEstado(id) {
    try {
      const res = await apiClient.patch(`/pacientes/${id}/toggle-estado`);
      return res.data;
    } catch {
      return { id_paciente: id, estado: false };
    }
  },

  async registrarsePublico(datos) {
    try {
      const res = await apiClient.post('/registro-paciente', datos);
      if (res?.data?.token) {
        apiClient.setToken(res.data.token);
      }
      return res.data;
    } catch {
      const mockResult = {
        token: 'mock-patient-token-' + Date.now(),
        usuario: {
          id_usuario: 10,
          nombre_usuario: datos.correo.split('@')[0],
          correo: datos.correo,
          rol: 'Paciente',
          perfil: datos.nombre_completo,
        },
        paciente: {
          id_paciente: Date.now(),
          ...datos,
          estado: true,
        },
      };
      apiClient.setToken(mockResult.token);
      return mockResult;
    }
  },
};

