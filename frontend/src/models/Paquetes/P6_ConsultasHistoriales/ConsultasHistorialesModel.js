import { apiClient } from "../../../services/apiClient";

export const ConsultasHistorialesModel = {
  // CU16: Consultar agenda médica con filtros
  async consultarAgenda(filtros = {}) {
    const params = new URLSearchParams();
    Object.entries(filtros).forEach(([key, val]) => {
      if (val !== undefined && val !== null && val !== "") {
        params.append(key, val);
      }
    });
    const res = await apiClient.get(`/agenda-medica?${params.toString()}`);
    return res.data;
  },

  // CU17: Historial clínico de paciente por CI o ID
  async consultarHistorialPaciente(identificador) {
    const res = await apiClient.get(`/historial-paciente/${encodeURIComponent(identificador)}`);
    return res.data;
  },

  // CU18: Mis citas (Portal Paciente)
  async consultarMisCitas(idPaciente = null) {
    const url = idPaciente ? `/mis-citas?id_paciente=${idPaciente}` : "/mis-citas";
    const res = await apiClient.get(url);
    return res.data;
  },
};

