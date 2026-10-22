import { createContext, useContext } from "react";
import type { Expediente } from "@/types/expediente";

// =========================================================
// TIPOS
// =========================================================

export type ExpedienteContextValue = {
  // ─── Expediente ─────────────────────────────────────────
  expediente: Expediente;

  // ─── Datos generales ────────────────────────────────────
  estado: string;
  setEstado: (v: string) => void;
  guardarEstado: () => void;
  guardandoEstado: boolean;

  direccion: string;
  setDireccion: (v: string) => void;
  guardarDireccion: () => void;
  guardandoDireccion: boolean;

  tema: string;
  setTema: (v: string) => void;

  area: string;
  setArea: (v: string) => void;


  // ─── Generación de propuesta ────────────────────────────
  genError: string | null;
  generatingPropuesta: boolean;
  progresoPropuesta: string | null;
  generarPropuesta: () => Promise<void>;

  // ─── Generación de entregable ───────────────────────────
  generandoSecciones: Record<string, boolean>;
  erroresSecciones: Record<string, string | null>;
  generarEntregable: () => Promise<void>;
  generarSeccionEntregable: (seccion: string) => Promise<void>;

  // ─── Persistencia de secciones ──────────────────────────
  guardarPropuesta: (s: Record<string, string>) => Promise<void>;
  guardarEntregable: (s: Record<string, string>) => Promise<void>;

  // ─── Estado de cambios sin guardar ──────────────────────
  hayCambiosSinGuardar: boolean;
  hayEditsPropuesta: boolean;
  hayEditsEntregable: boolean;

  registrarEditsPropuesta: (hay: boolean) => void;
  registrarEditsEntregable: (hay: boolean) => void;

  // ─── Configuración de objetivos ─────────────────────────
  numObjetivos: number;
  setNumObjetivos: (v: number) => void;
};

// =========================================================
// CONTEXTO
// =========================================================

const ExpedienteContext = createContext<ExpedienteContextValue | null>(null);

// =========================================================
// HOOK
// =========================================================

export function useExpedienteContext() {
  const ctx = useContext(ExpedienteContext);
  if (!ctx) {
    throw new Error("useExpedienteContext debe usarse dentro de ExpedienteProvider");
  }
  return ctx;
}

// =========================================================
// PROVIDER
// =========================================================

export const ExpedienteProvider = ExpedienteContext.Provider;