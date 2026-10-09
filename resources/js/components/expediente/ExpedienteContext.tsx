import { createContext, useContext } from "react";
import type { Expediente } from "@/types/expediente";

export type ExpedienteContextValue = {
  expediente: Expediente;
  // Meta / estado
  estado: string;
  setEstado: (v: string) => void;
  guardarEstado: () => void;
  guardandoEstado: boolean;
  tema: string;
  setTema: (v: string) => void;
  area: string;
  setArea: (v: string) => void;
  // Handles
  genError: string | null;
  generatingPropuesta: boolean;
  progresoPropuesta: string | null;
  generatingEntregable: boolean;
  progresoEntregable: string | null;
  generarPropuesta: () => void;
  generarEntregable: () => void;
  guardarPropuesta: (s: Record<string, string>) => Promise<void>;
  guardarEntregable: (s: Record<string, string>) => Promise<void>;
};

const ExpedienteContext = createContext<ExpedienteContextValue | null>(null);

export function useExpedienteContext() {
  const ctx = useContext(ExpedienteContext);
  if (!ctx) {
    throw new Error("useExpedienteContext debe usarse dentro de ExpedienteProvider");
  }
  return ctx;
}

export const ExpedienteProvider = ExpedienteContext.Provider;