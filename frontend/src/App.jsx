import React, { useState, useEffect } from 'react';
import { UI_Autenticacion } from './views/Paquetes/P1_SeguridadAdministracion/CU01_IniciarSesion/UI_Autenticacion';
import { UI_MenuPrincipal } from './views/Paquetes/P1_SeguridadAdministracion/CU02_CerrarSesion/UI_MenuPrincipal';
import { UI_Usuario } from './views/Paquetes/P1_SeguridadAdministracion/CU03_GestionarUsuariosRoles/UI_Usuario';
import { UI_Especialidad } from './views/Paquetes/P1_SeguridadAdministracion/CU04_GestionarEspecialidadesMedicas/UI_Especialidad';
import { UI_Medico } from './views/Paquetes/P2_PersonalMedico/CU05_GestionarMedicos/UI_Medico';
import { UI_HorarioAtencion } from './views/Paquetes/P2_PersonalMedico/CU06_GestionarHorariosAtencion/UI_HorarioAtencion';
import { UI_Paciente } from './views/Paquetes/P3_GestionPacientes/CU07_GestionarPacientes/UI_Paciente';
import { UI_PerfilPaciente } from './views/Paquetes/P3_GestionPacientes/CU08_GestionarPerfilPaciente/UI_PerfilPaciente';
import { UI_RegistroPaciente } from './views/Paquetes/P3_GestionPacientes/CU09_RegistrarCuentaPaciente/UI_RegistroPaciente';
import { UI_Disponibilidad } from './views/Paquetes/P4_GestionCitas/CU10_ConsultarDisponibilidadMedica/UI_Disponibilidad';
import { UI_CitaMedica } from './views/Paquetes/P4_GestionCitas/CU11_ProgramarCitaMedica/UI_CitaMedica';
import { UI_ReprogramarCita } from './views/Paquetes/P4_GestionCitas/CU12_ReprogramarCitaMedica/UI_ReprogramarCita';
import { UI_CancelarCita } from './views/Paquetes/P4_GestionCitas/CU13_CancelarCitaMedica/UI_CancelarCita';
import { UI_ControlAsistencia } from './views/Paquetes/P5_ControlAtencion/CU14_RegistrarConfirmacionAsistenciaInasistencia/UI_ControlAsistencia';
import { UI_CitaAtendida } from './views/Paquetes/P5_ControlAtencion/CU15_RegistrarCitaAtendida/UI_CitaAtendida';
import { UI_AgendaMedica } from './views/Paquetes/P6_ConsultasHistoriales/CU16_ConsultarAgendaMedica/UI_AgendaMedica';
import { UI_HistorialCitasPaciente } from './views/Paquetes/P6_ConsultasHistoriales/CU17_ConsultarHistorialCitasPaciente/UI_HistorialCitasPaciente';
import { UI_MisCitas } from './views/Paquetes/P6_ConsultasHistoriales/CU18_ConsultarMisCitas/UI_MisCitas';
import { UI_ReportesAdmin } from './views/Paquetes/P7_ReportesEstadisticas/CU19_GenerarReportesAdministrativos/UI_ReportesAdmin';
import { MedicoModel } from './models/Paquetes/P2_PersonalMedico/MedicoModel';
import { EspecialidadModel } from './models/Paquetes/P1_SeguridadAdministracion/EspecialidadModel';
import { CitaMedicaModel } from './models/Paquetes/P4_GestionCitas/CitaMedicaModel';
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
  const [moduloActivo, setModuloActivo] = useState('agenda');
  const [turnoSeleccionado, setTurnoSeleccionado] = useState(null);

  // Estados transversales compartidos para soporte entre módulos
  const [medicos, setMedicos] = useState([]);
  const [especialidades, setEspecialidades] = useState([]);
  const [citasOperativas, setCitasOperativas] = useState([]);
  const [modalReprogramar, setModalReprogramar] = useState(null);
  const [modalCancelar, setModalCancelar] = useState(null);

  const cargarCatalogos = async () => {
    try {
      const [meds, esps, cits] = await Promise.all([
        MedicoModel.obtenerTodos().catch(() => []),
        EspecialidadModel.obtenerTodas().catch(() => []),
        CitaMedicaModel.obtenerTodas().catch(() => []),
      ]);
      setMedicos(meds);
      setEspecialidades(esps);
      setCitasOperativas(cits);
    } catch {
      // Ignorar si el backend aún se está montando
    }
  };

  useEffect(() => {
    if (usuario) {
      localStorage.setItem('clinica_usuario', JSON.stringify(usuario));
      cargarCatalogos();
    } else {
      localStorage.removeItem('clinica_usuario');
    }
  }, [usuario]);

  const handleLoginSuccess = (user) => {
    setUsuario(user);
    if (user.rol === 'Paciente') {
      setModuloActivo('mis-citas');
    } else if (user.rol === 'Médico') {
      setModuloActivo('atencion-medica');
    } else {
      setModuloActivo('agenda');
    }
  };

  const handleCerrarSesion = () => {
    setUsuario(null);
    setVistaPublica('login');
  };

  // Simular cambio rápido de rol para evaluación de todos los casos de uso
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

    if (nuevoRol === 'Paciente') setModuloActivo('mis-citas');
    else if (nuevoRol === 'Médico') setModuloActivo('atencion-medica');
    else setModuloActivo('agenda');
  };

  const handleSeleccionarTurno = (turno) => {
    setTurnoSeleccionado(turno);
    setModuloActivo('citas');
  };

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
      {/* CU02: Barra de navegación superior */}
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
          <span className="demo-context">Contexto: Ciclos 1 y 2 (CU01–CU19) · Arquitectura BCE Completa</span>
        </div>
      </div>

      {/* Contenido dinámico del caso de uso seleccionado */}
      <main className="app-content">
        {/* Modales flotantes de reprogramación y cancelación */}
        {modalReprogramar && (
          <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.6)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1050 }}>
            <UI_ReprogramarCita
              cita={modalReprogramar}
              onCancel={() => setModalReprogramar(null)}
              onUpdated={() => {
                setModalReprogramar(null);
                cargarCatalogos();
              }}
            />
          </div>
        )}

        {modalCancelar && (
          <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.6)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1050 }}>
            <UI_CancelarCita
              cita={modalCancelar}
              onCancel={() => setModalCancelar(null)}
              onUpdated={() => {
                setModalCancelar(null);
                cargarCatalogos();
              }}
            />
          </div>
        )}

        {/* CU11: Programar Cita Médica */}
        {moduloActivo === 'citas' && (
          <UI_CitaMedica
            usuario={usuario}
            turnoSeleccionado={turnoSeleccionado}
            onLimpiarTurno={() => setTurnoSeleccionado(null)}
          />
        )}

        {/* CU16: Agenda Médica General */}
        {moduloActivo === 'agenda' && (
          <UI_AgendaMedica
            medicos={medicos}
            especialidades={especialidades}
            onReprogramar={(cita) => setModalReprogramar(cita)}
            onCancelar={(cita) => setModalCancelar(cita)}
          />
        )}

        {/* CU14: Control Operativo de Citas */}
        {moduloActivo === 'control-atencion' && (
          <UI_ControlAsistencia
            citas={citasOperativas}
            onActualizar={cargarCatalogos}
          />
        )}

        {/* CU15: Atención Médica (Médico) */}
        {moduloActivo === 'atencion-medica' && (
          <UI_CitaAtendida
            citas={citasOperativas.filter((c) => !usuario.id_medico || Number(c.id_medico) === Number(usuario.id_medico))}
            onActualizar={cargarCatalogos}
          />
        )}

        {/* CU10: Consultar Disponibilidad de Horarios */}
        {moduloActivo === 'disponibilidad' && (
          <UI_Disponibilidad onSeleccionarTurno={handleSeleccionarTurno} />
        )}

        {/* CU17: Historial Clínico de Pacientes */}
        {moduloActivo === 'historial-paciente' && <UI_HistorialCitasPaciente />}

        {/* CU18: Mis Citas (Portal Paciente) */}
        {moduloActivo === 'mis-citas' && <UI_MisCitas idPaciente={usuario.id_paciente} />}

        {/* CU08: Mi Perfil de Paciente */}
        {moduloActivo === 'perfil-paciente' && <UI_PerfilPaciente idPaciente={usuario.id_paciente} />}

        {/* CU07: Directorio de Pacientes */}
        {moduloActivo === 'pacientes' && <UI_Paciente />}

        {/* CU05: Gestión de Personal Médico */}
        {moduloActivo === 'medicos' && <UI_Medico />}

        {/* CU06: Horarios de Atención */}
        {moduloActivo === 'horarios' && <UI_HorarioAtencion />}

        {/* CU19: Reportes Administrativos */}
        {moduloActivo === 'reportes' && (
          <UI_ReportesAdmin medicos={medicos} especialidades={especialidades} />
        )}

        {/* CU04: Especialidades Médicas */}
        {moduloActivo === 'especialidades' && <UI_Especialidad />}

        {/* CU03: Gestión de Usuarios y Roles */}
        {moduloActivo === 'usuarios' && <UI_Usuario />}
      </main>

      <footer className="app-footer">
        <p>Clínica Vida Salud © 2026 · Hospital de Excelencia Quirúrgica · Estándar Telcom BCE</p>
      </footer>
    </div>
  );
}
