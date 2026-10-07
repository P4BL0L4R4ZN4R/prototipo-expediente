import type { Expediente } from "@/types/expediente";

const BASE = "/api/prototipo/excel";

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
  { id: string; path: string }[]
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