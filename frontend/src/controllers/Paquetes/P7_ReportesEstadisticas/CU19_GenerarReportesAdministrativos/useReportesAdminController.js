import { useState, useEffect, useCallback } from "react";
import { ReportesModel } from "../../../../models/Paquetes/P7_ReportesEstadisticas/ReportesModel";

export function useReportesAdminController(initialFilters = {}) {
  const [reporte, setReporte] = useState(null);
  const [filtros, setFiltros] = useState(initialFilters);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const cargarReporte = useCallback(async (filtrosActuales = filtros) => {
    setLoading(true);
    setError(null);
    try {
      const data = await ReportesModel.generarReporte(filtrosActuales);
      setReporte(data);
    } catch (err) {
      setError(err.message || "Error al generar el reporte estadístico.");
    } finally {
      setLoading(false);
    }
  }, [filtros]);

  useEffect(() => {
    cargarReporte();
  }, [cargarReporte]);

  const aplicarFiltros = (nuevosFiltros) => {
    const fusionados = { ...filtros, ...nuevosFiltros };
    setFiltros(fusionados);
    cargarReporte(fusionados);
  };

  return {
    reporte,
    filtros,
    loading,
    error,
    cargarReporte,
    aplicarFiltros,
  };
}
