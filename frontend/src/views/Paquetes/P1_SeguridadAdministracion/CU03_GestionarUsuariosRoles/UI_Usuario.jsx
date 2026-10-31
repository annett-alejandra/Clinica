import React, { useState } from 'react';
import { useUsuarioController } from '../../../../controllers/Paquetes/P1_SeguridadAdministracion/CU03_GestionarUsuariosRoles/useUsuarioController';

export function UI_Usuario() {
  const { usuarios, roles, loading, error, success, crearUsuario, toggleEstado } = useUsuarioController();

  const [mostrarModal, setMostrarModal] = useState(false);
  const [nombreUsuario, setNombreUsuario] = useState('');
  const [correo, setCorreo] = useState('');
  const [password, setPassword] = useState('');
  const [idRol, setIdRol] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!nombreUsuario || !correo || !password || !idRol) return;
    try {
      await crearUsuario({
        nombre_usuario: nombreUsuario,
        correo: correo,
        password: password,
        id_rol: Number(idRol),
      });
      setNombreUsuario('');
      setCorreo('');
      setPassword('');
      setIdRol('');
      setMostrarModal(false);
    } catch {
      // Error manejado en hook
    }
  };

  return (
    <div className="module-container">
      <div className="module-header">
        <div>
          <span className="badge-overline">PAQUETE 01 · CU03</span>
          <h2>Gestión de Usuarios y Roles</h2>
          <p className="subtitle">Administración de credenciales, roles institucionales y estados de acceso.</p>
        </div>
        <button type="button" className="btn btn-primary" onClick={() => setMostrarModal(true)}>
          + Nuevo Usuario
        </button>
      </div>

      {error && <div className="alert alert-error">{error}</div>}
      {success && <div className="alert alert-success">{success}</div>}

      <div className="table-responsive">
        <table className="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Usuario</th>
              <th>Correo Institucional</th>
              <th>Rol Asignado</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {loading && usuarios.length === 0 ? (
              <tr><td colSpan="6" className="text-center">Cargando usuarios...</td></tr>
            ) : usuarios.map((u) => (
              <tr key={u.id_usuario}>
                <td>#{u.id_usuario}</td>
                <td className="font-semibold">{u.nombre_usuario}</td>
                <td>{u.correo}</td>
                <td>
                  <span className="badge-role">{u.rol?.nombre_rol || 'Sin Rol'}</span>
                </td>
                <td>
                  <span className={`status-pill ${u.estado ? 'status-active' : 'status-inactive'}`}>
                    {u.estado ? 'Activo' : 'Inactivo'}
                  </span>
                </td>
                <td>
                  <button
                    type="button"
                    className="btn btn-sm btn-outline"
                    onClick={() => toggleEstado(u.id_usuario)}
                  >
                    {u.estado ? 'Desactivar' : 'Activar'}
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
              <h3>Registrar Nuevo Usuario</h3>
              <button type="button" className="close-btn" onClick={() => setMostrarModal(false)}>✕</button>
            </div>
            <form onSubmit={handleSubmit} className="form-stack">
              <div className="form-group">
                <label>Nombre de Usuario</label>
                <input
                  type="text"
                  className="input-field"
                  value={nombreUsuario}
                  onChange={(e) => setNombreUsuario(e.target.value)}
                  placeholder="ej. recep_central"
                  required
                />
              </div>
              <div className="form-group">
                <label>Correo Electrónico</label>
                <input
                  type="email"
                  className="input-field"
                  value={correo}
                  onChange={(e) => setCorreo(e.target.value)}
                  placeholder="usuario@clinica.com"
                  required
                />
              </div>
              <div className="form-group">
                <label>Contraseña Temporal</label>
                <input
                  type="password"
                  className="input-field"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Mínimo 6 caracteres"
                  required
                />
              </div>
              <div className="form-group">
                <label>Rol del Sistema</label>
                <select
                  className="input-field"
                  value={idRol}
                  onChange={(e) => setIdRol(e.target.value)}
                  required
                >
                  <option value="">Seleccione un rol...</option>
                  {roles.map((r) => (
                    <option key={r.id_rol} value={r.id_rol}>
                      {r.nombre_rol} — {r.descripcion}
                    </option>
                  ))}
                </select>
              </div>
              <div className="modal-actions">
                <button type="button" className="btn btn-outline" onClick={() => setMostrarModal(false)}>
                  Cancelar
                </button>
                <button type="submit" className="btn btn-primary" disabled={loading}>
                  {loading ? 'Guardando...' : 'Crear Usuario'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

