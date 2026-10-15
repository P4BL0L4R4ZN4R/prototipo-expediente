import { useState } from "react";
import ConfigIA from "../forms/ConfigIA";
import SeccionEditable from "../section/SeccionEditable";
import { useExpedienteContext } from "../context/ExpedienteContext";
import { error as toastError } from "@/utils/alert";
import "@/../css/Secciones.css";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faCircleExclamation, faCheck } from "@fortawesome/free-solid-svg-icons";

const SECCIONES_ENTREGABLE = [
  { key: "introduccion",         label: "Introducción" },
  { key: "problematica",         label: "Problemática (retrospectiva)" },
  { key: "retrospectiva",         label: "Metodologia (retrospectiva)" },
  { key: "desarrollo_conceptos", label: "Desarrollo conceptos" },
  { key: "finalizacion_servicio",  label: "Finalización de servicio" }, 

] as const;

export default function EntregableTab() {
  const {
    expediente,
    tema,
    setTema,
    area,
    setArea,
    numObjetivos,
    setNumObjetivos,
    genError,
    generandoSecciones,
    erroresSecciones,
    generarSeccionEntregable,
    guardarEntregable,
  } = useExpedienteContext();

  const [edits, setEdits] = useState<Record<string, string>>({});
  const [guardando, setGuardando] = useState(false);
  const [guardadoOk, setGuardadoOk] = useState(false);
  const [seccionActiva, setSeccionActiva] = useState<string>("introduccion");

  const tienePropuesta =
    expediente.propuesta_ia &&
    Object.values(expediente.propuesta_ia).some(
      (s: any) => (s?.texto && s.texto.trim() !== "") || s?.path
    );

  const activa = seccionActiva;

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
        Genera primero la Propuesta.
      </div>
    );
  }

  const algunaGenerando = Object.values(generandoSecciones ?? {}).some(Boolean);
  const yaTieneTexto = !!expediente.entregable?.[activa]?.texto;

  return (
    <>
      <ConfigIA
        tema={tema}
        area={area}
        numObjetivos={numObjetivos}
        onTemaChange={setTema}
        onAreaChange={setArea}
        onNumObjetivosChange={setNumObjetivos}
        readonlyInputs={true}
      />

      <div className="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <button
          type="button"
          className="btn btn-primary btn-sm"
          onClick={() => generarSeccionEntregable(activa)}
          disabled={generandoSecciones?.[activa] || !tema || !area}
        >
          {generandoSecciones?.[activa] ? (
            <>
              <span className="spinner-border spinner-border-sm me-2" />
              Generando...
            </>
          ) : yaTieneTexto ? (
            "Regenerar esta sección"
          ) : (
            "Generar esta sección"
          )}
        </button>

        <button
          type="button"
          className="btn btn-outline-success btn-sm"
          onClick={handleGuardar}
          disabled={!Object.keys(edits).length || guardando || algunaGenerando}
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

      <ul className="nav nav-tabs secciones-tabs flex-nowrap text-nowrap">
        {SECCIONES_ENTREGABLE.map(({ key, label }) => {
          const generando = generandoSecciones?.[key];
          const error = erroresSecciones?.[key];
          const listo = !!expediente.entregable?.[key]?.texto;

          return (
            <li className="nav-item" key={key}>
              <button
                type="button"
                className={`nav-link text-capitalize ${activa === key ? "active" : ""}`}
                onClick={() => setSeccionActiva(key)}
              >
                <span className="d-inline-flex align-items-center gap-2">
                  <span>{label}</span>
                  {generando && (
                    <span className="spinner-border spinner-border-sm" />
                  )}
                  {error && (
                    <FontAwesomeIcon
                      icon={faCircleExclamation}
                      className="text-danger"
                    />
                  )}
                  {listo && !generando && !error && (
                    <FontAwesomeIcon icon={faCheck} className="text-success" />
                  )}
                  {edits[key] !== undefined && (
                    <FontAwesomeIcon
                      icon={faCircleExclamation}
                      className="text-warning"
                    />
                  )}
                </span>
              </button>
            </li>
          );
        })}
      </ul>

      <div className="pt-3">
        {erroresSecciones?.[activa] && (
          <div className="alert alert-danger">
            {erroresSecciones[activa]}
          </div>
        )}

        <SeccionEditable
          key={activa}
          seccion={activa}
          titulo={SECCIONES_ENTREGABLE.find(s => s.key === activa)?.label ?? activa}
          texto={expediente.entregable?.[activa]?.texto ?? ""}
          generando={generandoSecciones?.[activa] ?? false}
          onCambio={handleCambio}
        />
      </div>
    </>
  );
}