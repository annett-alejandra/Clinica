import { useState, useCallback } from "react";
import { ConsultasHistorialesModel } from "../../../../models/Paquetes/P6_ConsultasHistoriales/ConsultasHistorialesModel";

export function useHistorialCitasPacienteController() {
  const [historial, setHistorial] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const buscarHistorial = useCallback(async (identificador) => {
    if (!identificador || !identificador.trim()) {
      setError("Por favor ingrese un número de cédula (CI) o ID de paciente.");
      return;
    }
    setLoading(true);
    setError(null);
    try {
      const data = await ConsultasHistorialesModel.consultarHistorialPaciente(identificador.trim());
      setHistorial(data);
    } catch (err) {
      setHistorial(null);
      setError(err.message || "No se encontró historial clínico para este paciente.");
    } finally {
      setLoading(false);
    }
  }, []);

  return {
    historial,
    loading,
    error,
    buscarHistorial,
    limpiarBusqueda: () => {
      setHistorial(null);
      setError(null);
    },
  };
}
