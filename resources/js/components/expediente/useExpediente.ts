import { useEffect, useState, useCallback } from "react";

export interface Expediente {
  id: string;
  ia: Record<string, any>;
  data: Record<string, any>;
  meta: Record<string, any> | null;
  propuesta_ia: Record<string, any> | null;
  entregable: Record<string, any> | null;
}

export function useExpediente(idExpediente: string | null, open: boolean) {
  const [expediente, setExpediente] = useState<Expediente | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const cargar = useCallback(async () => {
    if (!idExpediente) return;

    setLoading(true);
    setError(null);
    setExpediente(null);

    try {
      const res = await fetch(`/api/prototipo/excel/${idExpediente}`);
      if (!res.ok) throw new Error("No se pudo cargar el expediente");
      const data = await res.json();

      console.log("[expediente] cargado:", data);
      console.log("[expediente] meta:", data.meta);
      console.log("[expediente] propuesta_ia:", data.propuesta_ia);
      console.log("[expediente] entregable:", data.entregable);

      setExpediente(data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [idExpediente]);

  const refrescar = useCallback(async () => {
    if (!idExpediente) return;

    const res = await fetch(`/api/prototipo/excel/${idExpediente}`);
    const data = await res.json();

    console.log("[refresh] expediente recibido:", data);
    console.log("[refresh] propuesta_ia:", data.propuesta_ia);
    console.log("[refresh] entregable:", data.entregable);

    setExpediente(data);
  }, [idExpediente]);

  const guardarMeta = useCallback(
    async (meta: Record<string, any>) => {
      if (!idExpediente) return;

      const res = await fetch(`/api/prototipo/excel/${idExpediente}`, {
        method: "PATCH",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({ meta }),
      });

      if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.error || "Error al guardar configuración");
      }
    },
    [idExpediente]
  );

  useEffect(() => {
    if (open && idExpediente) {
      cargar();
    }
  }, [open, idExpediente, cargar]);

  return { expediente, loading, error, cargar, refrescar, guardarMeta };
}