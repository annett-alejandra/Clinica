import { useState } from "react";
import { GestionOperativaCitasModel } from "../../../../models/Paquetes/P4_GestionCitas/GestionOperativaCitasModel";

export function useCitaAtendidaController(onSuccess) {
  const [guardando, setGuardando] = useState(false);
  const [error, setError] = useState(null);

  const registrarAtencion = async (idCita, observacion_atencion) => {
    setGuardando(true);
    setError(null);
    try {
      const cita = await GestionOperativaCitasModel.registrarAtencion(idCita, observacion_atencion);
      if (onSuccess) onSuccess(cita);
      return cita;
    } catch (err) {
      setError(err.message || "Error al registrar la consulta como atendida.");
      throw err;
    } finally {
      setGuardando(false);
    }
  };

  return {
    registrarAtencion,
    guardando,
    error,
    limpiarError: () => setError(null),
  };
}
