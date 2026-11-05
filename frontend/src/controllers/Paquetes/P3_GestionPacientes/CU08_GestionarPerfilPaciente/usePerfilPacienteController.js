import { useState, useEffect, useCallback } from "react";
import { PerfilPacienteModel } from "../../../../models/Paquetes/P3_GestionPacientes/PerfilPacienteModel";

export function usePerfilPacienteController(idPaciente = null) {
  const [perfil, setPerfil] = useState(null);
  const [loading, setLoading] = useState(false);
  const [guardando, setGuardando] = useState(false);
  const [error, setError] = useState(null);
  const [mensajeExito, setMensajeExito] = useState(null);

  const cargarPerfil = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await PerfilPacienteModel.obtenerPerfil(idPaciente);
      setPerfil(data);
    } catch (err) {
      setError(err.message || "Error al cargar la información del perfil.");
    } finally {
      setLoading(false);
    }
  }, [idPaciente]);

  useEffect(() => {
    cargarPerfil();
  }, [cargarPerfil]);

  const guardarCambios = async (datosFormulario) => {
    setGuardando(true);
    setError(null);
    setMensajeExito(null);
    try {
      const pacienteActualizado = await PerfilPacienteModel.actualizarPerfil({
        ...datosFormulario,
        id_paciente: perfil?.paciente?.id_paciente || idPaciente,
      });
      setPerfil((prev) => ({
        ...prev,
        paciente: pacienteActualizado,
      }));
      setMensajeExito("¡Tu información de contacto ha sido actualizada exitosamente!");
      return true;
    } catch (err) {
      setError(err.message || "No se pudo actualizar el perfil.");
      return false;
    } finally {
      setGuardando(false);
    }
  };

  return {
    perfil,
    loading,
    guardando,
    error,
    mensajeExito,
    cargarPerfil,
    guardarCambios,
    limpiarAlertas: () => {
      setError(null);
      setMensajeExito(null);
    },
  };
}
