import { useCallback, useEffect, useState } from "react";
import {
  obtenerExpediente,
  actualizarExpediente,
  generarPropuestaSeccion,
  generarEntregableSeccion,
  SECCIONES_PROPUESTA,
} from "@/api/excelApi";
import type { Expediente } from "@/types/expediente";

// =========================================================
// KEYS INTERNAS DEL ENTREGABLE
// =========================================================
// Se usan con guion bajo para coincidir con `expediente.entregable`.
// La conversión a guion se hace SOLO al momento de hacer el fetch.

const SECCIONES_ENTREGABLE_KEYS = [
  "introduccion",
  "problematica",
  "desarrollo_conceptos",
] as const;

interface Options {
  idExpediente: string | null;
  tema: string;
  area: string;
  estado: string;
  numObjetivos: number;
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

  const [fechaPropuesta, setFechaPropuesta] = useState<Date | null>(null);
  const [fechaEntregable, setFechaEntregable] = useState<Date | null>(null);

  useEffect(() => {
    if (!expediente) {
      setFechaPropuesta(null);
      setFechaEntregable(null);
      return;
    }

    setFechaPropuesta(
      expediente.fecha_propuesta ? new Date(expediente.fecha_propuesta) : null
    );
    setFechaEntregable(
      expediente.fecha_entregable ? new Date(expediente.fecha_entregable) : null
    );
  }, [expediente]);

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

      console.log("[guardarMeta] enviando al backend:", {
        idExpediente,
        meta_recibido: meta,
        tiene_num_objetivos: "num_objetivos" in meta,
        valor_num_objetivos: meta.num_objetivos,
      });

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
    fechaPropuesta,
    setFechaPropuesta,
    fechaEntregable,
    setFechaEntregable,
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
  numObjetivos,
  guardarMeta,
  refrescar,
}: Options) {
  const [genError, setGenError] = useState<string | null>(null);

  // ─── Propuesta (sin cambios) ─────────────────────────────
  const [generatingPropuesta, setGeneratingPropuesta] = useState(false);
  const [progresoPropuesta, setProgresoPropuesta] = useState<string | null>(null);

  // ─── Entregable: estado granular por sección ────────────
  const [generandoSecciones, setGenerandoSecciones] = useState<Record<string, boolean>>({});
  const [erroresSecciones, setErroresSecciones] = useState<Record<string, string | null>>({});

  // =========================================================
  // PROPUESTA
  // =========================================================

  const handleGenerarPropuesta = async () => {
    if (!idExpediente) return;

    if (!tema || !area) {
      setGenError("Completa Tema y Área primero.");
      return;
    }

    setGeneratingPropuesta(true);
    setGenError(null);
    setProgresoPropuesta(null);

    console.log("[handleGenerarPropuesta] lo que se va a guardar:", {
      estado,
      tema,
      area,
      numObjetivos,
      tipoNumObjetivos: typeof numObjetivos,
    });

    try {
      await guardarMeta({
        estado,
        tema,
        area,
        num_objetivos: numObjetivos,
      });

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

  // =========================================================
  // ENTREGABLE — una sección
  // =========================================================

  const generarSeccionEntregable = async (seccionKey: string) => {
    if (!idExpediente) return;
    if (generandoSecciones[seccionKey]) return;

    setGenerandoSecciones((prev) => ({ ...prev, [seccionKey]: true }));
    setErroresSecciones((prev) => ({ ...prev, [seccionKey]: null }));

    try {
      // Convertir guion bajo → guion SOLO para la URL
      const urlKey = seccionKey.replace(/_/g, "-");
      await generarEntregableSeccion(idExpediente, urlKey);
       await refrescar();
    } catch (err: any) {
      setErroresSecciones((prev) => ({
        ...prev,
        [seccionKey]: err?.message || "Error al generar la sección",
      }));
      throw err;
    } finally {
      setGenerandoSecciones((prev) => ({ ...prev, [seccionKey]: false }));
    }
  };

  // =========================================================
  // ENTREGABLE — todas en paralelo
  // =========================================================

  const handleGenerarEntregable = async () => {
    if (!idExpediente) return;

    if (!tema || !area) {
      setGenError("Completa Tema y Área primero.");
      return;
    }

    setGenError(null);

    // Guardar meta una sola vez antes de generar
    try {
      await guardarMeta({
        estado,
        tema,
        area,
        num_objetivos: numObjetivos,
      });
    } catch (err: any) {
      setGenError(err?.message || "Error al guardar la configuración");
      return;
    }

    // Lanzar las 3 en paralelo; si una falla, las otras continúan
    await Promise.allSettled(
      SECCIONES_ENTREGABLE_KEYS.map((k) => generarSeccionEntregable(k))
    );

    // Refrescar una sola vez al final para traer los .txt generados
    await refrescar();
  };

  // =========================================================
  // GUARDAR (sin cambios)
  // =========================================================

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

    // Propuesta
    generatingPropuesta,
    progresoPropuesta,
    handleGenerarPropuesta,

    // Entregable (nuevo esquema)
    generandoSecciones,
    erroresSecciones,
    generarSeccionEntregable,
    handleGenerarEntregable,

    // Guardado
    handleGuardarPropuesta,
    handleGuardarEntregable,
  };
}