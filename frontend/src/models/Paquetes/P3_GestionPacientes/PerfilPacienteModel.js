import { apiClient } from "../../../services/apiClient";

export const PerfilPacienteModel = {
  async obtenerPerfil(idPaciente = null) {
    const url = idPaciente ? `/perfil-paciente?id_paciente=${idPaciente}` : "/perfil-paciente";
    const res = await apiClient.get(url);
    return res.data;
  },

  async actualizarPerfil(datos) {
    const res = await apiClient.put("/perfil-paciente", datos);
    return res.data;
  },
};

