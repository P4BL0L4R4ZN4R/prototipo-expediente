import { useState, useEffect, useCallback } from "react";

// ============================================================
// TIPOS
// ============================================================
export interface Concepto {
  id: string | number;
  concepto: string;
  monto: number;
}

export interface ConceptosResponse {
  datos: Concepto[];
  total: number;
  total_textual: string;
}

// ============================================================
// HELPER
// ============================================================
function getHeaders(): HeadersInit {
  const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

  return {
    "Content-Type": "application/json",
    Accept: "application/json",
    "X-CSRF-TOKEN": token ?? "",
  };
}

// ============================================================
// HOOK
// ============================================================
export function useConceptos(idExpediente: string) {
  // 👈 URL armada aquí adentro, con el id
  const API_URL = `/api/prototipo/excel/${idExpediente}/conceptos`;

  const [datos, setDatos] = useState<Concepto[]>([]);
  const [total, setTotal] = useState<number>(0);
  const [totalTextual, setTotalTextual] = useState<string>("");
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  const cargar = useCallback(async (): Promise<void> => {
    setLoading(true);
    setError(null);
    try {
      const res = await fetch(API_URL, {
        headers: getHeaders(),
        credentials: "same-origin",
      });

      if (!res.ok) throw new Error(`Error ${res.status}`);

      const data: ConceptosResponse = await res.json();

      setDatos(
        data.datos.map((d) => ({
          id: d.id ?? crypto.randomUUID(),
          concepto: d.concepto,
          monto: Number(d.monto) || 0,
        }))
      );
      setTotal(Number(data.total) || 0);
      setTotalTextual(data.total_textual ?? "");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error desconocido");
    } finally {
      setLoading(false);
    }
  }, [API_URL]);   // 👈 depende de la URL

  useEffect(() => {
    cargar();
  }, [cargar]);

  const agregar = useCallback(
    async (concepto: string, monto: string | number): Promise<void> => {
      const nueva: Concepto = {
        id: crypto.randomUUID(),
        concepto,
        monto: Number(monto),
      };
      setDatos((prev) => [...prev, nueva]);

      try {
        const res = await fetch(API_URL, {
          method: "POST",
          headers: getHeaders(),
          credentials: "same-origin",
          body: JSON.stringify({ concepto, monto: nueva.monto }),
        });

        if (!res.ok) {
          const errData = await res.json().catch(() => ({}));
          throw new Error(errData.message || "Error al guardar");
        }

        const guardado: Concepto = await res.json();
        setDatos((prev) =>
          prev.map((d) => (d.id === nueva.id ? { ...d, id: guardado.id } : d))
        );
      } catch (err) {
        setDatos((prev) => prev.filter((d) => d.id !== nueva.id));
        setError(err instanceof Error ? err.message : "Error desconocido");
      }
    },
    [API_URL]
  );

  const eliminar = useCallback(
    async (id: Concepto["id"]): Promise<void> => {
      const backup = datos;
      setDatos((prev) => prev.filter((d) => d.id !== id));

      try {
        const res = await fetch(`${API_URL}/${id}`, {
          method: "DELETE",
          headers: getHeaders(),
          credentials: "same-origin",
        });
        if (!res.ok) throw new Error();
      } catch {
        setDatos(backup);
      }
    },
    [datos, API_URL]
  );

  const editar = useCallback(
    async (id: Concepto["id"], nuevoMonto: string | number): Promise<void> => {
      const backup = datos;
      setDatos((prev) =>
        prev.map((d) => (d.id === id ? { ...d, monto: Number(nuevoMonto) } : d))
      );

      try {
        const res = await fetch(`${API_URL}/${id}`, {
          method: "PUT",
          headers: getHeaders(),
          credentials: "same-origin",
          body: JSON.stringify({ monto: Number(nuevoMonto) }),
        });
        if (!res.ok) throw new Error();
      } catch {
        setDatos(backup);
      }
    },
    [datos, API_URL]
  );

  return {
    datos,
    total,
    totalTextual,
    loading,
    error,
    recargar: cargar,
    agregar,
    eliminar,
    editar,
  };
}