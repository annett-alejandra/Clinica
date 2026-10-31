import React, { useState } from 'react';
import { useEspecialidadController } from '../../../../controllers/Paquetes/P1_SeguridadAdministracion/CU04_GestionarEspecialidadesMedicas/useEspecialidadController';

export function UI_Especialidad() {
  const { especialidades, loading, error, success, crearEspecialidad, toggleEstado } = useEspecialidadController();

  const [mostrarModal, setMostrarModal] = useState(false);
  const [nombre, setNombre] = useState('');
  const [descripcion, setDescripcion] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!nombre) return;
    try {
      await crearEspecialidad({
        nombre_especialidad: nombre,
        descripcion: descripcion,
      });
      setNombre('');
      setDescripcion('');
      setMostrarModal(false);
    } catch {
      // Error manejado en hook
    }
  };

  return (
    <div className="module-container">
      <div className="module-header">
        <div>
          <span className="badge-overline">PAQUETE 01 · CU04</span>
          <h2>Catálogo de Especialidades Médicas</h2>
          <p className="subtitle">Gestión de áreas clínicas, descripciones terapéuticas y disponibilidad de servicio.</p>
        </div>
        <button type="button" className="btn btn-primary" onClick={() => setMostrarModal(true)}>
          + Nueva Especialidad
        </button>
      </div>

      {error && <div className="alert alert-error">{error}</div>}
      {success && <div className="alert alert-success">{success}</div>}

      <div className="grid-cards">
        {loading && especialidades.length === 0 ? (
          <p>Cargando catálogo...</p>
        ) : especialidades.map((esp) => (
          <div key={esp.id_especialidad} className="card-item">
            <div className="card-top">
              <span className={`status-pill ${esp.estado ? 'status-active' : 'status-inactive'}`}>
                {esp.estado ? 'Activa' : 'Inactiva'}
              </span>
              <span className="badge-count">{esp.medicos_count || 0} Especialistas</span>
            </div>
            <h3 className="card-title">{esp.nombre_especialidad}</h3>
            <p className="card-desc">{esp.descripcion || 'Sin descripción clínica registrada.'}</p>
            <div className="card-actions">
              <button
                type="button"
                className="btn btn-sm btn-outline"
                onClick={() => toggleEstado(esp.id_especialidad)}
              >
                {esp.estado ? 'Desactivar' : 'Reactivar'}
              </button>
            </div>
          </div>
        ))}
      </div>

      {mostrarModal && (
        <div className="modal-backdrop">
          <div className="modal-card">
            <div className="modal-header">
              <h3>Registrar Especialidad Médica</h3>
              <button type="button" className="close-btn" onClick={() => setMostrarModal(false)}>✕</button>
            </div>
            <form onSubmit={handleSubmit} className="form-stack">
              <div className="form-group">
                <label>Nombre de la Especialidad</label>
                <input
                  type="text"
                  className="input-field"
                  value={nombre}
                  onChange={(e) => setNombre(e.target.value)}
                  placeholder="ej. Neurología Clínica"
                  required
                />
              </div>
              <div className="form-group">
                <label>Descripción y Alcance Clínico</label>
                <textarea
                  className="input-field"
                  rows="3"
                  value={descripcion}
                  onChange={(e) => setDescripcion(e.target.value)}
                  placeholder="Detalles sobre procedimientos y consultas..."
                />
              </div>
              <div className="modal-actions">
                <button type="button" className="btn btn-outline" onClick={() => setMostrarModal(false)}>
                  Cancelar
                </button>
                <button type="submit" className="btn btn-primary" disabled={loading}>
                  {loading ? 'Guardando...' : 'Registrar'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

