import React, { useState } from "react";
import { useReprogramarCitaController } from "../../../../controllers/Paquetes/P4_GestionCitas/CU12_ReprogramarCitaMedica/useReprogramarCitaController";
import { DisponibilidadModel } from "../../../../models/Paquetes/P4_GestionCitas/DisponibilidadModel";

export function UI_ReprogramarCita({ cita, onCancel, onUpdated }) {
  const { reprogramar, procesando, error, limpiarError } = useReprogramarCitaController(onUpdated);

  const [fechaCita, setFechaCita] = useState("");
  const [horaCita, setHoraCita] = useState("");
  const [motivo, setMotivo] = useState("");
  const [turnosDisponibles, setTurnosDisponibles] = useState([]);
  const [buscandoTurnos, setBuscandoTurnos] = useState(false);
  const [errorTurnos, setErrorTurnos] = useState(null);

  const consultarTurnos = async (fecha) => {
    setFechaCita(fecha);
    setHoraCita("");
    if (!fecha) return;

    setBuscandoTurnos(true);
    setErrorTurnos(null);
    try {
      const res = await DisponibilidadModel.consultar({
        id_medico: cita.id_medico,
        id_especialidad: cita.id_especialidad,
        fecha: fecha,
      });
      setTurnosDisponibles(res.turnos_disponibles || []);
      if (!res.turnos_disponibles || res.turnos_disponibles.length === 0) {
        setErrorTurnos("No hay turnos disponibles para el médico en la fecha seleccionada.");
      }
    } catch (err) {
      setErrorTurnos(err.message || "Error al buscar turnos disponibles.");
    } finally {
      setBuscandoTurnos(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!fechaCita || !horaCita) {
      return;
    }
    try {
      await reprogramar(cita.id_cita, {
        fecha_cita: fechaCita,
        hora_cita: horaCita,
        motivo_reprogramacion: motivo,
      });
    } catch {
      // El error lo maneja el hook useReprogramarCitaController
    }
  };

  return (
    <div className="card" style={{ maxWidth: "600px", margin: "0 auto", border: "2px solid #0284C7" }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "1rem" }}>
        <h3>Reprogramar Cita Médica</h3>
        <span className="badge badge-info">Código: {cita.codigo_unico?.slice(0, 8)}...</span>
      </div>

      <div style={{ background: "#F8FAFC", padding: "1rem", borderRadius: "8px", marginBottom: "1.2rem", fontSize: "0.9rem" }}>
        <div><strong>Paciente:</strong> {cita.paciente?.nombre_completo} (CI: {cita.paciente?.ci})</div>
        <div><strong>Médico:</strong> {cita.medico?.nombre_completo}</div>
        <div><strong>Especialidad:</strong> {cita.especialidad?.nombre_especialidad}</div>
        <div style={{ marginTop: "0.5rem", color: "#DC2626" }}>
          <strong>Horario Actual:</strong> {cita.fecha_cita} a las {cita.hora_cita?.slice(0, 5)}
        </div>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}
      {errorTurnos && <div className="alert alert-warning">{errorTurnos}</div>}

      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label>Nueva Fecha para la Cita *</label>
          <input
            type="date"
            className="form-control"
            value={fechaCita}
            min={new Date().toISOString().split("T")[0]}
            onChange={(e) => {
              limpiarError();
              consultarTurnos(e.target.value);
            }}
            required
          />
        </div>

        {buscandoTurnos && <div className="text-muted" style={{ marginBottom: "1rem" }}>Consultando agenda del especialista...</div>}

        {turnosDisponibles.length > 0 && (
          <div className="form-group">
            <label>Selecciona un Turno Disponible *</label>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(100px, 1fr))", gap: "0.5rem", marginTop: "0.5rem" }}>
              {turnosDisponibles.map((slot) => {
                const horaFormat = slot.hora_inicio.slice(0, 5);
                const isSelected = horaCita === horaFormat;
                return (
                  <button
                    type="button"
                    key={slot.hora_inicio}
                    onClick={() => {
                      setHoraCita(horaFormat);
                      limpiarError();
                    }}
                    style={{
                      padding: "0.5rem",
                      borderRadius: "6px",
                      border: isSelected ? "2px solid #0284C7" : "1px solid #CBD5E1",
                      background: isSelected ? "#E0F2FE" : "#FFFFFF",
                      color: isSelected ? "#0369A1" : "#1E293B",
                      fontWeight: isSelected ? 600 : 400,
                      cursor: "pointer",
                    }}
                  >
                    {horaFormat}
                  </button>
                );
              })}
            </div>
          </div>
        )}

        <div className="form-group">
          <label>Motivo de la Reprogramación (Opcional)</label>
          <textarea
            className="form-control"
            rows="2"
            value={motivo}
            onChange={(e) => setMotivo(e.target.value)}
            placeholder="Ej: El paciente solicitó cambio por motivos laborales"
          />
        </div>

        <div style={{ display: "flex", justifyContent: "flex-end", gap: "0.8rem", marginTop: "1.5rem" }}>
          <button type="button" className="btn btn-secondary" onClick={onCancel} disabled={procesando}>
            Cancelar
          </button>
          <button type="submit" className="btn btn-primary" disabled={procesando || !fechaCita || !horaCita}>
            {procesando ? "Guardando..." : "Confirmar Reprogramación"}
          </button>
        </div>
      </form>
    </div>
  );
}
