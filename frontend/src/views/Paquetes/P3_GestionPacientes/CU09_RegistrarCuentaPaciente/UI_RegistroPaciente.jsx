import React, { useState } from 'react';
import { useRegistroPacienteController } from '../../../../controllers/Paquetes/P3_GestionPacientes/CU09_RegistrarCuentaPaciente/useRegistroPacienteController';

export function UI_RegistroPaciente({ onRegistroExitoso, onVolverALogin }) {
  const { loading, error, success, registrar } = useRegistroPacienteController(onRegistroExitoso);

  const [ci, setCi] = useState('');
  const [nombre, setNombre] = useState('');
  const [fechaNacimiento, setFechaNacimiento] = useState('');
  const [genero, setGenero] = useState('Masculino');
  const [correo, setCorreo] = useState('');
  const [telefono, setTelefono] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [terminos, setTerminos] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!terminos) {
      alert('Debe aceptar las políticas de privacidad y términos del servicio clínico.');
      return;
    }
    await registrar({
      ci,
      nombre_completo: nombre,
      fecha_nacimiento: fechaNacimiento,
      genero,
      correo,
      telefono: telefono || null,
      password,
      password_confirmation: passwordConfirmation,
    });
  };

  return (
    <div className="auth-card register-card">
      <div className="auth-header">
        <span className="badge-overline">PORTAL DE PACIENTES · CU09</span>
        <h2>Auto-Registro de Paciente</h2>
        <p className="subtitle">Cree su cuenta web para consultar disponibilidad y reservar citas en línea.</p>
      </div>

      {error && <div className="alert alert-error">{error}</div>}
      {success && <div className="alert alert-success">{success}</div>}

      <form onSubmit={handleSubmit} className="form-stack">
        <div className="form-row">
          <div className="form-group">
            <label>Cédula de Identidad (CI) *</label>
            <input
              type="text"
              className="input-field"
              value={ci}
              onChange={(e) => setCi(e.target.value)}
              placeholder="Número de CI"
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
          <label>Nombre y Apellidos Completos *</label>
          <input
            type="text"
            className="input-field"
            value={nombre}
            onChange={(e) => setNombre(e.target.value)}
            placeholder="Como figura en su documento de identidad"
            required
          />
        </div>

        <div className="form-row">
          <div className="form-group">
            <label>Fecha de Nacimiento *</label>
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
          <label>Correo Electrónico (Será su usuario de acceso) *</label>
          <input
            type="email"
            className="input-field"
            value={correo}
            onChange={(e) => setCorreo(e.target.value)}
            placeholder="su_correo@ejemplo.com"
            required
          />
        </div>

        <div className="form-row">
          <div className="form-group">
            <label>Contraseña *</label>
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
            <label>Confirmar Contraseña *</label>
            <input
              type="password"
              className="input-field"
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
              placeholder="Repita la contraseña"
              required
            />
          </div>
        </div>

        <div className="form-group checkbox-simple">
          <label>
            <input
              type="checkbox"
              checked={terminos}
              onChange={(e) => setTerminos(e.target.checked)}
              required
            />{' '}
            Acepto los términos de consentimiento informado y tratamiento de datos clínicos de Clínica Vida Salud.
          </label>
        </div>

        <button type="submit" className="btn btn-primary" disabled={loading}>
          {loading ? 'Creando cuenta...' : 'Finalizar Registro'}
        </button>
      </form>

      <div className="auth-footer">
        <p>
          ¿Ya tiene cuenta de usuario?{' '}
          <button type="button" className="btn-link" onClick={onVolverALogin}>
            Iniciar sesión aquí ↗
          </button>
        </p>
      </div>
    </div>
  );
}


