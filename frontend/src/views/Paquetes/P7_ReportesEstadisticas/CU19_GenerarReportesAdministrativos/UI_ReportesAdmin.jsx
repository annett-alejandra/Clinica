import React, { useState } from "react";
import { useReportesAdminController } from "../../../../controllers/Paquetes/P7_ReportesEstadisticas/CU19_GenerarReportesAdministrativos/useReportesAdminController";

export function UI_ReportesAdmin({ medicos = [], especialidades = [] }) {
  const { reporte, filtros, loading, error, aplicarFiltros } = useReportesAdminController();

  const [filtroLocal, setFiltroLocal] = useState({
    fecha_desde: "",
    fecha_hasta: "",
    id_medico: "",
    id_especialidad: "",
  });

  const handleInputChange = (e) => {
    setFiltroLocal({ ...filtroLocal, [e.target.name]: e.target.value });
  };

  const handleFiltrar = (e) => {
    e.preventDefault();
    aplicarFiltros(filtroLocal);
  };

  const kpis = reporte?.kpis;
  const distEstados = reporte?.distribucion_estados || [];
  const porEspecialidad = reporte?.por_especialidad || [];
  const porMedico = reporte?.por_medico || [];

  return (
    <div className="card">
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "1.5rem" }}>
        <div>
          <h2>Reportes Administrativos y Estadísticas</h2>
          <p className="text-muted" style={{ margin: 0 }}>
            Métricas clave, volúmenes de atención, efectividad e inasistencias en la clínica.
          </p>
        </div>
      </div>

      {/* Filtros de Rango */}
      <form onSubmit={handleFiltrar} style={{ background: "#F8FAFC", padding: "1rem", borderRadius: "8px", marginBottom: "1.5rem" }}>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(180px, 1fr))", gap: "0.8rem", alignItems: "flex-end" }}>
          <div>
            <label style={{ fontSize: "0.85rem", fontWeight: 600 }}>Fecha Desde:</label>
            <input
              type="date"
              name="fecha_desde"
              className="form-control"
              value={filtroLocal.fecha_desde}
              onChange={handleInputChange}
            />
          </div>

          <div>
            <label style={{ fontSize: "0.85rem", fontWeight: 600 }}>Fecha Hasta:</label>
            <input
              type="date"
              name="fecha_hasta"
              className="form-control"
              value={filtroLocal.fecha_hasta}
              onChange={handleInputChange}
            />
          </div>

          <div>
            <label style={{ fontSize: "0.85rem", fontWeight: 600 }}>Médico:</label>
            <select
              name="id_medico"
              className="form-control"
              value={filtroLocal.id_medico}
              onChange={handleInputChange}
            >
              <option value="">Todos los médicos</option>
              {medicos.map((m) => (
                <option key={m.id_medico} value={m.id_medico}>{m.nombre_completo}</option>
              ))}
            </select>
          </div>

          <div>
            <label style={{ fontSize: "0.85rem", fontWeight: 600 }}>Especialidad:</label>
            <select
              name="id_especialidad"
              className="form-control"
              value={filtroLocal.id_especialidad}
              onChange={handleInputChange}
            >
              <option value="">Todas</option>
              {especialidades.map((esp) => (
                <option key={esp.id_especialidad} value={esp.id_especialidad}>{esp.nombre_especialidad}</option>
              ))}
            </select>
          </div>

          <div>
            <button type="submit" className="btn btn-primary" style={{ width: "100%" }} disabled={loading}>
              {loading ? "Calculando..." : "Generar Reporte"}
            </button>
          </div>
        </div>
      </form>

      {error && <div className="alert alert-danger">{error}</div>}

      {/* Tarjetas de Métricas Clave (KPIs) */}
      {kpis && (
        <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(180px, 1fr))", gap: "1rem", marginBottom: "2rem" }}>
          <div style={{ background: "#F1F5F9", padding: "1.2rem", borderRadius: "8px", borderLeft: "4px solid #0284C7" }}>
            <div className="text-muted" style={{ fontSize: "0.85rem", fontWeight: 600 }}>TOTAL CITAS</div>
            <div style={{ fontSize: "1.8rem", fontWeight: 700, color: "#0F172A" }}>{kpis.total_citas}</div>
          </div>

          <div style={{ background: "#ECFDF5", padding: "1.2rem", borderRadius: "8px", borderLeft: "4px solid #10B981" }}>
            <div className="text-muted" style={{ fontSize: "0.85rem", fontWeight: 600 }}>ATENDIDAS</div>
            <div style={{ fontSize: "1.8rem", fontWeight: 700, color: "#047857" }}>{kpis.atendidas}</div>
            <div style={{ fontSize: "0.8rem", color: "#059669" }}>{kpis.tasa_efectividad_pct}% del total</div>
          </div>

          <div style={{ background: "#FEF2F2", padding: "1.2rem", borderRadius: "8px", borderLeft: "4px solid #EF4444" }}>
            <div className="text-muted" style={{ fontSize: "0.85rem", fontWeight: 600 }}>CANCELADAS</div>
            <div style={{ fontSize: "1.8rem", fontWeight: 700, color: "#B91C1C" }}>{kpis.canceladas}</div>
            <div style={{ fontSize: "0.8rem", color: "#DC2626" }}>{kpis.tasa_cancelacion_pct}% del total</div>
          </div>

          <div style={{ background: "#FFFBEB", padding: "1.2rem", borderRadius: "8px", borderLeft: "4px solid #F59E0B" }}>
            <div className="text-muted" style={{ fontSize: "0.85rem", fontWeight: 600 }}>INASISTENCIAS</div>
            <div style={{ fontSize: "1.8rem", fontWeight: 700, color: "#B45309" }}>{kpis.inasistencias}</div>
            <div style={{ fontSize: "0.8rem", color: "#D97706" }}>{kpis.tasa_inasistencia_pct}% del total</div>
          </div>
        </div>
      )}

      {/* Distribución por Estados */}
      {distEstados.length > 0 && (
        <div style={{ marginBottom: "2rem" }}>
          <h3 style={{ fontSize: "1.1rem", marginBottom: "0.8rem" }}>Distribución por Estados de Citas</h3>
          <div className="table-responsive">
            <table className="table">
              <thead>
                <tr>
                  <th>Estado</th>
                  <th>Cantidad de Citas</th>
                  <th>Porcentaje</th>
                </tr>
              </thead>
              <tbody>
                {distEstados.map((row) => (
                  <tr key={row.id_estado_cita}>
                    <td><strong>{row.nombre_estado}</strong></td>
                    <td>{row.total}</td>
                    <td>
                      <div style={{ display: "flex", alignItems: "center", gap: "0.5rem" }}>
                        <div style={{ flex: 1, background: "#E2E8F0", height: "8px", borderRadius: "4px", overflow: "hidden" }}>
                          <div style={{ width: `${row.porcentaje}%`, background: "#0284C7", height: "100%" }} />
                        </div>
                        <span style={{ fontSize: "0.85rem", minWidth: "45px" }}>{row.porcentaje}%</span>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Volúmenes por Especialidad y Médico */}
      <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "1.5rem" }}>
        <div>
          <h3 style={{ fontSize: "1.1rem", marginBottom: "0.8rem" }}>Volumen por Especialidad</h3>
          <div className="table-responsive">
            <table className="table">
              <thead>
                <tr>
                  <th>Especialidad</th>
                  <th>Citas</th>
                </tr>
              </thead>
              <tbody>
                {porEspecialidad.length === 0 ? (
                  <tr><td colSpan="2" className="text-muted text-center">Sin datos</td></tr>
                ) : (
                  porEspecialidad.map((esp) => (
                    <tr key={esp.id_especialidad}>
                      <td>{esp.nombre_especialidad}</td>
                      <td><strong>{esp.total}</strong></td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        <div>
          <h3 style={{ fontSize: "1.1rem", marginBottom: "0.8rem" }}>Volumen por Profesional Médico</h3>
          <div className="table-responsive">
            <table className="table">
              <thead>
                <tr>
                  <th>Médico</th>
                  <th>Citas</th>
                </tr>
              </thead>
              <tbody>
                {porMedico.length === 0 ? (
                  <tr><td colSpan="2" className="text-muted text-center">Sin datos</td></tr>
                ) : (
                  porMedico.map((med) => (
                    <tr key={med.id_medico}>
                      <td>{med.nombre_completo}</td>
                      <td><strong>{med.total}</strong></td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
