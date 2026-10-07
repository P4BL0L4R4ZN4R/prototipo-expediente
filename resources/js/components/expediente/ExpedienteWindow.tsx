import { useEffect, useState } from "react";
import FloatingWindow from "./FloatingWindow";
import { useExpediente } from "./useExpediente";
import GeneralTab from "./GeneralTab";
import PropuestaTab from "./PropuestaTab";
import EntregableTab from "./EntregableTab";

interface Props {
  idExpediente: string | null;
  open: boolean;
  onClose: () => void;
}

type Tab = "general" | "propuesta" | "entregable";

const SECCIONES_PROPUESTA = [
  { key: "texto-propuesta", label: "Texto propuesta" },
  { key: "introduccion", label: "Introducción" },
  { key: "problematica", label: "Problemática" },
  { key: "objetivo-general", label: "Objetivo general" },
  { key: "objetivos-especificos", label: "Objetivos específicos" },
  { key: "metodologia", label: "Metodología" },
];

const SECCIONES_ENTREGABLE = [
  { key: "introduccion", label: "Introducción" },
  { key: "problematica", label: "Problemática" },
  { key: "desarrollo-conceptos", label: "Desarrollo conceptos" },
];

export default function ExpedienteWindow({
  idExpediente,
  open,
  onClose,
}: Props) {
  const {
    expediente,
    loading,
    error,
    refrescar,
    guardarMeta,
  } = useExpediente(idExpediente, open);

  const [activeTab, setActiveTab] = useState<Tab>("general");

  const [estado, setEstado] = useState("");
  const [tema, setTema] = useState("");
  const [area, setArea] = useState("");

  // Generación propuesta
  const [generatingPropuesta, setGeneratingPropuesta] =
    useState(false);

  const [progresoPropuesta, setProgresoPropuesta] =
    useState<string | null>(null);

  // Generación entregable
  const [generatingEntregable, setGeneratingEntregable] =
    useState(false);

  const [progresoEntregable, setProgresoEntregable] =
    useState<string | null>(null);

  const [genError, setGenError] =
    useState<string | null>(null);

  // Sincronizar tema, área y estado cuando llega el expediente
  useEffect(() => {
    if (expediente?.meta) {
      setEstado(expediente.meta.estado ?? "");
      setTema(expediente.meta.tema ?? "");
      setArea(expediente.meta.area ?? "");
    }
  }, [expediente]);

  const handleGenerarPropuesta = async () => {
    if (!idExpediente) return;

    if (!tema || !area) {
      setGenError("Completa Tema y Área primero.");
      return;
    }

    setGeneratingPropuesta(true);
    setGenError(null);
    setProgresoPropuesta(null);

    try {
      await guardarMeta({
        estado,
        tema,
        area,
      });

      for (let i = 0; i < SECCIONES_PROPUESTA.length; i++) {
        const s = SECCIONES_PROPUESTA[i];

        setProgresoPropuesta(
          `${s.label} (${i + 1}/${SECCIONES_PROPUESTA.length})`
        );

        const res = await fetch(
          `/api/prototipo/excel/propuesta/${idExpediente}/${s.key}`,
          {
            headers: {
              Accept: "application/json",
            },
          }
        );

        if (!res.ok) {
          const err = await res.json().catch(() => ({}));

          throw new Error(
            `Falló "${s.label}": ${err.error || res.status}`
          );
        }
      }

      await refrescar();
    } catch (err: any) {
      setGenError(err.message);
    } finally {
      setGeneratingPropuesta(false);
      setProgresoPropuesta(null);
    }
  };

  const handleGenerarEntregable = async () => {
    if (!idExpediente) return;

    if (!tema || !area) {
      setGenError("Completa Tema y Área primero.");
      return;
    }

    setGeneratingEntregable(true);
    setGenError(null);
    setProgresoEntregable(null);

    try {
      await guardarMeta({
        estado,
        tema,
        area,
      });

      for (let i = 0; i < SECCIONES_ENTREGABLE.length; i++) {
        const s = SECCIONES_ENTREGABLE[i];

        setProgresoEntregable(
          `${s.label} (${i + 1}/${SECCIONES_ENTREGABLE.length})`
        );

        const res = await fetch(
          `/api/prototipo/excel/entregable/${idExpediente}/${s.key}`,
          {
            headers: {
              Accept: "application/json",
            },
          }
        );

        if (!res.ok) {
          const err = await res.json().catch(() => ({}));

          throw new Error(
            `Falló "${s.label}": ${err.error || res.status}`
          );
        }
      }

      await refrescar();
    } catch (err: any) {
      setGenError(err.message);
    } finally {
      setGeneratingEntregable(false);
      setProgresoEntregable(null);
    }
  };

  // Guardar secciones editadas de la propuesta
  const handleGuardarPropuesta = async (
    secciones: Record<string, string>
  ) => {
    if (!idExpediente) return;

    const propuesta_ia: Record<string, any> = {};

    for (const [key, texto] of Object.entries(secciones)) {
      propuesta_ia[key] = {
        texto,
      };
    }

    const res = await fetch(
      `/api/prototipo/excel/${idExpediente}`,
      {
        method: "PATCH",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          propuesta_ia,
        }),
      }
    );

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));

      throw new Error(
        err.error || "Error al guardar"
      );
    }

    await refrescar();
  };

  // Guardar secciones editadas del entregable
  const handleGuardarEntregable = async (
    secciones: Record<string, string>
  ) => {
    if (!idExpediente) return;

    const entregable: Record<string, any> = {};

    for (const [key, texto] of Object.entries(secciones)) {
      entregable[key] = {
        texto,
      };
    }

    const res = await fetch(
      `/api/prototipo/excel/${idExpediente}`,
      {
        method: "PATCH",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          entregable,
        }),
      }
    );

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));

      throw new Error(
        err.error || "Error al guardar"
      );
    }

    await refrescar();
  };

  const tabStyle = (tab: Tab): React.CSSProperties => ({
    padding: "8px 16px",
    fontSize: 14,
    fontWeight: activeTab === tab ? 600 : 500,
    color:
      activeTab === tab
        ? "#111827"
        : "#6b7280",
    background: "transparent",
    border: "none",
    borderBottom:
      activeTab === tab
        ? "2px solid #2563eb"
        : "2px solid transparent",
    cursor: "pointer",
    marginBottom: -1,
  });

  return (
    <FloatingWindow
      title={`Expediente ${idExpediente ?? ""}`}
      open={open}
      onClose={onClose}
      width={950}
      height={700}
      initialX={80}
      initialY={80}
      headerRight={
        <select
          className="form-select form-select-sm"
          value={estado}
          onChange={(e) =>
            setEstado(e.target.value)
          }
          style={{
            width: 160,
            fontSize: 13,
          }}
          title="Estado del expediente"
        >
          <option value="">Sin estado</option>
          <option value="borrador">
            Borrador
          </option>
          <option value="en_revision">
            En revisión
          </option>
          <option value="aprobado">
            Aprobado
          </option>
          <option value="finalizado">
            Finalizado
          </option>
        </select>
      }
    >
      {loading && (
        <div className="text-center py-5">
          <div
            className="spinner-border text-primary"
            role="status"
          >
            <span className="visually-hidden">
              Cargando...
            </span>
          </div>

          <p className="mt-3 text-muted">
            Cargando expediente...
          </p>
        </div>
      )}

      {error && (
        <div
          className="alert alert-danger"
          role="alert"
        >
          {error}
        </div>
      )}

      {!loading &&
        !error &&
        expediente && (
          <>
            {/* Tabs */}
            <div
              style={{
                display: "flex",
                gap: 4,
                borderBottom:
                  "1px solid #e5e7eb",
                marginBottom: 20,
              }}
            >
              <button
                type="button"
                style={tabStyle("general")}
                onClick={() =>
                  setActiveTab("general")
                }
              >
                General
              </button>

              <button
                type="button"
                style={tabStyle("propuesta")}
                onClick={() =>
                  setActiveTab("propuesta")
                }
              >
                Propuesta
              </button>

              <button
                type="button"
                style={tabStyle("entregable")}
                onClick={() =>
                  setActiveTab("entregable")
                }
              >
                Entregable
              </button>
            </div>

            {/* General */}
            {activeTab === "general" && (
              <GeneralTab
                expediente={expediente}
              />
            )}

            {/* Propuesta */}
            {activeTab === "propuesta" && (
              <PropuestaTab
                  expediente={expediente}
                  tema={tema}
                  area={area}
                  generating={generatingPropuesta}
                  progreso={progresoPropuesta}
                  genError={genError}
                  onTemaChange={setTema}
                  onAreaChange={setArea}
                  onGenerar={handleGenerarPropuesta}
                  onGenerarEntregable={async () => {
                    await handleGenerarEntregable();
                    setActiveTab("entregable");
                  }}
                  onGuardarSecciones={handleGuardarPropuesta}
                />
            )}

            {/* Entregable */}
            {activeTab === "entregable" && (
              <EntregableTab
                expediente={expediente}
                tema={tema}
                area={area}
                generating={
                  generatingEntregable
                }
                progreso={
                  progresoEntregable
                }
                genError={genError}
                onTemaChange={setTema}
                onAreaChange={setArea}
                onGenerar={
                  handleGenerarEntregable
                }
                onGuardarSecciones={
                  handleGuardarEntregable
                }
              />
            )}
          </>
        )}
    </FloatingWindow>
  );
}