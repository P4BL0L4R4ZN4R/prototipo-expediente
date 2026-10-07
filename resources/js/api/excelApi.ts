import type { Expediente } from "@/types/expediente";

const BASE = "/api/prototipo/excel";

// =========================================================
// UTILIDADES
// =========================================================

/**
 * Lee el token CSRF desde el meta tag o desde la cookie XSRF-TOKEN.
 */
function getCsrf(): string {
  const meta = document.querySelector(
    'meta[name="csrf-token"]'
  ) as HTMLMetaElement | null;

  if (meta?.content) return meta.content;

  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return match ? decodeURIComponent(match[1]) : "";
}

/**
 * Wrapper de fetch que centraliza:
 * - CSRF
 * - headers
 * - credentials
 * - manejo de errores
 */
async function fetchJson<T = any>(
  url: string,
  options: RequestInit = {}
): Promise<T> {
  const res = await fetch(url, {
    ...options,
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      "X-CSRF-TOKEN": getCsrf(),
      ...(options.headers ?? {}),
    },
  });

  if (!res.ok) {
    const error = await res.json().catch(() => ({}));
    throw new Error(
      error.detalle || error.error || `HTTP ${res.status}`
    );
  }

  return res.json();
}

// =========================================================
// EXPEDIENTES — CRUD básico
// =========================================================

/**
 * Sube un Excel y devuelve el expediente parseado.
 */
export async function subirExcel(
  file: File
): Promise<{ id: string; expediente: Expediente }> {
  const form = new FormData();
  form.append("excel", file);

  return fetchJson(`${BASE}/upload`, {
    method: "POST",
    body: form,
  });
}

/**
 * Lista todos los expedientes procesados.
 */
export async function listarExpedientes(): Promise<
  { id: string; path?: string }[]
> {
  return fetchJson(BASE);
}

/**
 * Obtiene un expediente completo por id.
 */
export async function obtenerExpediente(
  id: string
): Promise<Expediente> {
  return fetchJson(`${BASE}/${id}`);
}

/**
 * Elimina un expediente.
 */
export async function eliminarExpediente(
  id: string
): Promise<{ ok: boolean }> {
  return fetchJson(`${BASE}/${id}`, { method: "DELETE" });
}

/**
 * Actualiza meta, propuesta_ia o entregable de un expediente.
 */
export async function actualizarExpediente(
  id: string,
  payload: {
    meta?: Record<string, any>;
    propuesta_ia?: Record<string, any>;
    entregable?: Record<string, any>;
  }
): Promise<Expediente> {
  return fetchJson(`${BASE}/${id}`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
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
  return fetchJson(`${BASE}/propuesta/${id}/${seccion}`);
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
  return fetchJson(`${BASE}/entregable/${id}/${seccion}`);
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
    url: (id) => `${BASE}/word/cotizacion/inicial/${id}`,
  },
  {
    key: "cotizacion-final",
    label: "Cotización final",
    url: (id) => `${BASE}/word/cotizacion/final/${id}`,
  },
  {
    key: "calendario",
    label: "Calendario",
    url: (id) => `${BASE}/word/calendario/${id}`,
  },
  {
    key: "resumen",
    label: "Resumen",
    url: (id) => `${BASE}/word/resumen/${id}`,
  },
  {
    key: "acuse",
    label: "Acuse",
    url: (id) => `${BASE}/word/acuse/${id}`,
  },
  {
    key: "propuesta",
    label: "Propuesta",
    url: (id) => `${BASE}/propuesta/${id}/word`,
  },
  {
    key: "entregable",
    label: "Entregable",
    url: (id) => `${BASE}/entregable/${id}/word`,
  },
];



/**
 * Descarga los 7 documentos con delay entre cada uno
 * para evitar el bloqueo de descargas múltiples del navegador.
 */
export async function descargarTodos(
  id: string,
  delayMs = 500,
  onProgreso?: (
    indice: number,
    total: number,
    doc: DocumentoWord
  ) => void
) {
  const total = DOCUMENTOS_WORD.length;

  for (let i = 0; i < total; i++) {
    const doc = DOCUMENTOS_WORD[i];

    onProgreso?.(i + 1, total, doc);

    descargarDocumento(doc.url(id));

    if (i < total - 1) {
      await new Promise((r) => setTimeout(r, delayMs));
    }
  }
}

// =========================================================
// EXPORTACIÓN ZIP
// =========================================================

/**
 * Descarga todos los documentos del expediente en un solo ZIP.
 */
export function descargarZip(id: string) {
  const url = `${BASE}/${id}/zip`;
  descargarDocumento(url);
}

/**
 * Dispara la descarga de un archivo usando un <a> temporal.
 * El navegador maneja el Content-Disposition del backend.
 */
export function descargarDocumento(url: string) {
  const a = document.createElement("a");
  a.href = url;
  a.style.display = "none";
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}