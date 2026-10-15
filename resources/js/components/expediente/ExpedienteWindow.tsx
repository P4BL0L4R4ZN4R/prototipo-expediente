import { useState } from "react";
import FloatingWindow from "./FloatingWindow";
import GeneralTab from "./tabs/GeneralTab";
import PropuestaTab from "./tabs/PropuestaTab";
import EntregableTab from "./tabs/EntregableTab";
import ExpedienteHeader from "./ExpedienteHeader";
import { ExpedienteProvider } from "./context/ExpedienteContext";
import { useExpedienteUI } from "../../hooks/useExpedienteUI";
import { confirmar, error as toastError } from "@/utils/alert";
import "@/../css/expediente.css";
import TooltipUi from "@/components/ui/TooltipUi";
import "@/../css/tooltip.css";

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

  const [hayEditsPropuesta, setHayEditsPropuesta] = useState(false);
  const [hayEditsEntregable, setHayEditsEntregable] = useState(false);

  const [hayEditsFechas, setHayEditsFechas] = useState(false);

  const marcarEditsFechas = () => setHayEditsFechas(true);
  const limpiarEditsFechas = () => setHayEditsFechas(false);
  

  const handleSetFechaPropuesta = (d: Date | null) => {
    ui.setFechaPropuesta(d);
    marcarEditsFechas();
  };
  const handleSetFechaEntregable = (d: Date | null) => {
    ui.setFechaEntregable(d);
    marcarEditsFechas();
  };

  const hayCambiosSinGuardar =
    ui.hayCambiosSinGuardar ||
    hayEditsPropuesta ||
    hayEditsEntregable ||
    hayEditsFechas;

  const handleCerrar = async () => {
    if (hayCambiosSinGuardar) {
      const result = await confirmar({
        titulo: "¿Cerrar sin guardar?",
        texto:
          "Tienes cambios sin guardar. Las fechas sí se guardarán; los textos editados se perderán.",
        textoConfirmar: "Sí, cerrar sin guardar",
        textoCancelar: "Cancelar",
      });
      if (!result.isConfirmed) return;

      if (hayEditsFechas) {
        try {
          await ui.persistirFechas();
          limpiarEditsFechas();
        } catch (err: any) {
          toastError({
            mensaje: err.message || "No se pudieron guardar las fechas",
          });
          return;
        }
      }
    }
    onClose();
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

            // Entregable: estado granular por sección
            generandoSecciones: ui.generandoSecciones,
            erroresSecciones: ui.erroresSecciones,
            generarSeccionEntregable: ui.generarSeccionEntregable,

            generarPropuesta: ui.handleGenerarPropuesta, 
            generarEntregable: ui.handleGenerarEntregable,
            guardarPropuesta: ui.handleGuardarPropuesta,
            guardarEntregable: ui.handleGuardarEntregable,

            numObjetivos: ui.numObjetivos,
            setNumObjetivos: ui.setNumObjetivos,

            // fechas (con wrappers que marcan edit)
            fechaPropuesta: ui.fechaPropuesta,
            setFechaPropuesta: handleSetFechaPropuesta,
            fechaEntregable: ui.fechaEntregable,
            setFechaEntregable: handleSetFechaEntregable,
            persistirFechas: ui.persistirFechas,

            // flags
            hayCambiosSinGuardar,
            registrarEditsPropuesta: setHayEditsPropuesta,
            registrarEditsEntregable: setHayEditsEntregable,
            hayEditsPropuesta,
            hayEditsEntregable,

            hayEditsFechas,
            marcarEditsFechas,
            limpiarEditsFechas,
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
              {activeTab === "propuesta" && <PropuestaTab />}
              {activeTab === "entregable" && <EntregableTab />}
            </div>
          </div>
        </ExpedienteProvider>
      )}
    </FloatingWindow>
  );
}