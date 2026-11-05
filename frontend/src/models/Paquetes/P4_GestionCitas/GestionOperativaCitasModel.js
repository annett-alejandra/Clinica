import { apiClient } from "../../../services/apiClient";

export const GestionOperativaCitasModel = {
  // CU12: Reprogramar
  async reprogramarCita(idCita, { fecha_cita, hora_cita, motivo_reprogramacion }) {
    const res = await apiClient.patch(`/citas/${idCita}/reprogramar`, {
      fecha_cita,
      hora_cita,
      motivo_reprogramacion,
    });
    return res.data;
  },

  // CU13: Cancelar
  async cancelarCita(idCita, motivo_cancelacion) {
    const res = await apiClient.patch(`/citas/${idCita}/cancelar`, {
      motivo_cancelacion,
    });
    return res.data;
  },

  // CU14: Confirmar
  async confirmarCita(idCita) {
    const res = await apiClient.patch(`/control-atencion/citas/${idCita}/confirmar`);
    return res.data;
  },

  // CU14: Llegada
  async registrarLlegada(idCita) {
    const res = await apiClient.patch(`/control-atencion/citas/${idCita}/llegada`);
    return res.data;
  },

  // CU14: Inasistencia
  async marcarInasistencia(idCita, motivo) {
    const res = await apiClient.patch(`/control-atencion/citas/${idCita}/inasistencia`, { motivo });
    return res.data;
  },

  // CU15: Atendida
  async registrarAtencion(idCita, observacion_atencion) {
    const res = await apiClient.patch(`/citas/${idCita}/atender`, {
      observacion_atencion,
    });
    return res.data;
  },
};

