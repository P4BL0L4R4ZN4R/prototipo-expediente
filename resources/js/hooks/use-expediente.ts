import { useCallback, useEffect, useState } from "react";
import {
  obtenerExpediente,
  actualizarExpediente,
  generarPropuestaSeccion,
  generarEntregableSeccion,
  SECCIONES_PROPUESTA,
  SECCIONES_ENTREGABLE,
} from "@/api/excelApi";
import type { Expediente } from "@/types/expediente";

interface Options {
  idExpediente: string | null;
  tema: string;
  area: string;
  estado: string;
  guardarMeta: (meta: Record<string, any>) => Promise<void>;
  refrescar: () => Promise<void>;
}

// =========================================================
// CARGA DE DATOS
// =========================================================

export function useExpediente(idExpediente: string | null, open: boolean) {
  const [expediente, setExpediente] = useState<Expediente | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const refrescar = useCallback(async () => {
    if (!idExpediente) {
      setExpediente(null);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const data = await obtenerExpediente(idExpediente);
      setExpediente(data);
    } catch (err: any) {
      setError(err?.message || "Error al cargar el expediente");
    } finally {
      setLoading(false);
    }
  }, [idExpediente]);

  const guardarMeta = useCallback(
    async (meta: Record<string, any>) => {
      if (!idExpediente) return;

      await actualizarExpediente(idExpediente, { meta });
      await refrescar();
    },
    [idExpediente, refrescar]
  );

  useEffect(() => {
    if (open && idExpediente) {
      refrescar();
    } else if (!open) {
      setExpediente(null);
      setError(null);
    }
  }, [open, idExpediente, refrescar]);

  return {
    expediente,
    loading,
    error,
    refrescar,
    guardarMeta,
  };
}

// =========================================================
// ACCIONES (generar, guardar)
// =========================================================

export function useExpedienteHandles({
  idExpediente,
  tema,
  area,
  estado,
  guardarMeta,
  refrescar,
}: Options) {
  const [genError, setGenError] = useState<string | null>(null);

  const [generatingPropuesta, setGeneratingPropuesta] = useState(false);
  const [progresoPropuesta, setProgresoPropuesta] =
    useState<string | null>(null);

  const [generatingEntregable, setGeneratingEntregable] = useState(false);
  const [progresoEntregable, setProgresoEntregable] =
    useState<string | null>(null);

  const handleGenerarPropuesta = async () => {
    if (!idExpediente) return;

    if (!tema || !area) {
      setGenError("Completa Tema y Área primero.");
      return;
    }

    setGeneratingPropuesta(true);
    setGenError(null);
    setProgresoPropuesta(null);

    try {
      await guardarMeta({ estado, tema, area });

      for (let i = 0; i < SECCIONES_PROPUESTA.length; i++) {
        const s = SECCIONES_PROPUESTA[i];

        setProgresoPropuesta(
          `${s.label} (${i + 1}/${SECCIONES_PROPUESTA.length})`
        );

        await generarPropuestaSeccion(idExpediente, s.key);
      }

      await refrescar();
    } catch (err: any) {
      setGenError(err?.message || "Error al generar propuesta");
    } finally {
      setGeneratingPropuesta(false);
      setProgresoPropuesta(null);
    }
  };

  const handleGenerarEntregable = async () => {
    if (!idExpediente) return;

    if (!tema || !area) {
      setGenError("Completa Tema y Área primero.");
      return;
    }

    setGeneratingEntregable(true);
    setGenError(null);
    setProgresoEntregable(null);

    try {
      await guardarMeta({ estado, tema, area });

      for (let i = 0; i < SECCIONES_ENTREGABLE.length; i++) {
        const s = SECCIONES_ENTREGABLE[i];

        setProgresoEntregable(
          `${s.label} (${i + 1}/${SECCIONES_ENTREGABLE.length})`
        );

        await generarEntregableSeccion(idExpediente, s.key);
      }

      await refrescar();
    } catch (err: any) {
      setGenError(err?.message || "Error al generar entregable");
    } finally {
      setGeneratingEntregable(false);
      setProgresoEntregable(null);
    }
  };

  const handleGuardarPropuesta = async (
    secciones: Record<string, string>
  ) => {
    if (!idExpediente) return;

    const propuesta_ia: Record<string, any> = {};
    for (const [key, texto] of Object.entries(secciones)) {
      propuesta_ia[key] = { texto };
    }

    await actualizarExpediente(idExpediente, { propuesta_ia });
    await refrescar();
  };

  const handleGuardarEntregable = async (
    secciones: Record<string, string>
  ) => {
    if (!idExpediente) return;

    const entregable: Record<string, any> = {};
    for (const [key, texto] of Object.entries(secciones)) {
      entregable[key] = { texto };
    }

    await actualizarExpediente(idExpediente, { entregable });
    await refrescar();
  };

  return {
    genError,
    generatingPropuesta,
    progresoPropuesta,
    generatingEntregable,
    progresoEntregable,
    handleGenerarPropuesta,
    handleGenerarEntregable,
    handleGuardarPropuesta,
    handleGuardarEntregable,
  };
}