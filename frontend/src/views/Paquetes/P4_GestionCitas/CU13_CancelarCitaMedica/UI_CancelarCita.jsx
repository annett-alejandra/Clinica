import React, { useState } from "react";
import { useCancelarCitaController } from "../../../../controllers/Paquetes/P4_GestionCitas/CU13_CancelarCitaMedica/useCancelarCitaController";

export function UI_CancelarCita({ cita, onCancel, onUpdated }) {
  const { cancelar, cancelando, error, limpiarError } = useCancelarCitaController(onUpdated);
  const [motivo, setMotivo] = useState("");

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await cancelar(cita.id_cita, motivo);
    } catch {
      // Manejado por el hook
    }
  };

  return (
    <div className="card" style={{ maxWidth: "550px", margin: "0 auto", border: "2px solid #DC2626" }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "1rem" }}>
        <h3 style={{ color: "#DC2626" }}>Cancelar Cita Médica</h3>
        <span className="badge badge-danger">Acción Destructiva</span>
      </div>

      <p className="text-muted" style={{ fontSize: "0.95rem" }}>
        ¿Estás seguro de que deseas cancelar esta cita? Al cancelarla, el horario ocupado en la agenda del doctor se liberará de inmediato para otros pacientes.
      </p>

      <div style={{ background: "#FEF2F2", padding: "1rem", borderRadius: "8px", marginBottom: "1.2rem", fontSize: "0.9rem" }}>
        <div><strong>Código:</strong> {cita.codigo_unico}</div>
        <div><strong>Paciente:</strong> {cita.paciente?.nombre_completo}</div>
        <div><strong>Médico:</strong> {cita.medico?.nombre_completo}</div>
        <div><strong>Fecha y Hora:</strong> {cita.fecha_cita} {cita.hora_cita?.slice(0, 5)}</div>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}

      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label>Motivo de Cancelación</label>
          <textarea
            className="form-control"
            rows="3"
            value={motivo}
            onChange={(e) => {
              setMotivo(e.target.value);
              limpiarError();
            }}
            placeholder="Ej: Paciente notificó imprevisto personal / viaje imprevisto..."
          />
        </div>

        <div style={{ display: "flex", justifyContent: "flex-end", gap: "0.8rem", marginTop: "1.5rem" }}>
          <button type="button" className="btn btn-secondary" onClick={onCancel} disabled={cancelando}>
            Volver
          </button>
          <button type="submit" className="btn btn-danger" disabled={cancelando}>
            {cancelando ? "Cancelando cita..." : "Sí, Cancelar Cita"}
          </button>
        </div>
      </form>
    </div>
  );
}
