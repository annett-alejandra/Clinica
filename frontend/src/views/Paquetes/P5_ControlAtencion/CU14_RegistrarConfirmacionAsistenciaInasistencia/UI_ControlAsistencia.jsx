import React, { useState } from "react";
import { useControlAsistenciaController } from "../../../../controllers/Paquetes/P5_ControlAtencion/CU14_RegistrarConfirmacionAsistenciaInasistencia/useControlAsistenciaController";

export function UI_ControlAsistencia({ citas = [], onActualizar }) {
  const { confirmar, registrarLlegada, marcarInasistencia, procesando, error, limpiarError } =
    useControlAsistenciaController(onActualizar);

  const [modalInasistencia, setModalInasistencia] = useState(null);
  const [motivoInasistencia, setMotivoInasistencia] = useState("");

  const handleInasistenciaSubmit = async (e) => {
    e.preventDefault();
    if (!modalInasistencia) return;
    try {
      await marcarInasistencia(modalInasistencia.id_cita, motivoInasistencia);
      setModalInasistencia(null);
      setMotivoInasistencia("");
    } catch {
      // Manejado por el hook
    }
  };

  return (
    <div className="card">
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "1.5rem" }}>
        <div>
          <h2>Control Operativo de Citas</h2>
          <p className="text-muted" style={{ margin: 0 }}>
            Gestiona confirmaciones previas, registro de llegada a recepción y marcación de inasistencia.
          </p>
        </div>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      <div className="table-responsive">
        <table className="table">
          <thead>
            <tr>
              <th>Hora / Fecha</th>
              <th>Paciente</th>
              <th>Médico / Especialidad</th>
              <th>Estado Actual</th>
              <th>Llegada Física</th>
              <th>Acciones Operativas</th>
            </tr>
          </thead>
          <tbody>
            {citas.length === 0 ? (
              <tr>
                <td colSpan="6" className="text-center text-muted" style={{ padding: "2rem" }}>
                  No hay citas pendientes de atención operativa para mostrar.
                </td>
              </tr>
            ) : (
              citas.map((cita) => {
                const estadoId = Number(cita.id_estado_cita);
                return (
                  <tr key={cita.id_cita}>
                    <td>
                      <strong>{cita.hora_cita?.slice(0, 5)}</strong>
                      <div className="text-muted" style={{ fontSize: "0.8rem" }}>{cita.fecha_cita}</div>
                    </td>
                    <td>
                      <strong>{cita.paciente?.nombre_completo}</strong>
                      <div className="text-muted" style={{ fontSize: "0.8rem" }}>CI: {cita.paciente?.ci}</div>
                    </td>
                    <td>
                      <div>{cita.medico?.nombre_completo}</div>
                      <span className="badge badge-info">{cita.especialidad?.nombre_especialidad}</span>
                    </td>
                    <td>
                      <span
                        className={`badge ${
                          estadoId === 1
                            ? "badge-warning"
                            : estadoId === 2
                            ? "badge-info"
                            : estadoId === 3
                            ? "badge-success"
                            : "badge-danger"
                        }`}
                      >
                        {cita.estado?.nombre_estado || "Estado " + estadoId}
                      </span>
                    </td>
                    <td>
                      {cita.fecha_hora_llegada ? (
                        <span style={{ color: "#0D9488", fontWeight: 600 }}>
                          ✓ {new Date(cita.fecha_hora_llegada).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}
                        </span>
                      ) : (
                        <span className="text-muted">Pendiente</span>
                      )}
                    </td>
                    <td>
                      <div style={{ display: "flex", gap: "0.4rem", flexWrap: "wrap" }}>
                        {/* Confirmar si está Programada (1) */}
                        {estadoId === 1 && (
                          <button
                            type="button"
                            className="btn btn-sm btn-secondary"
                            onClick={() => {
                              limpiarError();
                              confirmar(cita.id_cita);
                            }}
                            disabled={procesando}
                            title="Confirmar por teléfono o aviso"
                          >
                            Confirmar
                          </button>
                        )}

                        {/* Registrar llegada si está en 1 o 2 */}
                        {(estadoId === 1 || estadoId === 2) && (
                          <button
                            type="button"
                            className="btn btn-sm btn-primary"
                            onClick={() => {
                              limpiarError();
                              registrarLlegada(cita.id_cita);
                            }}
                            disabled={procesando}
                            title="Registrar que el paciente llegó a sala de espera"
                          >
                            Llegó a Clínica
                          </button>
                        )}

                        {/* Inasistencia si está en 1 o 2 */}
                        {(estadoId === 1 || estadoId === 2) && (
                          <button
                            type="button"
                            className="btn btn-sm btn-danger"
                            onClick={() => {
                              limpiarError();
                              setModalInasistencia(cita);
                            }}
                            disabled={procesando}
                            title="Marcar falta del paciente"
                          >
                            Inasistencia
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      {/* Modal para motivo de Inasistencia */}
      {modalInasistencia && (
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
          <div className="card" style={{ maxWidth: "450px", width: "90%" }}>
            <h3>Marcar Inasistencia</h3>
            <p className="text-muted" style={{ fontSize: "0.9rem" }}>
              Paciente: <strong>{modalInasistencia.paciente?.nombre_completo}</strong>
            </p>
            <form onSubmit={handleInasistenciaSubmit}>
              <div className="form-group">
                <label>Observación o Motivo</label>
                <textarea
                  className="form-control"
                  rows="3"
                  value={motivoInasistencia}
                  onChange={(e) => setMotivoInasistencia(e.target.value)}
                  placeholder="Ej: Pasaron más de 20 minutos de tolerancia y no contestó llamadas..."
                />
              </div>
              <div style={{ display: "flex", justifyContent: "flex-end", gap: "0.5rem" }}>
                <button
                  type="button"
                  className="btn btn-secondary"
                  onClick={() => setModalInasistencia(null)}
                  disabled={procesando}
                >
                  Cancelar
                </button>
                <button type="submit" className="btn btn-danger" disabled={procesando}>
                  {procesando ? "Guardando..." : "Confirmar Inasistencia"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
