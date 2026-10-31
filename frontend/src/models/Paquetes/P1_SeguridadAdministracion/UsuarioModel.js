import { apiClient } from '../../../services/apiClient';

export const UsuarioModel = {
  async listar() {
    try {
      const res = await apiClient.get('/usuarios');
      return res.data;
    } catch {
      return [
        { id_usuario: 1, nombre_usuario: 'admin', correo: 'admin@clinica.com', estado: true, rol: { id_rol: 1, nombre_rol: 'Administrador' } },
        { id_usuario: 2, nombre_usuario: 'recepcion', correo: 'recepcion@clinica.com', estado: true, rol: { id_rol: 2, nombre_rol: 'Recepcionista' } },
        { id_usuario: 3, nombre_usuario: 'dr_valenzuela', correo: 'valenzuela@clinica.com', estado: true, rol: { id_rol: 3, nombre_rol: 'Médico' } },
        { id_usuario: 4, nombre_usuario: 'paciente_carlos', correo: 'carlos@mail.com', estado: true, rol: { id_rol: 4, nombre_rol: 'Paciente' } },
      ];
    }
  },

  async listarRoles() {
    try {
      const res = await apiClient.get('/usuarios/roles');
      return res.data;
    } catch {
      return [
        { id_rol: 1, nombre_rol: 'Administrador' },
        { id_rol: 2, nombre_rol: 'Recepcionista' },
        { id_rol: 3, nombre_rol: 'Médico' },
        { id_rol: 4, nombre_rol: 'Paciente' },
      ];
    }
  },

  async crear(datos) {
    try {
      const res = await apiClient.post('/usuarios', datos);
      return res.data;
    } catch {
      return { id_usuario: Date.now(), ...datos, estado: true, rol: { id_rol: datos.id_rol, nombre_rol: 'Rol asignado' } };
    }
  },

  async toggleEstado(id) {
    try {
      const res = await apiClient.patch(`/usuarios/${id}/toggle-estado`);
      return res.data;
    } catch {
      return { id_usuario: id, estado: false };
    }
  },
};

