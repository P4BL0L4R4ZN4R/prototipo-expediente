import { useEffect, useRef, useState } from "react";
import { format } from "date-fns";
import { useExpediente, useExpedienteHandles } from "@/hooks";
import {
  actualizarEstado,
  actualizarDireccion,
  actualizarExpediente,
  solicitarZip,
  estadoZip,
  descargarZipPorJob,
} from "@/api/excelApi";
import { exito, error as toastError } from "@/utils/alert";

// =========================================================
// HOOK
// =========================================================

export function useExpedienteUI(idExpediente: string | null, open: boolean) {
  const {
    expediente,
    loading,
    error,
    refrescar,
    guardarMeta,
    fechaPropuesta,
    setFechaPropuesta,
    fechaEntregable,
    setFechaEntregable,
  } = useExpediente(idExpediente, open);

  // ─── Estado local ───────────────────────────────────────
  const [estado, setEstado] = useState("");
  const [estadoOriginal, setEstadoOriginal] = useState("");
  const [direccion, setDireccion] = useState("");
  const [direccionOriginal, setDireccionOriginal] = useState("");
  const [tema, setTema] = useState("");
  const [area, setArea] = useState("");
  const [numObjetivos, setNumObjetivos] = useState<number>(4);

  const [exportando, setExportando] = useState(false);
  const [guardandoEstado, setGuardandoEstado] = useState(false);
  const [guardandoDireccion, setGuardandoDireccion] = useState(false);

  const hidratadoRef = useRef<string | null>(null);

  // ─── Hidratación ────────────────────────────────────────
  useEffect(() => {
    if (!expediente) {
      hidratadoRef.current = null;
      setEstado("");
      setEstadoOriginal("");
      setDireccion("");
      setDireccionOriginal("");
      setTema("");
      setArea("");
      setNumObjetivos(4);
      return;
    }

    if (hidratadoRef.current === expediente.id) return;
    hidratadoRef.current = expediente.id;

    // Estado viene de la columna estado_expediente
    setEstado(expediente.estado_expediente ?? "");
    setEstadoOriginal(expediente.estado_expediente ?? "");

    // Dirección viene de la columna direccion
    setDireccion(expediente.direccion ?? "");
    setDireccionOriginal(expediente.direccion ?? "");

    // Tema y área siguen viviendo en meta
    setTema((expediente.meta?.tema ?? "").trim());
    setArea((expediente.meta?.area ?? "").trim());
    setNumObjetivos(expediente.meta?.num_objetivos ?? 4);
  }, [expediente]);

  // ─── Handles de generación ──────────────────────────────
  const handles = useExpedienteHandles({
    idExpediente,
    tema,
    area,
    numObjetivos,
    estado,
    guardarMeta,
    refrescar,
  });

  // ─── Flags de cambios sin guardar ───────────────────────
  const hayCambiosSinGuardar =
    estado !== estadoOriginal || direccion !== direccionOriginal;

  const guardarEstado = async () => {


    if (!idExpediente || estado === estadoOriginal) {
      
      return;
    }

    setGuardandoEstado(true);
    try {
      
      const res = await actualizarEstado(idExpediente, estado);
      

      setEstadoOriginal(estado);
      await refrescar();
      exito({ mensaje: "Estado guardado" });
    } catch (err: any) {
      
      toastError({ mensaje: err.message || "No se pudo guardar" });
    } finally {
      setGuardandoEstado(false);
    }
  };

  const guardarDireccion = async () => {
   

    if (!idExpediente || direccion === direccionOriginal) {
   
      return;
    }

    setGuardandoDireccion(true);
    try {
      
      const res = await actualizarDireccion(idExpediente, direccion || null);
      

      setDireccionOriginal(direccion);
      await refrescar();
      exito({ mensaje: "Dirección guardada" });
    } catch (err: any) {
      
      toastError({ mensaje: err.message || "No se pudo guardar" });
    } finally {
      setGuardandoDireccion(false);
    }
  };

  // ─── Exportar ZIP ───────────────────────────────────────
  const exportarZip = async () => {
    if (!idExpediente) return;

    setExportando(true);
    try {
      // 1) Disparar generación.
      //    Con QUEUE_CONNECTION=sync, status ya viene 'done'.
      const { job_id, status: initialStatus } = await solicitarZip(idExpediente);
      let status = initialStatus;

      // 2) Si está en cola, hacer polling hasta que termine.
      while (status === "pending" || status === "processing") {
        await new Promise((r) => setTimeout(r, 2000));
        const res = await estadoZip(job_id);
        status = res.status;
      }

      if (status === "error") {
        throw new Error("No se pudo generar el ZIP");
      }

      // 3) Descargar el archivo ya generado.
      await descargarZipPorJob(job_id, `expediente-${idExpediente}.zip`);

    } catch (err: any) {

      toastError({ mensaje: err.message || "No se pudo generar el ZIP" });
    } finally {
      setExportando(false);
    }
  };

  // ─── Return ─────────────────────────────────────────────
  return {
    // Expediente
    expediente,
    loading,
    error,

    // Datos generales
    estado,
    setEstado,
    guardarEstado,
    guardandoEstado,

    direccion,
    setDireccion,
    guardarDireccion,
    guardandoDireccion,

    tema,
    setTema,
    area,
    setArea,
    numObjetivos,
    setNumObjetivos,

    // Exportación
    exportarZip,
    exportando,

    // Flags
    hayCambiosSinGuardar,

    // Handles de generación
    ...handles,
  };
}