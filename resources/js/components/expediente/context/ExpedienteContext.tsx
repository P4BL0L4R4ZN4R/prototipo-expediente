import { createContext, useContext } from "react";
import type { Expediente } from "@/types/expediente";

export type ExpedienteContextValue = {
  expediente: Expediente;

  // Meta / estado (sin cambios)
  estado: string;
  setEstado: (v: string) => void;
  guardarEstado: () => void;
  guardandoEstado: boolean;
  tema: string;
  setTema: (v: string) => void;
  area: string;
  setArea: (v: string) => void;

  // Fechas (sin cambios)
  fechaPropuesta: Date | null;
  setFechaPropuesta: (d: Date | null) => void;
  fechaEntregable: Date | null;
  setFechaEntregable: (d: Date | null) => void;

  // Handles (MODIFICADO)
  genError: string | null;
  generatingPropuesta: boolean;
  progresoPropuesta: string | null;
  
  // 👇 MODIFICADO: ya no es un solo booleano, es un mapa por sección
  generandoSecciones: Record<string, boolean>;
  erroresSecciones: Record<string, string | null>;
  
  // 👇 MODIFICADO: firma nueva
  generarPropuesta: () => Promise<void>;     
  generarEntregable: () => Promise<void>;
  generarSeccionEntregable: (seccion: string) => Promise<void>;

  guardarPropuesta: (s: Record<string, string>) => Promise<void>;
  guardarEntregable: (s: Record<string, string>) => Promise<void>;
  hayCambiosSinGuardar: boolean;
  registrarEditsPropuesta: (hay: boolean) => void;
  registrarEditsEntregable: (hay: boolean) => void;
  hayEditsPropuesta: boolean;
  hayEditsEntregable: boolean;

  hayEditsFechas: boolean;
  marcarEditsFechas: () => void;
  limpiarEditsFechas: () => void;

  numObjetivos: number;
  setNumObjetivos: (v: number) => void;

  persistirFechas: () => Promise<void>;
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