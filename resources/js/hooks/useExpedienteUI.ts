// resources/js/components/expediente/useExpedienteUI.ts
import { useEffect, useState } from "react";
import { useExpediente, useExpedienteHandles } from "@/hooks";
import { actualizarMeta, descargarZip } from "@/api/excelApi";
import { confirmar, exito, error as toastError } from "@/utils/alert";

export function useExpedienteUI(idExpediente: string | null, open: boolean) {
  const { expediente, loading, error, refrescar, guardarMeta } =
    useExpediente(idExpediente, open);

  const [estado, setEstado] = useState("");
  const [estadoOriginal, setEstadoOriginal] = useState("");
  const [tema, setTema] = useState("");
  const [area, setArea] = useState("");

  const [exportando, setExportando] = useState(false);
  const [guardandoEstado, setGuardandoEstado] = useState(false);

  useEffect(() => {
    if (expediente?.meta) {
      setEstado(expediente.meta.estado ?? "");
      setEstadoOriginal(expediente.meta.estado ?? "");
      setTema(expediente.meta.tema ?? "");
      setArea(expediente.meta.area ?? "");
    }
  }, [expediente]);

  const handles = useExpedienteHandles({
    idExpediente,
    tema,
    area,
    estado,
    guardarMeta,
    refrescar,
  });

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

  return {
    expediente,
    loading,
    error,
    estado, setEstado,
    tema, setTema,
    area, setArea,
    guardarEstado,
    guardandoEstado,
    exportarZip,
    exportando,
    ...handles,
  };
}