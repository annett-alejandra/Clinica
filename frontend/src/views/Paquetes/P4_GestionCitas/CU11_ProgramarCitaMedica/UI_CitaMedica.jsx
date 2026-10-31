import React, { useState, useEffect } from 'react';
import { useCitaMedicaController } from '../../../../controllers/Paquetes/P4_GestionCitas/CU11_ProgramarCitaMedica/useCitaMedicaController';

export function UI_CitaMedica({ usuario, turnoSeleccionado, onLimpiarTurno }) {
  const {
    citas,
    pacientes,
    medicos,
    especialidades,
    loading,
    error,
    reservaConfirmada,
    setReservaConfirmada,
    programarCita,
  } = useCitaMedicaController();

  const [idPaciente, setIdPaciente] = useState('');
  const [idMedico, setIdMedico] = useState('');
  const [idEspecialidad, setIdEspecialidad] = useState('');
  const [fechaCita, setFechaCita] = useState('');
  const [horaCita, setHoraCita] = useState('');
  const [duracion, setDuracion] = useState('30');
  const [motivo, setMotivo] = useState('');

  // Sincronizar si vino un turno seleccionado desde el módulo de disponibilidad (CU10)
  useEffect(() => {
    if (turnoSeleccionado) {
      if (turnoSeleccionado.medico?.id_medico) setIdMedico(turnoSeleccionado.medico.id_medico);
      if (turnoSeleccionado.especialidad?.id_especialidad) setIdEspecialidad(turnoSeleccionado.especialidad.id_especialidad);
      if (turnoSeleccionado.fecha) setFechaCita(turnoSeleccionado.fecha);
      if (turnoSeleccionado.slot?.hora_inicio) setHoraCita(turnoSeleccionado.slot.hora_inicio);
      if (turnoSeleccionado.slot?.duracion_minutos) setDuracion(String(turnoSeleccionado.slot.duracion_minutos));
    }
  }, [turnoSeleccionado]);

  // Si el usuario autenticado es Paciente, autoasignar su id_paciente
  useEffect(() => {
    if (usuario?.id_paciente) {
      setIdPaciente(usuario.id_paciente);
    } else if (pacientes.length > 0 && !idPaciente) {
      setIdPaciente(pacientes[0].id_paciente);
    }
  }, [usuario, pacientes, idPaciente]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!idPaciente || !idMedico || !idEspecialidad || !fechaCita || !horaCita) return;

    try {
      await programarCita({
        id_paciente: Number(idPaciente),
        id_medico: Number(idMedico),
        id_especialidad: Number(idEspecialidad),
        fecha_cita: fechaCita,
        hora_cita: horaCita,
        duracion_minutos: Number(duracion),
        motivo: motivo || null,
      });
      if (onLimpiarTurno) onLimpiarTurno();
    } catch {
      // Error manejado en hook
    }
  };

  return (
    <div className="module-container">
      <div className="module-header">
        <div>
          <span className="badge-overline">PAQUETE 04 · CU11</span>
          <h2>Programación y Reserva de Citas Médicas</h2>
          <p className="subtitle">Reserva de cupos clínicos con prevención atómica de solapamientos y código único de seguimiento.</p>
        </div>
      </div>

      {reservaConfirmada && (
        <div className="confirmation-banner">
          <div className="banner-badge">¡CITA CONFIRMADA EXITOSAMENTE!</div>
          <h3>Código Único de Reserva: <code className="uuid-highlight">{reservaConfirmada.codigo_unico}</code></h3>
          <p className="banner-details">
            Fecha: <strong>{reservaConfirmada.fecha_cita}</strong> a las <strong>{reservaConfirmada.hora_cita}</strong> | Estado: <span className="status-pill status-active">Programada</span>
          </p>
          <button type="button" className="btn btn-sm btn-outline" onClick={() => setReservaConfirmada(null)}>
            Cerrar Notificación ✕
          </button>
        </div>
      )}

      {error && <div className="alert alert-error">{error}</div>}

      <div className="booking-layout">
        <div className="booking-form-col">
          <div className="card-item form-card">
            <h3>Nueva Reserva de Cita</h3>
            <form onSubmit={handleSubmit} className="form-stack">
              <div className="form-group">
                <label>Paciente Solicitante *</label>
                {usuario?.rol === 'Paciente' ? (
                  <input
                    type="text"
                    className="input-field"
                    value={usuario.perfil || usuario.nombre_usuario}
                    disabled
                  />
                ) : (
                  <select
                    className="input-field"
                    value={idPaciente}
                    onChange={(e) => setIdPaciente(e.target.value)}
                    required
                  >
                    <option value="">Seleccione paciente...</option>
                    {pacientes.map((p) => (
                      <option key={p.id_paciente} value={p.id_paciente}>
                        {p.nombre_completo} (CI: {p.ci})
                      </option>
                    ))}
                  </select>
                )}
              </div>

              <div className="form-group">
                <label>Especialidad Requerida *</label>
                <select
                  className="input-field"
                  value={idEspecialidad}
                  onChange={(e) => setIdEspecialidad(e.target.value)}
                  required
                >
                  <option value="">Seleccione especialidad...</option>
                  {especialidades.map((esp) => (
                    <option key={esp.id_especialidad} value={esp.id_especialidad}>
                      {esp.nombre_especialidad}
                    </option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label>Médico Especialista *</label>
                <select
                  className="input-field"
                  value={idMedico}
                  onChange={(e) => setIdMedico(e.target.value)}
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

              <div className="form-row">
                <div className="form-group">
                  <label>Fecha de la Cita *</label>
                  <input
                    type="date"
                    className="input-field"
                    value={fechaCita}
                    min={new Date().toISOString().split('T')[0]}
                    onChange={(e) => setFechaCita(e.target.value)}
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Hora de Atención *</label>
                  <input
                    type="time"
                    className="input-field"
                    value={horaCita}
                    onChange={(e) => setHoraCita(e.target.value)}
                    required
                  />
                </div>
              </div>

              <div className="form-group">
                <label>Motivo de Consulta Médica (Opcional)</label>
                <textarea
                  className="input-field"
                  rows="2"
                  value={motivo}
                  onChange={(e) => setMotivo(e.target.value)}
                  placeholder="Síntomas, control preventivo o motivo..."
                />
              </div>

              <button type="submit" className="btn btn-primary btn-block" disabled={loading}>
                {loading ? 'Verificando exclusión y agendando...' : 'Confirmar y Programar Cita'}
              </button>
            </form>
          </div>
        </div>

        <div className="booking-list-col">
          <h3>Citas Programadas Recientes</h3>
          <div className="appointments-list">
            {citas.length === 0 ? (
              <p className="text-muted">No hay citas registradas en el sistema.</p>
            ) : (
              citas.map((c) => (
                <div key={c.id_cita} className="appointment-card">
                  <div className="apt-top">
                    <span className="badge-status-programada">{c.estado_cita?.nombre_estado || 'Programada'}</span>
                    <span className="apt-date">{c.fecha_cita} a las {c.hora_cita?.substring(0, 5)}</span>
                  </div>
                  <div className="apt-body">
                    <strong>{c.paciente?.nombre_completo || 'Paciente'}</strong>
                    <p className="text-muted">Con: {c.medico?.nombre_completo} ({c.especialidad?.nombre_especialidad})</p>
                    {c.motivo && <small className="apt-motivo">“{c.motivo}”</small>}
                  </div>
                  <div className="apt-footer">
                    <small>UUID: <code>{c.codigo_unico}</code></small>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

