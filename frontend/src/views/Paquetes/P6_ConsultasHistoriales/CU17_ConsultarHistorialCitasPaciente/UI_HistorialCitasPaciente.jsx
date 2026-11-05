import React, { useState } from "react";
import { useHistorialCitasPacienteController } from "../../../../controllers/Paquetes/P6_ConsultasHistoriales/CU17_ConsultarHistorialCitasPaciente/useHistorialCitasPacienteController";

export function UI_HistorialCitasPaciente() {
  const { historial, loading, error, buscarHistorial, limpiarBusqueda } = useHistorialCitasPacienteController();
  const [identificador, setIdentificador] = useState("");

  const handleBuscar = (e) => {
    e.preventDefault();
    buscarHistorial(identificador);
  };

  const paciente = historial?.paciente;
  const citas = historial?.citas || [];

  return (
    <div className="card">
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "1.5rem" }}>
        <div>
          <h2>Expediente e Historial de Citas por Paciente</h2>
          <p className="text-muted" style={{ margin: 0 }}>
            Consulta el recorrido clínico y las citas históricas ingresando la Cédula de Identidad (CI) o ID.
          </p>
        </div>
      </div>

      {/* Formulario de Búsqueda */}
      <form onSubmit={handleBuscar} style={{ maxWidth: "500px", display: "flex", gap: "0.5rem", marginBottom: "1.5rem" }}>
        <input
          type="text"
          className="form-control"
          placeholder="Ingrese CI del paciente (Ej: 10203040)"
          value={identificador}
          onChange={(e) => setIdentificador(e.target.value)}
          required
        />
        <button type="submit" className="btn btn-primary" disabled={loading}>
          {loading ? "Buscando..." : "Buscar"}
        </button>
        {historial && (
          <button
            type="button"
            className="btn btn-secondary"
            onClick={() => {
              setIdentificador("");
              limpiarBusqueda();
            }}
          >
            Limpiar
          </button>
        )}
      </form>

      {error && <div className="alert alert-danger">{error}</div>}

      {/* Ficha Resumen del Paciente */}
      {paciente && (
        <div style={{ background: "#F1F5F9", padding: "1.2rem", borderRadius: "8px", marginBottom: "1.5rem" }}>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))", gap: "1rem" }}>
            <div>
              <strong style={{ fontSize: "0.85rem", color: "#64748B" }}>Nombre del Paciente:</strong>
              <div style={{ fontWeight: 600, fontSize: "1.1rem" }}>{paciente.nombre_completo}</div>
            </div>
            <div>
              <strong style={{ fontSize: "0.85rem", color: "#64748B" }}>Cédula (CI):</strong>
              <div style={{ fontWeight: 600 }}>{paciente.ci}</div>
            </div>
            <div>
              <strong style={{ fontSize: "0.85rem", color: "#64748B" }}>Contacto:</strong>
              <div>{paciente.telefono} | {paciente.correo}</div>
            </div>
            <div>
              <strong style={{ fontSize: "0.85rem", color: "#64748B" }}>Total de Citas Históricas:</strong>
              <div><span className="badge badge-info">{historial.total_citas} Citas</span></div>
            </div>
          </div>
        </div>
      )}

      {/* Tabla de Citas Históricas */}
      {paciente && (
        <div className="table-responsive">
          <table className="table">
            <thead>
              <tr>
                <th>Fecha y Hora</th>
                <th>Especialidad / Médico</th>
                <th>Motivo de Consulta</th>
                <th>Estado Final</th>
                <th>Observación Clínica / Atención</th>
              </tr>
            </thead>
            <tbody>
              {citas.length === 0 ? (
                <tr>
                  <td colSpan="5" className="text-center text-muted" style={{ padding: "2rem" }}>
                    Este paciente aún no tiene citas registradas.
                  </td>
                </tr>
              ) : (
                citas.map((c) => {
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
                      <td>{c.motivo || "No especificado"}</td>
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
                          {c.estado?.nombre_estado}
                        </span>
                      </td>
                      <td>
                        {c.observacion_atencion ? (
                          <div style={{ fontSize: "0.9rem", color: "#1E293B" }}>
                            {c.observacion_atencion}
                          </div>
                        ) : c.motivo_cancelacion ? (
                          <div style={{ fontSize: "0.85rem", color: "#DC2626" }}>
                            <em>Cancelada: {c.motivo_cancelacion}</em>
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
