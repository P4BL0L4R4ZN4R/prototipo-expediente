import { useState } from "react";
import FloatingWindow from "./FloatingWindow";
import GeneralTab from "./tabs/GeneralTab";
import PropuestaTab from "./tabs/PropuestaTab";
import EntregableTab from "./tabs/EntregableTab";
import ExpedienteHeader from "./ExpedienteHeader";
import { ExpedienteProvider } from "./context/ExpedienteContext";
import { useExpedienteUI } from "../../hooks/expediente/useExpedienteUI";
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

  



  const hayCambiosSinGuardar =
    ui.hayCambiosSinGuardar ||
    hayEditsPropuesta ||
    hayEditsEntregable ;


  const handleCerrar = async () => {
    if (hayCambiosSinGuardar) {
      const result = await confirmar({
        titulo: "¿Cerrar sin guardar?",
        texto:
          "Tienes cambios sin guardar, los textos editados se perderán.",
        textoConfirmar: "Sí, cerrar sin guardar",
        textoCancelar: "Cancelar",
      });
      if (!result.isConfirmed) return;

    
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
          // ─── Expediente ─────────────────────────────────────
          expediente: ui.expediente,

          // ─── Datos generales ────────────────────────────────
          estado: ui.estado,
          setEstado: ui.setEstado,
          guardarEstado: ui.guardarEstado,
          guardandoEstado: ui.guardandoEstado,

          direccion: ui.direccion,
          setDireccion: ui.setDireccion,
          guardarDireccion: ui.guardarDireccion,
          guardandoDireccion: ui.guardandoDireccion,

          tema: ui.tema,
          setTema: ui.setTema,
          area: ui.area,
          setArea: ui.setArea,

          // ─── Generación propuesta ───────────────────────────
          genError: ui.genError,
          generatingPropuesta: ui.generatingPropuesta,
          progresoPropuesta: ui.progresoPropuesta,
          generarPropuesta: ui.handleGenerarPropuesta,



          // ─── Generación entregable ──────────────────────────
          generandoSecciones: ui.generandoSecciones,
          erroresSecciones: ui.erroresSecciones,
          generarEntregable: ui.handleGenerarEntregable,
          generarSeccionEntregable: ui.generarSeccionEntregable,

          // ─── Persistencia secciones ─────────────────────────

          guardarPropuesta: ui.handleGuardarPropuesta,
          guardarEntregable: ui.handleGuardarEntregable,

          // ─── Flags de cambios ───────────────────────────────
          hayCambiosSinGuardar,
          hayEditsPropuesta,
          hayEditsEntregable,
          registrarEditsPropuesta: setHayEditsPropuesta,
          registrarEditsEntregable: setHayEditsEntregable,

          // ─── Configuración ──────────────────────────────────
          numObjetivos: ui.numObjetivos,
          setNumObjetivos: ui.setNumObjetivos,
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