import ConfigIA from "../forms/ConfigIA";
import SeccionEditable from "../section/SeccionEditable";
import { useExpedienteContext } from "../context/ExpedienteContext";
import "@/../css/Secciones.css";
import { faCircleExclamation } from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";

interface Props {
  tema: string;
  area: string;
  onTemaChange: (v: string) => void;
  onAreaChange: (v: string) => void;
  generating: boolean;
  progreso: string | null;
  genError: string | null;
  onGenerar: () => void;
  secciones: [string, any][];
  titulos: Record<string, string>;
  activa: string | null;
  edits: Record<string, string>;
  tienePropuesta: boolean;
  tieneEdits: boolean;
  guardando: boolean;
  guardadoOk: boolean;
  onSeccionChange: (k: string) => void;
  onCambio: (s: string, t: string) => void;
  onEjecutar: () => void;
  textoActivo: string;
  numObjetivos: number;
  onNumObjetivosChange: (v: number) => void;

}

export default function PropuestaTabView({
  tema,
  area,
  onTemaChange,
  onAreaChange,
  generating,
  progreso,
  genError,
  onGenerar,
  secciones,
  titulos,
  activa,
  edits,
  tienePropuesta,
  tieneEdits,
  guardando,
  guardadoOk,
  onSeccionChange,
  onCambio,
  onEjecutar,
  textoActivo,

  numObjetivos,
  onNumObjetivosChange,
}: Props) {
  

  const hayAlgoQueGuardar = tieneEdits ;
  return (
    <>
      <ConfigIA
        tema={tema}
        area={area}
        onTemaChange={onTemaChange}
        onAreaChange={onAreaChange}
        numObjetivos={numObjetivos}
        onNumObjetivosChange={onNumObjetivosChange}
      />

      <div className="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <button
          type="button"
          className="btn btn-primary btn-sm"
          onClick={onGenerar}
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

        <button
          type="button"
          className="btn btn-outline-success btn-sm"
          onClick={onEjecutar}
          disabled={guardando || generating || !hayAlgoQueGuardar}
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
                  className={`nav-link ${activa === key ? "active" : ""}`}
                  onClick={() => onSeccionChange(key)}
                >
                  <span className="d-inline-flex align-items-center gap-2">
                    <span>{titulos[key] ?? key.replace(/_/g, " ")}</span>
                    {edits[key] !== undefined && (
                      <FontAwesomeIcon
                        icon={faCircleExclamation}
                        className="text-warning"
                        style={{ fontSize: 20, marginLeft: 4 }}
                      />
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
                titulo={titulos[activa] ?? activa.replace(/_/g, " ")}
                texto={textoActivo}
                generando={generating}
                onCambio={onCambio}
              />
            )}
          </div>
        </>
      )}
    </>
  );
}