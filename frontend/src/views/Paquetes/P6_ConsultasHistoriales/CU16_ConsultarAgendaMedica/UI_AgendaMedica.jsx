import React, { useState } from "react";
import { useAgendaMedicaController } from "../../../../controllers/Paquetes/P6_ConsultasHistoriales/CU16_ConsultarAgendaMedica/useAgendaMedicaController";

export function UI_AgendaMedica({ medicos = [], especialidades = [], onReprogramar, onCancelar }) {
  const { citas, filtros, loading, error, aplicarFiltros, limpiarFiltros } = useAgendaMedicaController();

  const [filtroLocal, setFiltroLocal] = useState({
    id_medico: "",
    id_especialidad: "",
    id_estado_cita: "",
    fecha_cita: "",
  });

  const handleInputChange = (e) => {
    setFiltroLocal({ ...filtroLocal, [e.target.name]: e.target.value });
  };

  const handleBuscar = (e) => {
    e.preventDefault();
    aplicarFiltros(filtroLocal);
  };

  const handleReset = () => {
    setFiltroLocal({
      id_medico: "",
      id_especialidad: "",
      id_estado_cita: "",
      fecha_cita: "",
    });
    limpiarFiltros();
  };

  return (
    <div className="card">
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "1.5rem" }}>
        <div>
          <h2>Agenda Médica General</h2>
          <p className="text-muted" style={{ margin: 0 }}>
            Consulta global y filtrada de todas las citas y turnos programados en la clínica.
          </p>
        </div>
        <span className="badge badge-info">{citas.length} Citas Registradas</span>
      </div>

      {/* Barra de Filtros */}
      <form onSubmit={handleBuscar} style={{ background: "#F8FAFC", padding: "1rem", borderRadius: "8px", marginBottom: "1.5rem" }}>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(180px, 1fr))", gap: "0.8rem", alignItems: "flex-end" }}>
          <div>
            <label style={{ fontSize: "0.85rem", fontWeight: 600 }}>Fecha:</label>
            <input
              type="date"
              name="fecha_cita"
              className="form-control"
              value={filtroLocal.fecha_cita}
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
                <option key={m.id_medico} value={m.id_medico}>
                  {m.nombre_completo}
                </option>
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
                <option key={esp.id_especialidad} value={esp.id_especialidad}>
                  {esp.nombre_especialidad}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label style={{ fontSize: "0.85rem", fontWeight: 600 }}>Estado:</label>
            <select
              name="id_estado_cita"
              className="form-control"
              value={filtroLocal.id_estado_cita}
              onChange={handleInputChange}
            >
              <option value="">Todos los estados</option>
              <option value="1">1 - Programada</option>
              <option value="2">2 - Confirmada</option>
              <option value="3">3 - Atendida</option>
              <option value="4">4 - Cancelada</option>
              <option value="5">5 - Inasistencia</option>
            </select>
          </div>

          <div style={{ display: "flex", gap: "0.5rem" }}>
            <button type="submit" className="btn btn-primary" style={{ flex: 1 }}>
              Filtrar
            </button>
            <button type="button" className="btn btn-secondary" onClick={handleReset}>
              Limpiar
            </button>
          </div>
        </div>
      </form>

      {error && <div className="alert alert-danger">{error}</div>}

      {/* Grilla / Listado de Citas */}
      {loading ? (
        <div className="text-center text-muted" style={{ padding: "2rem" }}>
          Cargando agenda médica...
        </div>
      ) : (
        <div className="table-responsive">
          <table className="table">
            <thead>
              <tr>
                <th>Código / Fecha</th>
                <th>Paciente</th>
                <th>Médico Responsable</th>
                <th>Especialidad</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              {citas.length === 0 ? (
                <tr>
                  <td colSpan="6" className="text-center text-muted" style={{ padding: "2rem" }}>
                    No se encontraron citas para los criterios seleccionados.
                  </td>
                </tr>
              ) : (
                citas.map((cita) => {
                  const estadoId = Number(cita.id_estado_cita);
                  const sePuedeModificar = estadoId === 1 || estadoId === 2;
                  return (
                    <tr key={cita.id_cita}>
                      <td>
                        <strong style={{ color: "#0284C7" }}>{cita.codigo_unico?.slice(0, 8)}...</strong>
                        <div style={{ fontSize: "0.85rem", fontWeight: 500 }}>
                          {cita.fecha_cita} | {cita.hora_cita?.slice(0, 5)}
                        </div>
                      </td>
                      <td>
                        <strong>{cita.paciente?.nombre_completo}</strong>
                        <div className="text-muted" style={{ fontSize: "0.8rem" }}>CI: {cita.paciente?.ci}</div>
                      </td>
                      <td>
                        <div>{cita.medico?.nombre_completo}</div>
                        <div className="text-muted" style={{ fontSize: "0.8rem" }}>Cons. {cita.medico?.numero_consultorio}</div>
                      </td>
                      <td>{cita.especialidad?.nombre_especialidad}</td>
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
                          {cita.estado?.nombre_estado}
                        </span>
                      </td>
                      <td>
                        {sePuedeModificar ? (
                          <div style={{ display: "flex", gap: "0.4rem" }}>
                            <button
                              type="button"
                              className="btn btn-sm btn-secondary"
                              onClick={() => onReprogramar && onReprogramar(cita)}
                            >
                              Reprogramar
                            </button>
                            <button
                              type="button"
                              className="btn btn-sm btn-danger"
                              onClick={() => onCancelar && onCancelar(cita)}
                            >
                              Cancelar
                            </button>
                          </div>
                        ) : (
                          <span className="text-muted" style={{ fontSize: "0.8rem" }}>Cerrada</span>
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
