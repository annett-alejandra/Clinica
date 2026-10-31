import React, { useState } from 'react';
import { useMedicoController } from '../../../../controllers/Paquetes/P2_PersonalMedico/CU05_GestionarMedicos/useMedicoController';

export function UI_Medico() {
  const { medicos, especialidades, loading, error, success, crearMedico, toggleEstado } = useMedicoController();

  const [mostrarModal, setMostrarModal] = useState(false);
  const [nombre, setNombre] = useState('');
  const [registro, setRegistro] = useState('');
  const [correo, setCorreo] = useState('');
  const [telefono, setTelefono] = useState('');
  const [especialidadesSeleccionadas, setEspecialidadesSeleccionadas] = useState([]);

  const handleCheckboxChange = (id) => {
    setEspecialidadesSeleccionadas((prev) =>
      prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
    );
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!nombre || !registro) return;
    try {
      await crearMedico({
        nombre_completo: nombre,
        registro_profesional: registro,
        correo: correo || null,
        telefono: telefono || null,
        especialidades: especialidadesSeleccionadas,
      });
      setNombre('');
      setRegistro('');
      setCorreo('');
      setTelefono('');
      setEspecialidadesSeleccionadas([]);
      setMostrarModal(false);
    } catch {
      // Error manejado en hook
    }
  };

  return (
    <div className="module-container">
      <div className="module-header">
        <div>
          <span className="badge-overline">PAQUETE 02 · CU05</span>
          <h2>Gestión del Cuerpo Médico</h2>
          <p className="subtitle">Administración de perfiles profesionales, matrículas y asignación de especialidades.</p>
        </div>
        <button type="button" className="btn btn-primary" onClick={() => setMostrarModal(true)}>
          + Nuevo Médico
        </button>
      </div>

      {error && <div className="alert alert-error">{error}</div>}
      {success && <div className="alert alert-success">{success}</div>}

      <div className="table-responsive">
        <table className="data-table">
          <thead>
            <tr>
              <th>Médico Especialista</th>
              <th>Matrícula Profesional</th>
              <th>Especialidades Asignadas</th>
              <th>Contacto</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {loading && medicos.length === 0 ? (
              <tr><td colSpan="6" className="text-center">Cargando profesionales...</td></tr>
            ) : medicos.map((m) => (
              <tr key={m.id_medico}>
                <td className="font-semibold">{m.nombre_completo}</td>
                <td><code className="code-badge">{m.registro_profesional}</code></td>
                <td>
                  <div className="tag-cloud">
                    {m.especialidades && m.especialidades.length > 0 ? (
                      m.especialidades.map((e) => (
                        <span key={e.id_especialidad} className="tag-specialty">
                          {e.nombre_especialidad}
                        </span>
                      ))
                    ) : (
                      <span className="text-muted">Sin especialidad</span>
                    )}
                  </div>
                </td>
                <td>
                  <div className="contact-col">
                    <span>{m.correo || '—'}</span>
                    <small>{m.telefono || '—'}</small>
                  </div>
                </td>
                <td>
                  <span className={`status-pill ${m.estado ? 'status-active' : 'status-inactive'}`}>
                    {m.estado ? 'Activo' : 'Inactivo'}
                  </span>
                </td>
                <td>
                  <button
                    type="button"
                    className="btn btn-sm btn-outline"
                    onClick={() => toggleEstado(m.id_medico)}
                  >
                    {m.estado ? 'Desactivar' : 'Activar'}
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
              <h3>Registrar Nuevo Médico</h3>
              <button type="button" className="close-btn" onClick={() => setMostrarModal(false)}>✕</button>
            </div>
            <form onSubmit={handleSubmit} className="form-stack">
              <div className="form-group">
                <label>Nombre Completo</label>
                <input
                  type="text"
                  className="input-field"
                  value={nombre}
                  onChange={(e) => setNombre(e.target.value)}
                  placeholder="ej. Dr. Carlos Fernández Ramos"
                  required
                />
              </div>
              <div className="form-group">
                <label>Registro Profesional / Matrícula</label>
                <input
                  type="text"
                  className="input-field"
                  value={registro}
                  onChange={(e) => setRegistro(e.target.value)}
                  placeholder="ej. MP-98124"
                  required
                />
              </div>
              <div className="form-row">
                <div className="form-group">
                  <label>Correo Electrónico</label>
                  <input
                    type="email"
                    className="input-field"
                    value={correo}
                    onChange={(e) => setCorreo(e.target.value)}
                    placeholder="doctor@clinica.com"
                  />
                </div>
                <div className="form-group">
                  <label>Teléfono de Contacto</label>
                  <input
                    type="text"
                    className="input-field"
                    value={telefono}
                    onChange={(e) => setTelefono(e.target.value)}
                    placeholder="+591 7..."
                  />
                </div>
              </div>
              <div className="form-group">
                <label>Asignación de Especialidades Médicas (Pivote)</label>
                <div className="checkbox-grid">
                  {especialidades.map((esp) => (
                    <label key={esp.id_especialidad} className="checkbox-item">
                      <input
                        type="checkbox"
                        checked={especialidadesSeleccionadas.includes(esp.id_especialidad)}
                        onChange={() => handleCheckboxChange(esp.id_especialidad)}
                      />
                      <span>{esp.nombre_especialidad}</span>
                    </label>
                  ))}
                </div>
              </div>
              <div className="modal-actions">
                <button type="button" className="btn btn-outline" onClick={() => setMostrarModal(false)}>
                  Cancelar
                </button>
                <button type="submit" className="btn btn-primary" disabled={loading}>
                  {loading ? 'Guardando...' : 'Registrar Médico'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

