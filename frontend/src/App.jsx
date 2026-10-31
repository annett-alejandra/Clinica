import React, { useState, useEffect } from 'react';
import { UI_Autenticacion } from './views/Paquetes/P1_SeguridadAdministracion/CU01_IniciarSesion/UI_Autenticacion';
import { UI_MenuPrincipal } from './views/Paquetes/P1_SeguridadAdministracion/CU02_CerrarSesion/UI_MenuPrincipal';
import { UI_Usuario } from './views/Paquetes/P1_SeguridadAdministracion/CU03_GestionarUsuariosRoles/UI_Usuario';
import { UI_Especialidad } from './views/Paquetes/P1_SeguridadAdministracion/CU04_GestionarEspecialidadesMedicas/UI_Especialidad';
import { UI_Medico } from './views/Paquetes/P2_PersonalMedico/CU05_GestionarMedicos/UI_Medico';
import { UI_HorarioAtencion } from './views/Paquetes/P2_PersonalMedico/CU06_GestionarHorariosAtencion/UI_HorarioAtencion';
import { UI_Paciente } from './views/Paquetes/P3_GestionPacientes/CU07_GestionarPacientes/UI_Paciente';
import { UI_RegistroPaciente } from './views/Paquetes/P3_GestionPacientes/CU09_RegistrarseComoPaciente/UI_RegistroPaciente';
import { UI_Disponibilidad } from './views/Paquetes/P4_GestionCitas/CU10_ConsultarDisponibilidadMedica/UI_Disponibilidad';
import { UI_CitaMedica } from './views/Paquetes/P4_GestionCitas/CU11_ProgramarCitaMedica/UI_CitaMedica';
import './index.css';

export default function App() {
  const [usuario, setUsuario] = useState(() => {
    const sesion = localStorage.getItem('clinica_usuario');
    return sesion ? JSON.parse(sesion) : {
      id_usuario: 1,
      nombre_usuario: 'admin',
      correo: 'admin@clinica.com',
      rol: 'Administrador',
      perfil: 'Dr. Administrador General',
    };
  });

  const [vistaPublica, setVistaPublica] = useState('login'); // 'login' | 'registro'
  const [moduloActivo, setModuloActivo] = useState('citas');
  const [turnoSeleccionado, setTurnoSeleccionado] = useState(null);

  useEffect(() => {
    if (usuario) {
      localStorage.setItem('clinica_usuario', JSON.stringify(usuario));
    } else {
      localStorage.removeItem('clinica_usuario');
    }
  }, [usuario]);

  const handleLoginSuccess = (user) => {
    setUsuario(user);
    setModuloActivo('citas');
  };

  const handleCerrarSesion = () => {
    setUsuario(null);
    setVistaPublica('login');
  };

  // Simular cambio rápido de rol para evaluación
  const cambiarRolDemo = (nuevoRol) => {
    const rolesPerfiles = {
      Administrador: { id: 1, u: 'admin', perfil: 'Administrador de Sistemas' },
      Recepcionista: { id: 2, u: 'recepcion', perfil: 'Recepcionista Central' },
      Médico: { id: 3, u: 'dr_valenzuela', perfil: 'Dr. Roberto Valenzuela M.', id_medico: 1 },
      Paciente: { id: 4, u: 'paciente_carlos', perfil: 'Carlos Eduardo Mendoza P.', id_paciente: 1 },
    };
    const ref = rolesPerfiles[nuevoRol] || rolesPerfiles.Administrador;
    const nuevoUser = {
      id_usuario: ref.id,
      nombre_usuario: ref.u,
      correo: `${ref.u}@clinica.com`,
      rol: nuevoRol,
      perfil: ref.perfil,
      id_medico: ref.id_medico || null,
      id_paciente: ref.id_paciente || null,
    };
    setUsuario(nuevoUser);
  };

  // Callback cuando desde Disponibilidad se selecciona un turno libre
  const handleSeleccionarTurno = (turno) => {
    setTurnoSeleccionado(turno);
    setModuloActivo('citas');
  };

  // Si no está autenticado, mostrar pantalla de Login o Registro
  if (!usuario) {
    return (
      <main className="auth-wrapper">
        <div className="auth-brand-ambient">
          <div className="ambient-sphere sphere-1"></div>
          <div className="ambient-sphere sphere-2"></div>
        </div>
        {vistaPublica === 'login' ? (
          <UI_Autenticacion
            onLoginSuccess={handleLoginSuccess}
            onIrARegistro={() => setVistaPublica('registro')}
          />
        ) : (
          <UI_RegistroPaciente
            onRegistroExitoso={handleLoginSuccess}
            onVolverALogin={() => setVistaPublica('login')}
          />
        )}
      </main>
    );
  }

  return (
    <div className="app-shell">
      {/* Barra de navegación superior con CU02 Cierre de sesión */}
      <UI_MenuPrincipal
        usuario={usuario}
        moduloActivo={moduloActivo}
        setModuloActivo={setModuloActivo}
        onCerrarSesion={handleCerrarSesion}
      />

      {/* Barra de telemetría y selector rápido de roles para pruebas */}
      <div className="demo-role-bar">
        <div className="demo-bar-inner">
          <span className="demo-label">Simular Rol Activo:</span>
          {['Administrador', 'Recepcionista', 'Médico', 'Paciente'].map((r) => (
            <button
              key={r}
              type="button"
              className={`btn-role-chip ${usuario.rol === r ? 'active' : ''}`}
              onClick={() => cambiarRolDemo(r)}
            >
              {r}
            </button>
          ))}
          <span className="demo-context">Contexto: Ciclo 1 (CU01–CU11) · Arquitectura BCE</span>
        </div>
      </div>

      {/* Contenido dinámico del caso de uso seleccionado */}
      <main className="app-content">
        {moduloActivo === 'citas' && (
          <UI_CitaMedica
            usuario={usuario}
            turnoSeleccionado={turnoSeleccionado}
            onLimpiarTurno={() => setTurnoSeleccionado(null)}
          />
        )}
        {moduloActivo === 'disponibilidad' && (
          <UI_Disponibilidad onSeleccionarTurno={handleSeleccionarTurno} />
        )}
        {moduloActivo === 'pacientes' && <UI_Paciente />}
        {moduloActivo === 'medicos' && <UI_Medico />}
        {moduloActivo === 'horarios' && <UI_HorarioAtencion />}
        {moduloActivo === 'especialidades' && <UI_Especialidad />}
        {moduloActivo === 'usuarios' && <UI_Usuario />}
      </main>

      {/* Pie institucional */}
      <footer className="app-footer">
        <p>Clínica Vida Salud © 2026 · Hospital de Excelencia Quirúrgica · Estándar Telcom BCE</p>
      </footer>
    </div>
  );
}
