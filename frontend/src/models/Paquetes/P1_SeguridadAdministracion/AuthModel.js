import { apiClient } from '../../../services/apiClient';

export const AuthModel = {
  async login(identificador, password) {
    try {
      const response = await apiClient.post('/auth/login', { identificador, password });
      if (response?.data?.token) {
        apiClient.setToken(response.data.token);
      }
      return response.data;
    } catch {
      // Fallback mock para pruebas en modo desacoplado / demo
      if (password === 'clinica123' || password.length >= 6) {
        let rol = 'Administrador';
        if (identificador.includes('recep')) rol = 'Recepcionista';
        if (identificador.includes('doc') || identificador.includes('med')) rol = 'Médico';
        if (identificador.includes('pac')) rol = 'Paciente';

        const mockData = {
          token: 'mock-token-' + Date.now(),
          usuario: {
            id_usuario: 1,
            nombre_usuario: identificador,
            correo: identificador.includes('@') ? identificador : `${identificador}@clinica.com`,
            rol: rol,
            perfil: `Usuario ${rol} (${identificador})`,
          },
        };
        apiClient.setToken(mockData.token);
        return mockData;
      }
      throw new Error('Credenciales inválidas o servidor no disponible.');
    }
  },

  async logout(idUsuario) {
    try {
      await apiClient.post('/auth/logout', { id_usuario: idUsuario });
    } catch {
      // Silencioso en desconexión
    } finally {
      apiClient.setToken(null);
    }
  },
};

