import React, { useState, useEffect } from "react";
import { usePerfilPacienteController } from "../../../../controllers/Paquetes/P3_GestionPacientes/CU08_GestionarPerfilPaciente/usePerfilPacienteController";

export function UI_PerfilPaciente({ idPaciente = null }) {
  const {
    perfil,
    loading,
    guardando,
    error,
    mensajeExito,
    guardarCambios,
    limpiarAlertas,
  } = usePerfilPacienteController(idPaciente);

  const [form, setForm] = useState({
    telefono: "",
    correo: "",
    direccion_domicilio: "",
    contacto_emergencia: "",
    telefono_emergencia: "",
  });

  useEffect(() => {
    if (perfil?.paciente) {
      setForm({
        telefono: perfil.paciente.telefono || "",
        correo: perfil.paciente.correo || "",
        direccion_domicilio: perfil.paciente.direccion_domicilio || "",
        contacto_emergencia: perfil.paciente.contacto_emergencia || "",
        telefono_emergencia: perfil.paciente.telefono_emergencia || "",
      });
    }
  }, [perfil]);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
    limpiarAlertas();
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    await guardarCambios(form);
  };

  if (loading) {
    return <div className="card text-center text-muted">Cargando perfil del paciente...</div>;
  }

  const pac = perfil?.paciente;

  return (
    <div className="card" style={{ maxWidth: "800px", margin: "0 auto" }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "1.5rem" }}>
        <div>
          <h2>Mi Perfil de Paciente</h2>
          <p className="text-muted" style={{ margin: 0 }}>
            Mantén actualizada tu información de contacto y emergencia.
          </p>
        </div>
        <span className="badge badge-success">Paciente Activo</span>
      </div>

      {error && <div className="alert alert-danger">{error}</div>}
      {mensajeExito && <div className="alert alert-success">{mensajeExito}</div>}

      {/* Datos Inmutables / Identidad */}
      {pac && (
        <div style={{ background: "#F1F5F9", padding: "1rem", borderRadius: "8px", marginBottom: "1.5rem" }}>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(200px, 1fr))", gap: "1rem" }}>
            <div>
              <strong style={{ fontSize: "0.85rem", color: "#64748B" }}>Nombre Completo:</strong>
              <div style={{ fontWeight: 600 }}>{pac.nombre_completo}</div>
            </div>
            <div>
              <strong style={{ fontSize: "0.85rem", color: "#64748B" }}>Cédula de Identidad (CI):</strong>
              <div style={{ fontWeight: 600 }}>{pac.ci}</div>
            </div>
            <div>
              <strong style={{ fontSize: "0.85rem", color: "#64748B" }}>Fecha de Nacimiento:</strong>
              <div>{pac.fecha_nacimiento}</div>
            </div>
            <div>
              <strong style={{ fontSize: "0.85rem", color: "#64748B" }}>Género:</strong>
              <div>{pac.genero === "M" ? "Masculino" : pac.genero === "F" ? "Femenino" : "Otro"}</div>
            </div>
          </div>
        </div>
      )}

      {/* Formulario de Datos Editables */}
      <form onSubmit={handleSubmit}>
        <h3 style={{ fontSize: "1.1rem", borderBottom: "1px solid #E2E8F0", paddingBottom: "0.5rem" }}>
          Datos de Contacto Directo
        </h3>

        <div className="form-group">
          <label>Teléfono / WhatsApp *</label>
          <input
            type="text"
            name="telefono"
            className="form-control"
            value={form.telefono}
            onChange={handleChange}
            required
            placeholder="Ej: +591 76543210"
          />
        </div>

        <div className="form-group">
          <label>Correo Electrónico *</label>
          <input
            type="email"
            name="correo"
            className="form-control"
            value={form.correo}
            onChange={handleChange}
            required
            placeholder="correo@ejemplo.com"
          />
        </div>

        <div className="form-group">
          <label>Dirección de Domicilio</label>
          <input
            type="text"
            name="direccion_domicilio"
            className="form-control"
            value={form.direccion_domicilio}
            onChange={handleChange}
            placeholder="Av. Principal #123, Zona Centro"
          />
        </div>

        <h3 style={{ fontSize: "1.1rem", borderBottom: "1px solid #E2E8F0", paddingBottom: "0.5rem", marginTop: "1.5rem" }}>
          Contacto de Emergencia
        </h3>

        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "1rem" }}>
          <div className="form-group">
            <label>Nombre de Referencia</label>
            <input
              type="text"
              name="contacto_emergencia"
              className="form-control"
              value={form.contacto_emergencia}
              onChange={handleChange}
              placeholder="Familiar o persona cercana"
            />
          </div>

          <div className="form-group">
            <label>Teléfono de Emergencia</label>
            <input
              type="text"
              name="telefono_emergencia"
              className="form-control"
              value={form.telefono_emergencia}
              onChange={handleChange}
              placeholder="Teléfono disponible 24/7"
            />
          </div>
        </div>

        <div style={{ marginTop: "1.5rem", display: "flex", justifyContent: "flex-end" }}>
          <button type="submit" className="btn btn-primary" disabled={guardando}>
            {guardando ? "Guardando cambios..." : "Guardar Información de Perfil"}
          </button>
        </div>
      </form>
    </div>
  );
}
