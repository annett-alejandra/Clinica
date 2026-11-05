import React, { useState } from "react";
import { useCitaAtendidaController } from "../../../../controllers/Paquetes/P5_ControlAtencion/CU15_RegistrarCitaAtendida/useCitaAtendidaController";

export function UI_CitaAtendida({ citas = [], onActualizar }) {
  const { registrarAtencion, guardando, error, limpiarError } = useCitaAtendidaController(onActualizar);
  const [citaSeleccionada, setCitaSeleccionada] = useState(null);
  const [observacion, setObservacion] = useState("");

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!citaSeleccionada) return;
    try {
      await registrarAtencion(citaSeleccionada.id_cita, observacion);
      setCitaSeleccionada(null);
      setObservacion("");
    } catch {
      // Manejado por el hook
    }
  };

  return (
    <div className="card">
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "1.5rem" }}>
        <div>
          <h2>Consultas Médicas (Atención Clínica)</h2>
          <p className="text-muted" style={{ margin: 0 }}>
            Visualiza tus pacientes citados para hoy y registra formalmente la atención con tus notas clínicas.
          </p>
        </div>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      <div className="table-responsive">
        <table className="table">
          <thead>
            <tr>
              <th>Hora</th>
              <th>Paciente</th>
              <th>Motivo de Consulta</th>
              <th>Llegada / Estado</th>
              <th>Acción Médica</th>
            </tr>
          </thead>
          <tbody>
            {citas.length === 0 ? (
              <tr>
                <td colSpan="5" className="text-center text-muted" style={{ padding: "2rem" }}>
                  No tienes pacientes pendientes de atención para hoy.
                </td>
              </tr>
            ) : (
              citas.map((cita) => {
                const estadoId = Number(cita.id_estado_cita);
                const atendida = estadoId === 3;
                return (
                  <tr key={cita.id_cita}>
                    <td>
                      <strong>{cita.hora_cita?.slice(0, 5)}</strong>
                      <div className="text-muted" style={{ fontSize: "0.8rem" }}>{cita.fecha_cita}</div>
                    </td>
                    <td>
                      <strong>{cita.paciente?.nombre_completo}</strong>
                      <div className="text-muted" style={{ fontSize: "0.8rem" }}>
                        CI: {cita.paciente?.ci} | Tel: {cita.paciente?.telefono}
                      </div>
                    </td>
                    <td>
                      <span style={{ fontSize: "0.9rem" }}>{cita.motivo || "Consulta de rutina"}</span>
                    </td>
                    <td>
                      {cita.fecha_hora_llegada ? (
                        <span className="badge badge-success">En Espera (Llegó)</span>
                      ) : (
                        <span className="badge badge-info">{cita.estado?.nombre_estado}</span>
                      )}
                    </td>
                    <td>
                      {atendida ? (
                        <span className="badge badge-success">✓ Atendida</span>
                      ) : (
                        <button
                          type="button"
                          className="btn btn-sm btn-primary"
                          onClick={() => {
                            limpiarError();
                            setCitaSeleccionada(cita);
                            setObservacion("");
                          }}
                        >
                          Atender Paciente
                        </button>
                      )}
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      {/* Modal para registrar atención y observaciones */}
      {citaSeleccionada && (
        <div
          style={{
            position: "fixed",
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: "rgba(0,0,0,0.5)",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            zIndex: 1000,
          }}
        >
          <div className="card" style={{ maxWidth: "600px", width: "95%" }}>
            <h3>Finalizar Atención Médica</h3>
            <div style={{ background: "#F8FAFC", padding: "0.8rem", borderRadius: "6px", marginBottom: "1rem", fontSize: "0.9rem" }}>
              <div><strong>Paciente:</strong> {citaSeleccionada.paciente?.nombre_completo}</div>
              <div><strong>Motivo inicial:</strong> {citaSeleccionada.motivo || "No especificado"}</div>
            </div>

            <form onSubmit={handleSubmit}>
              <div className="form-group">
                <label>Notas de la Consulta / Diagnóstico / Tratamiento *</label>
                <textarea
                  className="form-control"
                  rows="4"
                  value={observacion}
                  onChange={(e) => setObservacion(e.target.value)}
                  placeholder="Detalla las observaciones clínicas, indicaciones o prescripciones brindadas al paciente..."
                  required
                />
              </div>

              <div style={{ display: "flex", justifyContent: "flex-end", gap: "0.8rem", marginTop: "1rem" }}>
                <button
                  type="button"
                  className="btn btn-secondary"
                  onClick={() => setCitaSeleccionada(null)}
                  disabled={guardando}
                >
                  Cancelar
                </button>
                <button type="submit" className="btn btn-primary" disabled={guardando}>
                  {guardando ? "Consolidando..." : "Registrar Cita como Atendida"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
