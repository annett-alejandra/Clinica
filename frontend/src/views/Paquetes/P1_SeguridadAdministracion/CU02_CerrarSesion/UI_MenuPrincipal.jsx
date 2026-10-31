import React from 'react';

export function UI_MenuPrincipal({ usuario, moduloActivo, setModuloActivo, onCerrarSesion }) {
  const rol = usuario?.rol || 'Administrador';

  // Configuración de módulos según rol
  const modulos = [
    { id: 'citas', label: 'Citas Médicas', roles: ['Administrador', 'Recepcionista', 'Médico', 'Paciente'], cu: 'CU11' },
    { id: 'disponibilidad', label: 'Consultar Oferta', roles: ['Administrador', 'Recepcionista', 'Médico', 'Paciente'], cu: 'CU10' },
    { id: 'pacientes', label: 'Directorio Pacientes', roles: ['Administrador', 'Recepcionista'], cu: 'CU07' },
    { id: 'medicos', label: 'Cuerpo Médico', roles: ['Administrador', 'Recepcionista'], cu: 'CU05' },
    { id: 'horarios', label: 'Horarios de Atención', roles: ['Administrador', 'Recepcionista', 'Médico'], cu: 'CU06' },
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
            <span className="brand-sub">SISTEMA INTEGRAL · CICLO 1</span>
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

