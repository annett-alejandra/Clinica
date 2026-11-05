import { useState, useEffect, useCallback } from "react";
import { ConsultasHistorialesModel } from "../../../../models/Paquetes/P6_ConsultasHistoriales/ConsultasHistorialesModel";

export function useAgendaMedicaController(initialFilters = {}) {
  const [citas, setCitas] = useState([]);
  const [filtros, setFiltros] = useState(initialFilters);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const cargarAgenda = useCallback(async (filtrosActuales = filtros) => {
    setLoading(true);
    setError(null);
    try {
      const data = await ConsultasHistorialesModel.consultarAgenda(filtrosActuales);
      setCitas(data || []);
    } catch (err) {
      setError(err.message || "Error al consultar la agenda médica.");
    } finally {
      setLoading(false);
    }
  }, [filtros]);

  useEffect(() => {
    cargarAgenda();
  }, [cargarAgenda]);

  const aplicarFiltros = (nuevosFiltros) => {
    const fusionados = { ...filtros, ...nuevosFiltros };
    setFiltros(fusionados);
    cargarAgenda(fusionados);
  };

  return {
    citas,
    filtros,
    loading,
    error,
    cargarAgenda,
    aplicarFiltros,
    limpiarFiltros: () => {
      setFiltros({});
      cargarAgenda({});
    },
  };
}
