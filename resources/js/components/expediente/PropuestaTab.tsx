import { useState } from "react";
import { SplitButton, Dropdown } from "react-bootstrap";
import ConfigIA from "./ConfigIA";
import SeccionEditable from "./SeccionEditable";
import { useExpedienteContext } from "./ExpedienteContext";
import { error as toastError } from "@/utils/alert";
import "@/../css/Secciones.css";

interface Props {
  onGenerarEntregable: () => void;
}

type Accion = "guardar" | "guardarYGenerar";

export default function PropuestaTab({ onGenerarEntregable }: Props) {
  const {
    expediente,
    tema,
    setTema,
    area,
    setArea,
    generatingPropuesta: generating,
    progresoPropuesta: progreso,
    genError,
    generarPropuesta,
    guardarPropuesta,
  } = useExpedienteContext();

  const [edits, setEdits] = useState<Record<string, string>>({});
  const [guardando, setGuardando] = useState(false);
  const [guardadoOk, setGuardadoOk] = useState(false);
  const [accion, setAccion] = useState<Accion>("guardar");
  const [seccionActiva, setSeccionActiva] = useState<string | null>(null);

  const secciones = expediente.propuesta_ia
    ? Object.entries(expediente.propuesta_ia)
    : [];

  const tienePropuesta = secciones.length > 0;
  const tieneEdits = Object.keys(edits).length > 0;

  const activa =
    seccionActiva && secciones.some(([k]) => k === seccionActiva)
      ? seccionActiva
      : secciones[0]?.[0] ?? null;

  const handleCambio = (s: string, t: string) =>
    setEdits((prev) => ({ ...prev, [s]: t }));

  const handleGuardar = async () => {
    if (!tieneEdits) return;
    setGuardando(true);
    try {
      await guardarPropuesta(edits);
      setEdits({});
      setGuardadoOk(true);
      setTimeout(() => setGuardadoOk(false), 2000);
    } catch (err: any) {
      toastError({ mensaje: err.message || "Error al guardar" });
    } finally {
      setGuardando(false);
    }
  };

  const handleEjecutar = async () => {
    if (accion === "guardar") {
      await handleGuardar();
    } else {
      await handleGuardar();
      onGenerarEntregable();
    }
  };

  return (
    <>
      <ConfigIA
        tema={tema}
        area={area}
        onTemaChange={setTema}
        onAreaChange={setArea}
      />

      <div className="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <button
          type="button"
          className="btn btn-primary btn-sm"
          onClick={generarPropuesta}
          disabled={!tema || !area || generating}
        >
          {generating ? (
            <>
              <span className="spinner-border spinner-border-sm me-2" />
              Generando: {progreso ?? "..."}
            </>
          ) : (
            "Generar Propuesta"
          )}
        </button>

        <SplitButton
          variant="outline-success"
          title={guardando ? "Guardando..." : "Guardar cambios"}
          onClick={handleEjecutar}
          disabled={guardando || generating}
          size="sm"
        >
          <Dropdown.Item
            active={accion === "guardar"}
            onClick={() => setAccion("guardar")}
            disabled={!tieneEdits || guardando}
          >
            Guardar cambios
          </Dropdown.Item>
          <Dropdown.Item
            active={accion === "guardarYGenerar"}
            onClick={() => setAccion("guardarYGenerar")}
            disabled={guardando || generating || !tema || !area}
          >
            Guardar y generar entregable
          </Dropdown.Item>
        </SplitButton>

        {guardadoOk && <span className="text-success small">✓ Guardado</span>}
      </div>

      {genError && (
        <div className="alert alert-danger" role="alert">
          {genError}
        </div>
      )}

      {!tienePropuesta ? (
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
                      <span className="badge text-bg-warning" title="Cambios sin guardar">
                        ●
                      </span>
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
                texto={expediente.propuesta_ia?.[activa]?.texto ?? ""}
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