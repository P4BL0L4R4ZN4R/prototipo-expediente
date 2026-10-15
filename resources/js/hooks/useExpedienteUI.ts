import { useEffect, useRef, useState } from "react";
import { format } from "date-fns";
import { useExpediente, useExpedienteHandles } from "@/hooks";
import { actualizarMeta, actualizarExpediente, descargarZip } from "@/api/excelApi";
import { exito, error as toastError } from "@/utils/alert";

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

  const [estado, setEstado] = useState("");
  const [estadoOriginal, setEstadoOriginal] = useState("");
  const [tema, setTema] = useState("");
  const [area, setArea] = useState("");
  const [numObjetivos, setNumObjetivos] = useState<number>(4);

  const [exportando, setExportando] = useState(false);
  const [guardandoEstado, setGuardandoEstado] = useState(false);

  // 👇 Ref para saber qué expediente ya hidratamos
  const hidratadoRef = useRef<string | null>(null);

  useEffect(() => {
    // Sin expediente → reset
    if (!expediente) {
      hidratadoRef.current = null;
      setEstado("");
      setEstadoOriginal("");
      setTema("");
      setArea("");
      setNumObjetivos(4);
      return;
    }

    // Si ya hidratamos este expediente, no volver a hacerlo
    if (hidratadoRef.current === expediente.id) return;

    // Primera vez que llega este expediente → hidratar
    hidratadoRef.current = expediente.id;

    setEstado(expediente.meta?.estado ?? "");
    setEstadoOriginal(expediente.meta?.estado ?? "");
    setTema((expediente.meta?.tema ?? "").trim());
    setArea((expediente.meta?.area ?? "").trim());
    setNumObjetivos(expediente.meta?.num_objetivos ?? 4);
  }, [expediente]);

  const handles = useExpedienteHandles({
    idExpediente,
    tema,
    area,
    numObjetivos,
    estado,
    guardarMeta,
    refrescar,
  });

  const hayCambiosSinGuardar = estado !== estadoOriginal;

  const guardarEstado = async () => {
    if (!idExpediente || estado === estadoOriginal) return;
    setGuardandoEstado(true);
    try {
      await actualizarMeta(idExpediente, { estado });
      setEstadoOriginal(estado);
      await refrescar();
      exito({ mensaje: "Estado guardado" });
    } catch (err: any) {
      toastError({ mensaje: err.message || "No se pudo guardar" });
    } finally {
      setGuardandoEstado(false);
    }
  };

  const exportarZip = () => {
    if (!idExpediente) return;
    setExportando(true);
    descargarZip(idExpediente);
    setTimeout(() => setExportando(false), 1500);
  };

  const persistirFechas = async () => {
    if (!idExpediente) throw new Error("No hay id de expediente");

    await actualizarExpediente(idExpediente, {
      fecha_propuesta: fechaPropuesta
        ? format(fechaPropuesta, "yyyy-MM-dd")
        : null,
      fecha_entregable: fechaEntregable
        ? format(fechaEntregable, "yyyy-MM-dd")
        : null,
    });

    await refrescar();
  };

  return {
    expediente,
    loading,
    error,
    estado,
    setEstado,
    tema,
    setTema,
    area,
    setArea,
    numObjetivos,
    setNumObjetivos,
    guardarEstado,
    guardandoEstado,
    exportarZip,
    exportando,
    hayCambiosSinGuardar,

    // fechas
    fechaPropuesta,
    setFechaPropuesta,
    fechaEntregable,
    setFechaEntregable,
    persistirFechas,

    ...handles,
  };
}