import React, { useState } from 'react';
import { useAuthController } from '../../../../controllers/Paquetes/P1_SeguridadAdministracion/CU01_IniciarSesion/useAuthController';

export function UI_Autenticacion({ onLoginSuccess, onIrARegistro }) {
  const [identificador, setIdentificador] = useState('admin');
  const [password, setPassword] = useState('clinica123');
  const { loading, error, login } = useAuthController(onLoginSuccess);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!identificador || !password) return;
    try {
      await login(identificador, password);
    } catch {
      // Error manejado en hook
    }
  };

  return (
    <div className="auth-card">
      <div className="auth-header">
        <span className="badge-overline">SISTEMA CLÍNICO · CONTROL DE ACCESO</span>
        <h2>Iniciar Sesión</h2>
        <p className="subtitle">Acceda con sus credenciales institucionales o cuenta de paciente</p>
      </div>

      {error && <div className="alert alert-error">{error}</div>}

      <form onSubmit={handleSubmit} className="form-stack">
        <div className="form-group">
          <label>Correo Electrónico o Usuario</label>
          <input
            type="text"
            className="input-field"
            value={identificador}
            onChange={(e) => setIdentificador(e.target.value)}
            placeholder="ej. admin@clinica.com o dr_valenzuela"
            required
          />
        </div>

        <div className="form-group">
          <label>Contraseña</label>
          <input
            type="password"
            className="input-field"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="••••••••"
            required
          />
        </div>

        <button type="submit" className="btn btn-primary" disabled={loading}>
          {loading ? 'Validando...' : 'Acceder al Sistema'}
        </button>
      </form>

      <div className="auth-footer">
        <p>
          ¿Es paciente nuevo y desea agendar citas online?{' '}
          <button type="button" className="btn-link" onClick={onIrARegistro}>
            Crear cuenta de paciente ↗
          </button>
        </p>
        <div className="demo-credentials">
          <small>Credenciales de prueba rápida: <strong>admin</strong> | <strong>recepcion</strong> | <strong>dr_valenzuela</strong> | <strong>paciente_carlos</strong> (clave: clinica123)</small>
        </div>
      </div>
    </div>
  );
}

