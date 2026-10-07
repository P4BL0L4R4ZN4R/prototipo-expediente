import { useEffect, useState } from "react";
import FloatingWindow from "./FloatingWindow";
import GeneralTab from "./GeneralTab";
import PropuestaTab from "./PropuestaTab";
import EntregableTab from "./EntregableTab";
import { useExpediente, useExpedienteHandles } from "@/hooks";
import { descargarZip } from "@/api/excelApi";
import "@/../css/expediente.css";

interface Props {
  idExpediente: string | null;
  open: boolean;
  onClose: () => void;
}

type Tab = "general" | "propuesta" | "entregable";

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

  // 👇 Estado de exportación (solo true/false, ya no hay progreso)
  const [exportando, setExportando] = useState(false);

  useEffect(() => {
    if (expediente?.meta) {
      setEstado(expediente.meta.estado ?? "");
      setTema(expediente.meta.tema ?? "");
      setArea(expediente.meta.area ?? "");
    }
  }, [expediente]);

  const {
    genError,
    generatingPropuesta,
    progresoPropuesta,
    generatingEntregable,
    progresoEntregable,
    handleGenerarPropuesta,
    handleGenerarEntregable,
    handleGuardarPropuesta,
    handleGuardarEntregable,
  } = useExpedienteHandles({
    idExpediente,
    tema,
    area,
    estado,
    guardarMeta,
    refrescar,
  });

  // 👇 Handler del ZIP
  const handleExportarTodo = () => {
    if (!idExpediente) return;

    setExportando(true);

    // Dispara la descarga del ZIP
    descargarZip(idExpediente);

    // El navegador empieza a descargar en background;
    // no hay callback real, así que liberamos el botón
    // después de un momento.
    setTimeout(() => setExportando(false), 1500);
  };

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
          className="form-select form-select-sm estado-select"
          value={estado}
          onChange={(e) => setEstado(e.target.value)}
          title="Estado del expediente"
        >
          <option value="">Sin estado</option>
          <option value="borrador">Borrador</option>
          <option value="en_revision">En revisión</option>
          <option value="aprobado">Aprobado</option>
          <option value="finalizado">Finalizado</option>
        </select>
      }
    >
      {loading && (
        <div className="loading-container">
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">Cargando...</span>
          </div>
          <p className="mt-3 text-muted">Cargando expediente...</p>
        </div>
      )}

      {error && (
        <div className="alert alert-danger" role="alert">
          {error}
        </div>
      )}

      {!loading && !error && expediente && (
        <>
          {/* Tabs */}
          <div className="tabs-container">
            <button
              type="button"
              className={`tab ${activeTab === "general" ? "active" : ""}`}
              onClick={() => setActiveTab("general")}
            >
              General
            </button>

            <button
              type="button"
              className={`tab ${activeTab === "propuesta" ? "active" : ""}`}
              onClick={() => setActiveTab("propuesta")}
            >
              Propuesta
            </button>

            <button
              type="button"
              className={`tab ${activeTab === "entregable" ? "active" : ""}`}
              onClick={() => setActiveTab("entregable")}
            >
              Entregable
            </button>
          </div>

          {/* Barra de acciones (fuera de los tabs) */}
          <div
            style={{
              display: "flex",
              justifyContent: "space-between",
              alignItems: "center",
              marginBottom: 12,
            }}
          >
            <button
              type="button"
              className="btn btn-sm btn-success"
              onClick={handleGenerarPropuesta}
              disabled={
                !tema ||
                !area ||
                generatingPropuesta ||
                generatingEntregable
              }
            >
              {generatingPropuesta ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2" />
                  {progresoPropuesta ?? "Generando..."}
                </>
              ) : (
                "Generar Propuesta"
              )}
            </button>

            <button
              type="button"
              className="btn btn-sm btn-outline-primary"
              onClick={handleExportarTodo}
              disabled={!idExpediente || exportando}
              title="Descarga los 7 documentos Word en un ZIP"
            >
              {exportando ? (
                <>
                  <span className="spinner-border spinner-border-sm me-2" />
                  Generando ZIP...
                </>
              ) : (
                "Exportar todo (ZIP)"
              )}
            </button>
          </div>

          {/* Contenido */}
          {activeTab === "general" && (
            <GeneralTab expediente={expediente} />
          )}

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

          {activeTab === "entregable" && (
            <EntregableTab
              expediente={expediente}
              tema={tema}
              area={area}
              generating={generatingEntregable}
              progreso={progresoEntregable}
              genError={genError}
              onTemaChange={setTema}
              onAreaChange={setArea}
              onGenerar={handleGenerarEntregable}
              onGuardarSecciones={handleGuardarEntregable}
            />
          )}
        </>
      )}
    </FloatingWindow>
  );
}