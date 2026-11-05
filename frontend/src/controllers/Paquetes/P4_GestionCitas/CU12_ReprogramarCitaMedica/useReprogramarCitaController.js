import { useState } from "react";
import { GestionOperativaCitasModel } from "../../../../models/Paquetes/P4_GestionCitas/GestionOperativaCitasModel";

export function useReprogramarCitaController(onSuccess) {
  const [procesando, setProcesando] = useState(false);
  const [error, setError] = useState(null);

  const reprogramar = async (idCita, { fecha_cita, hora_cita, motivo_reprogramacion }) => {
    setProcesando(true);
    setError(null);
    try {
      const cita = await GestionOperativaCitasModel.reprogramarCita(idCita, {
        fecha_cita,
        hora_cita,
        motivo_reprogramacion,
      });
      if (onSuccess) onSuccess(cita);
      return cita;
    } catch (err) {
      setError(err.message || "Error al reprogramar la cita médica.");
      throw err;
    } finally {
      setProcesando(false);
    }
  };

  return {
    reprogramar,
    procesando,
    error,
    limpiarError: () => setError(null),
  };
}
