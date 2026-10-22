import api from "@/api/client";
import type { Expediente } from "@/types/expediente";

/**
 * Devuelve el prefijo de la URL con la plaza actual.
 * Se calcula en cada llamada para que refleje cambios en localStorage.
 */
function base(): string {
  const plaza = localStorage.getItem("plaza") ?? "test00";
  return `/${plaza}/prototipo/excel`;
}

// =========================================================
// EXPEDIENTES — CRUD básico
// =========================================================

export async function subirExcel(
  file: File
): Promise<{ id: string; expediente: Expediente }> {
  const form = new FormData();
  form.append("excel", file);

  const { data } = await api.post(`${base()}/upload`, form);
  return data;
}

export async function listarExpedientes(): Promise<
  { id: string; path?: string }[]
> {
  const { data } = await api.get(base());
  return data;
}

export async function obtenerExpediente(id: string): Promise<Expediente> {
  const { data } = await api.get(`${base()}/${id}`);
  return data;
}

export async function eliminarExpediente(
  id: string
): Promise<{ ok: boolean }> {
  const { data } = await api.delete(`${base()}/${id}`);
  return data;
}

export async function actualizarExpediente(
  id: string,
  payload: {
    meta?: Record<string, any>;
    propuesta_ia?: Record<string, any>;
    entregable?: Record<string, any>;
    estado_expediente?: string;
    direccion?: string | null;
  }
): Promise<Expediente> {
  const { data } = await api.patch(`${base()}/${id}`, payload);
  return data;
}

export async function actualizarEstado(
  id: string,
  estado: string
): Promise<Expediente> {
  const { data } = await api.patch(`${base()}/${id}`, {
    estado_expediente: estado,
  });
  return data;
}

export async function actualizarDireccion(
  id: string,
  direccion: string | null
): Promise<Expediente> {
  const { data } = await api.patch(`${base()}/${id}`, { direccion });
  return data;
}

export async function actualizarMeta(
  id: string,
  meta: Record<string, any>
): Promise<{ ok: boolean }> {
  const { data } = await api.patch(`${base()}/${id}`, { meta });
  return data;
}

// =========================================================
// GENERACIÓN — PROPUESTA
// =========================================================

export const SECCIONES_PROPUESTA = [
  { key: "texto-propuesta", label: "Texto propuesta" },
  { key: "introduccion", label: "Introducción" },
  { key: "problematica", label: "Problemática" },
  { key: "objetivo-general", label: "Objetivo general" },
  { key: "objetivos-especificos", label: "Objetivos específicos" },
  { key: "metodologia", label: "Metodología" },
] as const;

export async function generarPropuestaSeccion(
  id: string,
  seccion: string
): Promise<any> {
  const { data } = await api.post(`${base()}/propuesta/${id}/${seccion}`);
  return data;
}

// =========================================================
// GENERACIÓN — ENTREGABLE
// =========================================================

export const SECCIONES_ENTREGABLE = [
  { key: "introduccion", label: "Introducción" },
  { key: "problematica", label: "Problemática" },
  { key: "desarrollo-conceptos", label: "Desarrollo conceptos" },
] as const;

export async function generarEntregableSeccion(
  id: string,
  seccion: string
): Promise<any> {
  const { data } = await api.post(`${base()}/entregable/${id}/${seccion}`);
  return data;
}

// =========================================================
// EXPORTACIÓN WORD
// =========================================================

export type DocumentoWord = {
  key: string;
  label: string;
  url: (id: string) => string;
};

export const DOCUMENTOS_WORD: DocumentoWord[] = [
  {
    key: "cotizacion-inicial",
    label: "Cotización inicial",
    url: (id) => `${base()}/word/cotizacion/inicial/${id}`,
  },
  {
    key: "cotizacion-final",
    label: "Cotización final",
    url: (id) => `${base()}/word/cotizacion/final/${id}`,
  },
  {
    key: "calendario",
    label: "Calendario",
    url: (id) => `${base()}/word/calendario/${id}`,
  },
  {
    key: "resumen",
    label: "Resumen",
    url: (id) => `${base()}/word/resumen/${id}`,
  },
  {
    key: "acuse",
    label: "Acuse",
    url: (id) => `${base()}/word/acuse/${id}`,
  },
  {
    key: "propuesta",
    label: "Propuesta",
    url: (id) => `${base()}/propuesta/${id}/word`,
  },
  {
    key: "entregable",
    label: "Entregable",
    url: (id) => `${base()}/entregable/${id}/word`,
  },
];

// =========================================================
// DESCARGA DE DOCUMENTOS (Word / ZIP)
// =========================================================

/**
 * Descarga un documento (Word, ZIP) usando axios con responseType blob.
 * El token viaja en el interceptor de client.ts.
 */
export async function descargarDocumento(url: string, filename?: string) {
  const { data: blob } = await api.get(url, { responseType: "blob" });

  const objectUrl = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = objectUrl;
  a.download = filename ?? "";
  a.style.display = "none";
  document.body.appendChild(a);
  a.click();
  a.remove();
  window.URL.revokeObjectURL(objectUrl);
}

/**
 * Descarga los 7 documentos con delay entre cada uno
 * para evitar el bloqueo de descargas múltiples del navegador.
 */
export async function descargarTodos(
  id: string,
  delayMs = 500,
  onProgreso?: (indice: number, total: number, doc: DocumentoWord) => void
) {
  const total = DOCUMENTOS_WORD.length;

  for (let i = 0; i < total; i++) {
    const doc = DOCUMENTOS_WORD[i];

    onProgreso?.(i + 1, total, doc);

    await descargarDocumento(doc.url(id));

    if (i < total - 1) {
      await new Promise((r) => setTimeout(r, delayMs));
    }
  }
}

// =========================================================
// ZIP ASÍNCRONO (nuevo flujo: solicitar → polling → descargar)
// =========================================================

/**
 * LEGACY: descarga el ZIP en un solo request (flujo antiguo).
 * Se mantiene por compatibilidad, pero el flujo nuevo usa
 * solicitarZip + estadoZip + descargarZipPorJob.
 */
export async function descargarZip(id: string) {
  await descargarDocumento(`${base()}/${id}/zip`, `expediente-${id}.zip`);
}

/**
 * 1) Dispara la generación del ZIP.
 *    Con QUEUE_CONNECTION=sync el status ya vuelve 'done'.
 *    Con cola, vuelve 'pending' o 'processing'.
 */
export async function solicitarZip(
  id: string
): Promise<{ job_id: string; status: "pending" | "processing" | "done" | "error" }> {
  const { data } = await api.post(`${base()}/zip/${id}`);
  return data;
}

/**
 * 2) Consulta el estado del job.
 */
export async function estadoZip(jobId: string): Promise<{
  status: "pending" | "processing" | "done" | "error";
  ok?: number;
  fallidos?: string[];
  error?: string;
}> {
  const { data } = await api.get(`${base()}/zip/${jobId}/status`);
  return data;
}

/**
 * 3) Descarga el ZIP ya generado, reutilizando el patrón de descargarDocumento.
 */
export async function descargarZipPorJob(
  jobId: string,
  filename?: string
): Promise<void> {
  await descargarDocumento(`${base()}/zip/${jobId}/download`, filename);
}