import React from 'react';

export function UI_MenuPrincipal({ usuario, moduloActivo, setModuloActivo, onCerrarSesion }) {
  const rol = usuario?.rol || 'Administrador';

  // Configuración completa de módulos según rol para Ciclo 1 y Ciclo 2
  const modulos = [
    // Operaciones Generales y Catálogos
    { id: 'citas', label: 'Programar Cita', roles: ['Administrador', 'Recepcionista', 'Paciente'], cu: 'CU11' },
    { id: 'agenda', label: 'Agenda Médica', roles: ['Administrador', 'Recepcionista', 'Médico'], cu: 'CU16' },
    { id: 'control-atencion', label: 'Control Operativo', roles: ['Administrador', 'Recepcionista'], cu: 'CU14' },
    { id: 'atencion-medica', label: 'Mis Consultas', roles: ['Médico'], cu: 'CU15' },
    { id: 'disponibilidad', label: 'Consultar Oferta', roles: ['Administrador', 'Recepcionista', 'Médico', 'Paciente'], cu: 'CU10' },
    { id: 'historial-paciente', label: 'Expediente Paciente', roles: ['Administrador', 'Recepcionista'], cu: 'CU17' },
    { id: 'mis-citas', label: 'Mis Citas', roles: ['Paciente'], cu: 'CU18' },
    { id: 'perfil-paciente', label: 'Mi Perfil', roles: ['Paciente'], cu: 'CU08' },
    { id: 'pacientes', label: 'Directorio Pacientes', roles: ['Administrador', 'Recepcionista'], cu: 'CU07' },
    { id: 'medicos', label: 'Cuerpo Médico', roles: ['Administrador', 'Recepcionista'], cu: 'CU05' },
    { id: 'horarios', label: 'Horarios de Atención', roles: ['Administrador', 'Recepcionista', 'Médico'], cu: 'CU06' },
    { id: 'reportes', label: 'Reportes y Métricas', roles: ['Administrador'], cu: 'CU19' },
    { id: 'especialidades', label: 'Especialidades', roles: ['Administrador'], cu: 'CU04' },
    { id: 'usuarios', label: 'Usuarios y Seguridad', roles: ['Administrador'], cu: 'CU03' },
  ];

  const modulosVisibles = modulos.filter((m) => m.roles.includes(rol));

  return (
    <header className="main-navbar">
      <div className="navbar-container">
        <div className="navbar-brand">
          <div className="brand-dot"></div>
          <div>
            <span className="brand-title">CLÍNICA VIDA SALUD</span>
            <span className="brand-sub">SISTEMA INTEGRAL · CICLO 1 & 2</span>
          </div>
        </div>

        <nav className="navbar-nav">
          {modulosVisibles.map((m) => (
            <button
              key={m.id}
              type="button"
              className={`nav-tab ${moduloActivo === m.id ? 'active' : ''}`}
              onClick={() => setModuloActivo(m.id)}
            >
              <span className="nav-cu-tag">{m.cu}</span>
              {m.label}
            </button>
          ))}
        </nav>

        <div className="navbar-user">
          <div className="user-info">
            <span className="user-name">{usuario?.perfil || usuario?.nombre_usuario || 'Operador'}</span>
            <span className="user-role-badge">{rol}</span>
          </div>
          <button
            type="button"
            className="btn btn-outline-danger btn-sm"
            onClick={onCerrarSesion}
            title="Cerrar sesión activa (CU02)"
          >
            Salir ⏻
          </button>
        </div>
      </div>
    </header>
  );
}
