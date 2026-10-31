import React, { useState } from 'react';
import { usePacienteController } from '../../../../controllers/Paquetes/P3_GestionPacientes/CU07_GestionarPacientes/usePacienteController';

export function UI_Paciente() {
  const { pacientes, busqueda, setBusqueda, loading, error, success, crearPaciente, toggleEstado } = usePacienteController();

  const [mostrarModal, setMostrarModal] = useState(false);
  const [ci, setCi] = useState('');
  const [nombre, setNombre] = useState('');
  const [fechaNacimiento, setFechaNacimiento] = useState('1990-01-01');
  const [genero, setGenero] = useState('Masculino');
  const [correo, setCorreo] = useState('');
  const [telefono, setTelefono] = useState('');
  const [direccion, setDireccion] = useState('');
  const [contactoEmergencia, setContactoEmergencia] = useState('');
  const [telefonoEmergencia, setTelefonoEmergencia] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!ci || !nombre || !fechaNacimiento || !genero) return;
    try {
      await crearPaciente({
        ci,
        nombre_completo: nombre,
        fecha_nacimiento: fechaNacimiento,
        genero,
        correo: correo || null,
        telefono: telefono || null,
        direccion_domicilio: direccion || null,
        contacto_emergencia: contactoEmergencia || null,
        telefono_emergencia: telefonoEmergencia || null,
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
          <span className="badge-overline">PAQUETE 03 · CU07</span>
          <h2>Directorio y Fichas de Pacientes</h2>
          <p className="subtitle">Gestión clínica de pacientes desde recepción con validación obligatoria y única de CI.</p>
        </div>
        <div className="header-actions">
          <input
            type="text"
            className="input-field search-input"
            placeholder="Buscar por CI, nombre o teléfono..."
            value={busqueda}
            onChange={(e) => setBusqueda(e.target.value)}
          />
          <button type="button" className="btn btn-primary" onClick={() => setMostrarModal(true)}>
            + Registrar Paciente
          </button>
        </div>
      </div>

      {error && <div className="alert alert-error">{error}</div>}
      {success && <div className="alert alert-success">{success}</div>}

      <div className="table-responsive">
        <table className="data-table">
          <thead>
            <tr>
              <th>Cédula (CI)</th>
              <th>Nombre Completo</th>
              <th>Nacimiento / Género</th>
              <th>Contacto</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {loading && pacientes.length === 0 ? (
              <tr><td colSpan="6" className="text-center">Consultando pacientes...</td></tr>
            ) : pacientes.map((p) => (
              <tr key={p.id_paciente}>
                <td><code className="code-badge">{p.ci}</code></td>
                <td className="font-semibold">{p.nombre_completo}</td>
                <td>
                  <small>{p.fecha_nacimiento} · {p.genero}</small>
                </td>
                <td>
                  <div className="contact-col">
                    <span>{p.correo || '—'}</span>
                    <small>{p.telefono || '—'}</small>
                  </div>
                </td>
                <td>
                  <span className={`status-pill ${p.estado ? 'status-active' : 'status-inactive'}`}>
                    {p.estado ? 'Activo' : 'Inactivo'}
                  </span>
                </td>
                <td>
                  <button
                    type="button"
                    className="btn btn-sm btn-outline"
                    onClick={() => toggleEstado(p.id_paciente)}
                  >
                    {p.estado ? 'Desactivar' : 'Activar'}
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
              <h3>Registrar Ficha de Paciente</h3>
              <button type="button" className="close-btn" onClick={() => setMostrarModal(false)}>✕</button>
            </div>
            <form onSubmit={handleSubmit} className="form-stack">
              <div className="form-row">
                <div className="form-group">
                  <label>Cédula de Identidad (CI) *</label>
                  <input
                    type="text"
                    className="input-field"
                    value={ci}
                    onChange={(e) => setCi(e.target.value)}
                    placeholder="ej. 6829104"
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Género</label>
                  <select
                    className="input-field"
                    value={genero}
                    onChange={(e) => setGenero(e.target.value)}
                    required
                  >
                    <option value="Masculino">Masculino</option>
                    <option value="Femenino">Femenino</option>
                    <option value="Otro">Otro</option>
                  </select>
                </div>
              </div>

              <div className="form-group">
                <label>Nombre Completo *</label>
                <input
                  type="text"
                  className="input-field"
                  value={nombre}
                  onChange={(e) => setNombre(e.target.value)}
                  placeholder="ej. Carlos Eduardo Mendoza Pérez"
                  required
                />
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Fecha de Nacimiento</label>
                  <input
                    type="date"
                    className="input-field"
                    value={fechaNacimiento}
                    onChange={(e) => setFechaNacimiento(e.target.value)}
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Teléfono Celular</label>
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
                <label>Correo Electrónico</label>
                <input
                  type="email"
                  className="input-field"
                  value={correo}
                  onChange={(e) => setCorreo(e.target.value)}
                  placeholder="paciente@ejemplo.com"
                />
              </div>

              <div className="form-group">
                <label>Dirección de Domicilio</label>
                <input
                  type="text"
                  className="input-field"
                  value={direccion}
                  onChange={(e) => setDireccion(e.target.value)}
                  placeholder="Calle, número, zona o condominio"
                />
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Contacto de Emergencia</label>
                  <input
                    type="text"
                    className="input-field"
                    value={contactoEmergencia}
                    onChange={(e) => setContactoEmergencia(e.target.value)}
                    placeholder="Nombre del familiar"
                  />
                </div>
                <div className="form-group">
                  <label>Teléfono de Emergencia</label>
                  <input
                    type="text"
                    className="input-field"
                    value={telefonoEmergencia}
                    onChange={(e) => setTelefonoEmergencia(e.target.value)}
                    placeholder="+591 ..."
                  />
                </div>
              </div>

              <div className="modal-actions">
                <button type="button" className="btn btn-outline" onClick={() => setMostrarModal(false)}>
                  Cancelar
                </button>
                <button type="submit" className="btn btn-primary" disabled={loading}>
                  {loading ? 'Validando CI...' : 'Crear Ficha'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

