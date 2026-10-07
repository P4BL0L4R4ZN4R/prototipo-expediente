import { useState } from "react";
import { SplitButton, Dropdown } from "react-bootstrap";
import ConfigIA from "./ConfigIA";
import SeccionEditable from "./SeccionEditable";
import type { Expediente } from "../../expediente";

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
  onGenerarEntregable: () => void;
  onGuardarSecciones: (
    secciones: Record<string, string>
  ) => Promise<void>;
}

// 👇 Nuevo tipo para la acción seleccionada
type Accion = "guardar" | "guardarYGenerar";

export default function PropuestaTab({
  expediente,
  tema,
  area,
  generating,
  progreso,
  genError,
  onTemaChange,
  onAreaChange,
  onGenerar,
  onGenerarEntregable,
  onGuardarSecciones,
}: Props) {
  const [edits, setEdits] = useState<Record<string, string>>({});
  const [guardando, setGuardando] = useState(false);
  const [guardadoOk, setGuardadoOk] = useState(false);
  const [guardadoError, setGuardadoError] = useState<string | null>(null);

  // 👇 Acción seleccionada (por defecto: guardar)
  const [accion, setAccion] = useState<Accion>("guardar");

  const tienePropuesta =
    expediente.propuesta_ia &&
    Object.keys(expediente.propuesta_ia).length > 0;

  const tieneEdits = Object.keys(edits).length > 0;

  // 👇 Texto que se muestra en el botón según la acción
  const labelAccion =
    accion === "guardar"
      ? "Guardar cambios"
      : "Guardar y generar entregable";

  const handleCambio = (seccion: string, texto: string) => {
    setEdits((prev) => ({
      ...prev,
      [seccion]: texto,
    }));
  };

  // Guarda solamente los cambios
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

      throw err;
    } finally {
      setGuardando(false);
    }
  };

  // Guarda los cambios y después genera el entregable
  const handleGuardarYGenerarEntregable = async () => {
    try {
      // Si hay cambios, primero los guardamos
      if (tieneEdits) {
        await handleGuardar();
      }

      // Si guardar fue correcto, generamos el entregable
      onGenerarEntregable();
    } catch {
      // Si guardar falla, no generamos el entregable.
    }
  };

  // 👇 Ejecuta la acción que esté seleccionada actualmente
  const handleEjecutarAccion = () => {
    if (accion === "guardar") {
      handleGuardar();
    } else {
      handleGuardarYGenerarEntregable();
    }
  };

  return (
    <>
      <ConfigIA
        tema={tema}
        area={area}
        onTemaChange={onTemaChange}
        onAreaChange={onAreaChange}
      />

      <div className="d-flex align-items-center gap-2 mb-3 flex-wrap">

        {/* =====================================================
            BOTÓN PRINCIPAL: GENERAR PROPUESTA
        ====================================================== */}
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
            "Generar Propuesta"
          )}
        </button>

        {/* =====================================================
            SPLIT BUTTON: el dropdown SOLO selecciona,
            el botón principal EJECUTA
        ====================================================== */}
        <SplitButton
          variant="outline-success"
          title={guardando ? "Guardando..." : labelAccion}
          // 👇 El botón principal ejecuta la acción seleccionada
          onClick={handleEjecutarAccion}
          disabled={guardando || generating}
          size="sm"
        >
          {/* OPCIÓN 1: GUARDAR CAMBIOS */}
          <Dropdown.Item
            active={accion === "guardar"}          // 👈 marca cuál está activa
            onClick={() => setAccion("guardar")}   // 👈 solo cambia el estado
            disabled={!tieneEdits || guardando}
          >
            Guardar cambios
          </Dropdown.Item>

          {/* OPCIÓN 2: GUARDAR Y GENERAR ENTREGABLE */}
          <Dropdown.Item
            active={accion === "guardarYGenerar"}       // 👈 marca cuál está activa
            onClick={() => setAccion("guardarYGenerar")} // 👈 solo cambia el estado
            disabled={guardando || generating || !tema || !area}
          >
            Guardar y generar entregable
          </Dropdown.Item>
        </SplitButton>

        {/* MENSAJE DE GUARDADO CORRECTO */}
        {guardadoOk && (
          <span className="text-success" style={{ fontSize: 13 }}>
            ✓ Guardado
          </span>
        )}

        {/* MENSAJE DE ERROR */}
        {guardadoError && (
          <span className="text-danger" style={{ fontSize: 13 }}>
            {guardadoError}
          </span>
        )}

        {/* AVISO DE TEMA / ÁREA */}
        {(!tema || !area) && (
          <span className="text-muted" style={{ fontSize: 13 }}>
            Completa Tema y Área primero
          </span>
        )}
      </div>

      {/* ERROR DE GENERACIÓN */}
      {genError && (
        <div className="alert alert-danger" role="alert">
          {genError}
        </div>
      )}

      {/* CONTENIDO DE LA PROPUESTA */}
      {tienePropuesta ? (
        Object.entries(expediente.propuesta_ia!).map(
          ([key, val]: any) => (
            <SeccionEditable
              key={key}
              seccion={key}
              titulo={key.replace(/_/g, " ")}
              texto={val?.texto ?? ""}
              generando={generating}
              onCambio={handleCambio}
            />
          )
        )
      ) : (
        <div className="alert alert-secondary">
          <em>Sin generar aún.</em>
        </div>
      )}
    </>
  );
}