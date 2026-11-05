import { apiClient } from "../../../services/apiClient";

export const ReportesModel = {
  // CU19: Reportes administrativos y métricas
  async generarReporte(filtros = {}) {
    const params = new URLSearchParams();
    Object.entries(filtros).forEach(([key, val]) => {
      if (val !== undefined && val !== null && val !== "") {
        params.append(key, val);
      }
    });
    const res = await apiClient.get(`/reportes-administrativos?${params.toString()}`);
    return res.data;
  },
};

