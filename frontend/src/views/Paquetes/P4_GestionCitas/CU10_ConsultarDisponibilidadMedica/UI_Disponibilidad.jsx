import React from 'react';
import { useDisponibilidadController } from '../../../../controllers/Paquetes/P4_GestionCitas/CU10_ConsultarDisponibilidadMedica/useDisponibilidadController';

export function UI_Disponibilidad({ onSeleccionarTurno }) {
  const {
    especialidades,
    medicos,
    idEspecialidad,
    setIdEspecialidad,
    idMedico,
    setIdMedico,
    fecha,
    setFecha,
    oferta,
    loading,
    error,
    consultar,
  } = useDisponibilidadController();

  const handleConsultar = (e) => {
    e.preventDefault();
    consultar();
  };

  return (
    <div className="module-container">
      <div className="module-header">
        <div>
          <span className="badge-overline">PAQUETE 04 · CU10</span>
          <h2>Consulta de Oferta y Disponibilidad Médica</h2>
          <p className="subtitle">Buscador en tiempo real de turnos y cupos libres, cruzando horarios y citas existentes.</p>
        </div>
      </div>

      <div className="filter-card">
        <form onSubmit={handleConsultar} className="filter-grid">
          <div className="form-group">
            <label>Especialidad Requerida *</label>
            <select
              className="input-field"
              value={idEspecialidad}
              onChange={(e) => setIdEspecialidad(e.target.value)}
              required
            >
              <option value="">Seleccione especialidad...</option>
              {especialidades.map((e) => (
                <option key={e.id_especialidad} value={e.id_especialidad}>
                  {e.nombre_especialidad}
                </option>
              ))}
            </select>
          </div>

          <div className="form-group">
            <label>Médico Especialista (Opcional)</label>
            <select
              className="input-field"
              value={idMedico}
              onChange={(e) => setIdMedico(e.target.value)}
            >
              <option value="">Cualquier médico disponible...</option>
              {medicos.map((m) => (
                <option key={m.id_medico} value={m.id_medico}>
                  {m.nombre_completo}
                </option>
              ))}
            </select>
          </div>

          <div className="form-group">
            <label>Fecha de Consulta *</label>
            <input
              type="date"
              className="input-field"
              value={fecha}
              min={new Date().toISOString().split('T')[0]}
              onChange={(e) => setFecha(e.target.value)}
              required
            />
          </div>

          <div className="form-group filter-btn-col">
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? 'Calculando cupos...' : 'Consultar Disponibilidad'}
            </button>
          </div>
        </form>
      </div>

      {error && <div className="alert alert-error">{error}</div>}

      <div className="results-container">
        {loading && <p className="text-center">Analizando agenda médica en tiempo real...</p>}

        {!loading && oferta.length === 0 && (
          <div className="empty-state">
            <p>Seleccione especialidad y fecha, luego presione <strong>Consultar Disponibilidad</strong> para ver los turnos disponibles.</p>
          </div>
        )}

        {!loading && oferta.map((item, idx) => (
          <div key={idx} className="availability-card">
            <div className="availability-header">
              <div>
                <span className="badge-specialty">{item.especialidad?.nombre_especialidad}</span>
                <h3>{item.medico?.nombre_completo}</h3>
                <small className="text-muted">Registro: {item.medico?.registro_profesional} · Fecha: {item.fecha}</small>
              </div>
              <span className="badge-count-success">{item.total_cupos} cupos libres</span>
            </div>

            <div className="slots-grid">
              {item.cupos.map((slot, sIdx) => (
                <button
                  key={sIdx}
                  type="button"
                  className="slot-pill"
                  onClick={() =>
                    onSeleccionarTurno &&
                    onSeleccionarTurno({
                      medico: item.medico,
                      especialidad: item.especialidad,
                      fecha: item.fecha,
                      slot: slot,
                    })
                  }
                  title="Click para reservar este turno"
                >
                  <span className="slot-time">{slot.hora_inicio}</span>
                  <span className="slot-dur">{slot.duracion_minutos} min</span>
                </button>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

