import { useState } from "react";
import ConfigIA from "./ConfigIA";
import SeccionEditable from "./SeccionEditable";
import { useExpedienteContext } from "./ExpedienteContext";
import { error as toastError } from "@/utils/alert";
import "@/../css/Secciones.css";

export default function EntregableTab() {
  const {
    expediente,
    tema,
    setTema,
    area,
    setArea,
    generatingEntregable: generating,
    progresoEntregable: progreso,
    genError,
    generarEntregable,
    guardarEntregable,
  } = useExpedienteContext();

  const [edits, setEdits] = useState<Record<string, string>>({});
  const [guardando, setGuardando] = useState(false);
  const [guardadoOk, setGuardadoOk] = useState(false);
  const [seccionActiva, setSeccionActiva] = useState<string | null>(null);

  const tienePropuesta =
    expediente.propuesta_ia &&
    Object.keys(expediente.propuesta_ia).length > 0;

  const secciones = expediente.entregable
    ? Object.entries(expediente.entregable)
    : [];

  const activa =
    seccionActiva && secciones.some(([k]) => k === seccionActiva)
      ? seccionActiva
      : secciones[0]?.[0] ?? null;

  const handleCambio = (s: string, t: string) =>
    setEdits((prev) => ({ ...prev, [s]: t }));

  const handleGuardar = async () => {
    if (!Object.keys(edits).length) return;
    setGuardando(true);
    try {
      await guardarEntregable(edits);
      setEdits({});
      setGuardadoOk(true);
      setTimeout(() => setGuardadoOk(false), 2000);
    } catch (err: any) {
      toastError({ mensaje: err.message || "Error al guardar" });
    } finally {
      setGuardando(false);
    }
  };

  if (!tienePropuesta) {
    return (
      <div className="alert alert-warning mb-0">
        <strong>Bloqueado.</strong> Genera primero la Propuesta.
      </div>
    );
  }

  return (
    <>
      <ConfigIA
        tema={tema}
        area={area}
        onTemaChange={setTema}
        onAreaChange={setArea}
      />

      <div className="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <button
          type="button"
          className="btn btn-primary btn-sm"
          onClick={generarEntregable}
          disabled={!tema || !area || generating}
        >
          {generating ? (
            <>
              <span className="spinner-border spinner-border-sm me-2" />
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
          disabled={!Object.keys(edits).length || guardando || generating}
        >
          {guardando ? "Guardando..." : "Guardar cambios"}
        </button>

        {guardadoOk && <span className="text-success small">✓ Guardado</span>}
      </div>

      {genError && (
        <div className="alert alert-danger" role="alert">
          {genError}
        </div>
      )}

      {!secciones.length ? (
        <div className="alert alert-secondary">
          <em>Sin generar aún.</em>
        </div>
      ) : (
        <>
          <ul className="nav nav-tabs secciones-tabs flex-nowrap text-nowrap">
            {secciones.map(([key]) => (
              <li className="nav-item" key={key}>
                <button
                  type="button"
                  className={`nav-link text-capitalize ${
                    activa === key ? "active" : ""
                  }`}
                  onClick={() => setSeccionActiva(key)}
                >
                  <span className="d-inline-flex align-items-center gap-2">
                    <span>{key.replace(/_/g, " ")}</span>
                    {edits[key] !== undefined && (
                      <span className="badge text-bg-warning">●</span>
                    )}
                  </span>
                </button>
              </li>
            ))}
          </ul>

          <div className="pt-3">
            {activa && (
              <SeccionEditable
                key={activa}
                seccion={activa}
                titulo={activa.replace(/_/g, " ")}
                texto={expediente.entregable?.[activa]?.texto ?? ""}
                generando={generating}
                onCambio={handleCambio}
              />
            )}
          </div>
        </>
      )}
    </>
  );
}