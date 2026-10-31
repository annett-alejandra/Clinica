import React, { useState } from 'react';
import { useHorarioController } from '../../../../controllers/Paquetes/P2_PersonalMedico/CU06_GestionarHorariosAtencion/useHorarioController';

export function UI_HorarioAtencion() {
  const {
    horarios,
    medicos,
    medicoSeleccionado,
    setMedicoSeleccionado,
    loading,
    error,
    success,
    crearHorario,
    eliminarHorario,
  } = useHorarioController();

  const [mostrarModal, setMostrarModal] = useState(false);
  const [idMedicoForm, setIdMedicoForm] = useState('');
  const [diaSemana, setDiaSemana] = useState('1');
  const [horaInicio, setHoraInicio] = useState('08:00');
  const [horaFin, setHoraFin] = useState('12:00');
  const [duracionMinutos, setDuracionMinutos] = useState('30');

  const dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!idMedicoForm || !diaSemana || !horaInicio || !horaFin) return;
    try {
      await crearHorario({
        id_medico: Number(idMedicoForm),
        dia_semana: Number(diaSemana),
        hora_inicio: horaInicio,
        hora_fin: horaFin,
        duracion_cita_minutos: Number(duracionMinutos),
      });
      setMostrarModal(false);
    } catch {
      // Error manejado en hook
    }
  };

  return (
    <div className="module-container">
      <div className="module-header">
        <div>
          <span className="badge-overline">PAQUETE 02 · CU06</span>
          <h2>Horarios y Turnos de Atención</h2>
          <p className="subtitle">Configuración semanal de turnos con prevención estricta de superposiciones.</p>
        </div>
        <div className="header-actions">
          <select
            className="input-field select-filter"
            value={medicoSeleccionado}
            onChange={(e) => setMedicoSeleccionado(e.target.value)}
          >
            <option value="">Todos los médicos...</option>
            {medicos.map((m) => (
              <option key={m.id_medico} value={m.id_medico}>
                {m.nombre_completo}
              </option>
            ))}
          </select>
          <button type="button" className="btn btn-primary" onClick={() => setMostrarModal(true)}>
            + Asignar Turno
          </button>
        </div>
      </div>

      {error && <div className="alert alert-error">{error}</div>}
      {success && <div className="alert alert-success">{success}</div>}

      <div className="table-responsive">
        <table className="data-table">
          <thead>
            <tr>
              <th>Médico Especialista</th>
              <th>Día de la Semana</th>
              <th>Horario de Atención</th>
              <th>Duración por Cita</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {loading && horarios.length === 0 ? (
              <tr><td colSpan="5" className="text-center">Cargando turnos...</td></tr>
            ) : horarios.map((h) => (
              <tr key={h.id_horario}>
                <td className="font-semibold">{h.medico?.nombre_completo || `Médico #${h.id_medico}`}</td>
                <td>
                  <span className="badge-day">{dias[h.dia_semana - 1] || `Día ${h.dia_semana}`}</span>
                </td>
                <td>
                  <strong>{h.hora_inicio?.substring(0, 5)}</strong> a <strong>{h.hora_fin?.substring(0, 5)}</strong>
                </td>
                <td>{h.duracion_cita_minutos} minutos</td>
                <td>
                  <button
                    type="button"
                    className="btn btn-sm btn-outline-danger"
                    onClick={() => eliminarHorario(h.id_horario)}
                  >
                    Eliminar
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {mostrarModal && (
        <div className="modal-backdrop">
          <div className="modal-card">
            <div className="modal-header">
              <h3>Configurar Turno de Atención</h3>
              <button type="button" className="close-btn" onClick={() => setMostrarModal(false)}>✕</button>
            </div>
            <form onSubmit={handleSubmit} className="form-stack">
              <div className="form-group">
                <label>Médico Especialista</label>
                <select
                  className="input-field"
                  value={idMedicoForm}
                  onChange={(e) => setIdMedicoForm(e.target.value)}
                  required
                >
                  <option value="">Seleccione médico...</option>
                  {medicos.map((m) => (
                    <option key={m.id_medico} value={m.id_medico}>
                      {m.nombre_completo} ({m.registro_profesional})
                    </option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label>Día de Atención</label>
                <select
                  className="input-field"
                  value={diaSemana}
                  onChange={(e) => setDiaSemana(e.target.value)}
                  required
                >
                  {dias.map((d, idx) => (
                    <option key={idx + 1} value={idx + 1}>
                      {d}
                    </option>
                  ))}
                </select>
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Hora de Inicio</label>
                  <input
                    type="time"
                    className="input-field"
                    value={horaInicio}
                    onChange={(e) => setHoraInicio(e.target.value)}
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Hora de Fin</label>
                  <input
                    type="time"
                    className="input-field"
                    value={horaFin}
                    onChange={(e) => setHoraFin(e.target.value)}
                    required
                  />
                </div>
              </div>

              <div className="form-group">
                <label>Duración Estimada por Cita (minutos)</label>
                <input
                  type="number"
                  className="input-field"
                  min="5"
                  max="120"
                  step="5"
                  value={duracionMinutos}
                  onChange={(e) => setDuracionMinutos(e.target.value)}
                  required
                />
              </div>

              <div className="modal-actions">
                <button type="button" className="btn btn-outline" onClick={() => setMostrarModal(false)}>
                  Cancelar
                </button>
                <button type="submit" className="btn btn-primary" disabled={loading}>
                  {loading ? 'Validando...' : 'Guardar Horario'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

