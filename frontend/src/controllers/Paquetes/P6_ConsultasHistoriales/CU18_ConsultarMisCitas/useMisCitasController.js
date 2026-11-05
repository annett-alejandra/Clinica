import { useState, useEffect, useCallback } from "react";
import { ConsultasHistorialesModel } from "../../../../models/Paquetes/P6_ConsultasHistoriales/ConsultasHistorialesModel";

export function useMisCitasController(idPaciente = null) {
  const [dataCitas, setDataCitas] = useState({
    paciente: null,
    proximas_citas: [],
    historial_citas: [],
    total_proximas: 0,
    total_historial: 0,
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const cargarMisCitas = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await ConsultasHistorialesModel.consultarMisCitas(idPaciente);
      setDataCitas(data);
    } catch (err) {
      setError(err.message || "Error al cargar sus citas médicas.");
    } finally {
      setLoading(false);
    }
  }, [idPaciente]);

  useEffect(() => {
    cargarMisCitas();
  }, [cargarMisCitas]);

  return {
    ...dataCitas,
    loading,
    error,
    recargar: cargarMisCitas,
  };
}
