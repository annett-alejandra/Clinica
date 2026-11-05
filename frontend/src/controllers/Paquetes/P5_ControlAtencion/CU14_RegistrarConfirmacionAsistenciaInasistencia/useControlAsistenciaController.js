import { useState } from "react";
import { GestionOperativaCitasModel } from "../../../../models/Paquetes/P4_GestionCitas/GestionOperativaCitasModel";

export function useControlAsistenciaController(onSuccess) {
  const [procesando, setProcesando] = useState(false);
  const [error, setError] = useState(null);

  const confirmar = async (idCita) => {
    setProcesando(true);
    setError(null);
    try {
      const cita = await GestionOperativaCitasModel.confirmarCita(idCita);
      if (onSuccess) onSuccess(cita);
      return cita;
    } catch (err) {
      setError(err.message || "Error al confirmar la cita.");
      throw err;
    } finally {
      setProcesando(false);
    }
  };

  const registrarLlegada = async (idCita) => {
    setProcesando(true);
    setError(null);
    try {
      const cita = await GestionOperativaCitasModel.registrarLlegada(idCita);
      if (onSuccess) onSuccess(cita);
      return cita;
    } catch (err) {
      setError(err.message || "Error al registrar la llegada a recepción.");
      throw err;
    } finally {
      setProcesando(false);
    }
  };

  const marcarInasistencia = async (idCita, motivo) => {
    setProcesando(true);
    setError(null);
    try {
      const cita = await GestionOperativaCitasModel.marcarInasistencia(idCita, motivo);
      if (onSuccess) onSuccess(cita);
      return cita;
    } catch (err) {
      setError(err.message || "Error al registrar inasistencia.");
      throw err;
    } finally {
      setProcesando(false);
    }
  };

  return {
    confirmar,
    registrarLlegada,
    marcarInasistencia,
    procesando,
    error,
    limpiarError: () => setError(null),
  };
}
