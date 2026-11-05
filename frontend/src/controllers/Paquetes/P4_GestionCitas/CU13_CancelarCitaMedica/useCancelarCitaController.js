import { useState } from "react";
import { GestionOperativaCitasModel } from "../../../../models/Paquetes/P4_GestionCitas/GestionOperativaCitasModel";

export function useCancelarCitaController(onSuccess) {
  const [cancelando, setCancelando] = useState(false);
  const [error, setError] = useState(null);

  const cancelar = async (idCita, motivo_cancelacion) => {
    setCancelando(true);
    setError(null);
    try {
      const cita = await GestionOperativaCitasModel.cancelarCita(idCita, motivo_cancelacion);
      if (onSuccess) onSuccess(cita);
      return cita;
    } catch (err) {
      setError(err.message || "Error al cancelar la cita médica.");
      throw err;
    } finally {
      setCancelando(false);
    }
  };

  return {
    cancelar,
    cancelando,
    error,
    limpiarError: () => setError(null),
  };
}
