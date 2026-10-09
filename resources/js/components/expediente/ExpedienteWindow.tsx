import { useState } from "react";
import FloatingWindow from "./FloatingWindow";
import GeneralTab from "./GeneralTab";
import PropuestaTab from "./PropuestaTab";
import EntregableTab from "./EntregableTab";
import ExpedienteHeader from "./ExpedienteHeader";
import { ExpedienteProvider } from "./ExpedienteContext";
import { useExpedienteUI } from "../../hooks/useExpedienteUI";
import { confirmar } from "@/utils/alert";
import "@/../css/expediente.css";

interface Props {
  idExpediente: string | null;
  open: boolean;
  onClose: () => void;
  restoreKey?: number;
}

type Tab = "general" | "propuesta" | "entregable";

export default function ExpedienteWindow({
  idExpediente,
  open,
  onClose,
  restoreKey,
}: Props) {
  const ui = useExpedienteUI(idExpediente, open);
  const [activeTab, setActiveTab] = useState<Tab>("general");

  const handleCerrar = async () => {
    const result = await confirmar({
      titulo: "¿Cerrar ventana?",
      texto: "Si hay cambios sin guardar, se perderán.",
      textoConfirmar: "Sí, cerrar",
    });
    if (result.isConfirmed) onClose();
  };

  return (
    <FloatingWindow
      title={`Expediente ${idExpediente ?? ""}`}
      open={open}
      onClose={handleCerrar}
      restoreKey={restoreKey}
      width={950}
      height={700}
      initialX={80}
      initialY={80}
    >
      {ui.loading && (
        <div className="text-center py-5">
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">Cargando...</span>
          </div>
          <p className="mt-3 text-muted mb-0">Cargando expediente...</p>
        </div>
      )}

      {ui.error && (
        <div className="alert alert-danger" role="alert">
          {ui.error}
        </div>
      )}

      {!ui.loading && !ui.error && ui.expediente && (
        <ExpedienteProvider
          value={{
            expediente: ui.expediente,
            estado: ui.estado,
            setEstado: ui.setEstado,
            guardarEstado: ui.guardarEstado,
            guardandoEstado: ui.guardandoEstado,
            tema: ui.tema,
            setTema: ui.setTema,
            area: ui.area,
            setArea: ui.setArea,
            genError: ui.genError,
            generatingPropuesta: ui.generatingPropuesta,
            progresoPropuesta: ui.progresoPropuesta,
            generatingEntregable: ui.generatingEntregable,
            progresoEntregable: ui.progresoEntregable,
            generarPropuesta: ui.handleGenerarPropuesta,
            generarEntregable: ui.handleGenerarEntregable,
            guardarPropuesta: ui.handleGuardarPropuesta,
            guardarEntregable: ui.handleGuardarEntregable,
          }}
        >
          <div className="border rounded-3 overflow-hidden">
            <ExpedienteHeader
              activeTab={activeTab}
              onTabChange={setActiveTab}
              onExport={ui.exportarZip}
              exportando={ui.exportando}
              disabledExport={!idExpediente}
            />

            <div className="p-3 bg-white">
              {activeTab === "general" && <GeneralTab />}
              {activeTab === "propuesta" && (
                <PropuestaTab
                  onGenerarEntregable={async () => {
                    await ui.handleGenerarEntregable();
                    setActiveTab("entregable");
                  }}
                />
              )}
              {activeTab === "entregable" && <EntregableTab />}
            </div>
          </div>
        </ExpedienteProvider>
      )}
    </FloatingWindow>
  );
}