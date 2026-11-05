import React, { useState } from "react";
import { useMisCitasController } from "../../../../controllers/Paquetes/P6_ConsultasHistoriales/CU18_ConsultarMisCitas/useMisCitasController";

export function UI_MisCitas({ idPaciente = null }) {
  const { paciente, proximas_citas, historial_citas, total_proximas, total_historial, loading, error, recargar } =
    useMisCitasController(idPaciente);

  const [pestaña, setPestaña] = useState("proximas"); // 'proximas' | 'historial'

  if (loading) {
    return <div className="card text-center text-muted">Cargando tus citas médicas...</div>;
  }

  return (
    <div className="card">
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "1.5rem" }}>
        <div>
          <h2>Mis Citas Médicas</h2>
          <p className="text-muted" style={{ margin: 0 }}>
            Revisa tus próximas consultas agendadas y consulta el historial de atenciones anteriores.
          </p>
        </div>
        <button type="button" className="btn btn-sm btn-secondary" onClick={recargar}>
          Actualizar
        </button>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      {/* Selector de pestañas */}
      <div style={{ display: "flex", gap: "1rem", borderBottom: "2px solid #E2E8F0", marginBottom: "1.5rem" }}>
        <button
          type="button"
          onClick={() => setPestaña("proximas")}
          style={{
            background: "none",
            border: "none",
            padding: "0.8rem 1rem",
            fontSize: "1rem",
            fontWeight: 600,
            cursor: "pointer",
            borderBottom: pestaña === "proximas" ? "3px solid #0284C7" : "none",
            color: pestaña === "proximas" ? "#0284C7" : "#64748B",
          }}
        >
          Próximas Consultas ({total_proximas})
        </button>
        <button
          type="button"
          onClick={() => setPestaña("historial")}
          style={{
            background: "none",
            border: "none",
            padding: "0.8rem 1rem",
            fontSize: "1rem",
            fontWeight: 600,
            cursor: "pointer",
            borderBottom: pestaña === "historial" ? "3px solid #0284C7" : "none",
            color: pestaña === "historial" ? "#0284C7" : "#64748B",
          }}
        >
          Historial Pasado ({total_historial})
        </button>
      </div>

      {/* Contenido pestaña: Próximas Citas */}
      {pestaña === "proximas" && (
        <div style={{ display: "grid", gap: "1rem" }}>
          {proximas_citas.length === 0 ? (
            <div className="text-center text-muted" style={{ padding: "2rem", background: "#F8FAFC", borderRadius: "8px" }}>
              No tienes ninguna consulta futura programada por el momento.
            </div>
          ) : (
            proximas_citas.map((cita) => (
              <div
                key={cita.id_cita}
                style={{
                  border: "1px solid #BAE6FD",
                  background: "#F0F9FF",
                  borderRadius: "8px",
                  padding: "1.2rem",
                  display: "flex",
                  justifyContent: "space-between",
                  alignItems: "center",
                  flexWrap: "wrap",
                  gap: "1rem",
                }}
              >
                <div>
                  <div style={{ display: "flex", alignItems: "center", gap: "0.6rem", marginBottom: "0.4rem" }}>
                    <span className="badge badge-info">{cita.especialidad?.nombre_especialidad}</span>
                    <span className="badge badge-warning">{cita.estado?.nombre_estado}</span>
                  </div>
                  <h3 style={{ margin: "0 0 0.4rem 0", color: "#0369A1" }}>{cita.medico?.nombre_completo}</h3>
                  <div style={{ fontSize: "0.9rem", color: "#334155" }}>
                    <strong>Consultorio:</strong> {cita.medico?.numero_consultorio || "Principal"} | <strong>Código:</strong> {cita.codigo_unico}
                  </div>
                </div>

                <div style={{ textAlign: "right" }}>
                  <div style={{ fontSize: "1.3rem", fontWeight: 700, color: "#0284C7" }}>
                    {cita.hora_cita?.slice(0, 5)}
                  </div>
                  <div style={{ fontWeight: 500, color: "#475569" }}>{cita.fecha_cita}</div>
                  <div className="text-muted" style={{ fontSize: "0.8rem" }}>Duración: {cita.duracion_minutos} min</div>
                </div>
              </div>
            ))
          )}
        </div>
      )}

      {/* Contenido pestaña: Historial de Consultas */}
      {pestaña === "historial" && (
        <div className="table-responsive">
          <table className="table">
            <thead>
              <tr>
                <th>Fecha y Hora</th>
                <th>Especialidad / Médico</th>
                <th>Estado</th>
                <th>Observación / Diagnóstico</th>
              </tr>
            </thead>
            <tbody>
              {historial_citas.length === 0 ? (
                <tr>
                  <td colSpan="4" className="text-center text-muted" style={{ padding: "2rem" }}>
                    Aún no cuentas con historial de consultas pasadas.
                  </td>
                </tr>
              ) : (
                historial_citas.map((c) => {
                  const estadoId = Number(c.id_estado_cita);
                  return (
                    <tr key={c.id_cita}>
                      <td>
                        <strong>{c.fecha_cita}</strong>
                        <div className="text-muted" style={{ fontSize: "0.85rem" }}>{c.hora_cita?.slice(0, 5)}</div>
                      </td>
                      <td>
                        <strong>{c.especialidad?.nombre_especialidad}</strong>
                        <div className="text-muted" style={{ fontSize: "0.85rem" }}>{c.medico?.nombre_completo}</div>
                      </td>
                      <td>
                        <span
                          className={`badge ${
                            estadoId === 3
                              ? "badge-success"
                              : estadoId === 4
                              ? "badge-danger"
                              : "badge-secondary"
                          }`}
                        >
                          {c.estado?.nombre_estado}
                        </span>
                      </td>
                      <td>
                        {c.observacion_atencion ? (
                          <div style={{ fontSize: "0.9rem" }}>{c.observacion_atencion}</div>
                        ) : c.motivo_cancelacion ? (
                          <div style={{ fontSize: "0.85rem", color: "#DC2626" }}>
                            Cancelada: {c.motivo_cancelacion}
                          </div>
                        ) : (
                          <span className="text-muted">—</span>
                        )}
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
