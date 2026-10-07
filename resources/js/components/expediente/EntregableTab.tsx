import { useState } from "react";
import ConfigIA from "./ConfigIA";
import SeccionEditable from "./SeccionEditable";
import type { Expediente } from "@/types/expediente";
import { useExpediente, useExpedienteHandles } from "@/hooks";

interface Props {
  expediente: Expediente;
  tema: string;
  area: string;
  generating: boolean;
  progreso: string | null;
  genError: string | null;
  onTemaChange: (v: string) => void;
  onAreaChange: (v: string) => void;
  onGenerar: () => void;
  onGuardarSecciones: (secciones: Record<string, string>) => Promise<void>;
}

export default function EntregableTab({
  expediente,
  tema,
  area,
  generating,
  progreso,
  genError,
  onTemaChange,
  onAreaChange,
  onGenerar,
  onGuardarSecciones,
}: Props) {
  const [edits, setEdits] = useState<Record<string, string>>({});
  const [guardando, setGuardando] = useState(false);
  const [guardadoOk, setGuardadoOk] = useState(false);
  const [guardadoError, setGuardadoError] = useState<string | null>(null);

  const tienePropuesta =
    expediente.propuesta_ia &&
    Object.keys(expediente.propuesta_ia).length > 0;

  const tieneEntregable =
    expediente.entregable &&
    Object.keys(expediente.entregable).length > 0;

  if (!tienePropuesta) {
    return (
      <div className="alert alert-warning mb-0">
        <strong>Bloqueado.</strong> Genera primero la Propuesta para habilitar
        el Entregable.
      </div>
    );
  }

  const handleCambio = (seccion: string, texto: string) => {
    setEdits((prev) => ({
      ...prev,
      [seccion]: texto,
    }));
  };

  const handleGuardar = async () => {
    if (Object.keys(edits).length === 0) return;

    setGuardando(true);
    setGuardadoError(null);
    setGuardadoOk(false);

    try {
      await onGuardarSecciones(edits);

      setEdits({});
      setGuardadoOk(true);

      setTimeout(() => {
        setGuardadoOk(false);
      }, 2000);
    } catch (err: any) {
      setGuardadoError(
        err.message || "Error al guardar los cambios."
      );
    } finally {
      setGuardando(false);
    }
  };

  const tieneEdits = Object.keys(edits).length > 0;

  return (
    <>
      <ConfigIA
        tema={tema}
        area={area}
        onTemaChange={onTemaChange}
        onAreaChange={onAreaChange}
      />

      <div className="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <button
          type="button"
          className="btn btn-primary btn-sm"
          onClick={onGenerar}
          disabled={!tema || !area || generating}
        >
          {generating ? (
            <>
              <span
                className="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              ></span>
              Generando: {progreso ?? "..."}
            </>
          ) : (
            "Procesar Entregable"
          )}
        </button>

        <button
          type="button"
          className="btn btn-outline-success btn-sm"
          onClick={handleGuardar}
          disabled={!tieneEdits || guardando || generating}
        >
          {guardando ? "Guardando..." : "Guardar cambios"}
        </button>

        {guardadoOk && (
          <span className="text-success" style={{ fontSize: 13 }}>
            ✓ Guardado
          </span>
        )}

        {guardadoError && (
          <span className="text-danger" style={{ fontSize: 13 }}>
            {guardadoError}
          </span>
        )}

        {(!tema || !area) && (
          <span className="text-muted" style={{ fontSize: 13 }}>
            Completa Tema y Área primero
          </span>
        )}
      </div>

      {genError && (
        <div className="alert alert-danger" role="alert">
          {genError}
        </div>
      )}

      {tieneEntregable ? (
        Object.entries(expediente.entregable!).map(([key, val]: any) => (
          <SeccionEditable
            key={key}
            seccion={key}
            titulo={key.replace(/_/g, " ")}
            texto={val?.texto ?? ""}
            generando={generating}
            onCambio={handleCambio}
          />
        ))
      ) : (
        <div className="alert alert-secondary">
          <em>Sin generar aún.</em>
        </div>
      )}
    </>
  );
}