import { useEffect, useState } from "react";
import PropuestaTabView from "./PropuestaTabView";
import { useExpedienteContext } from "../context/ExpedienteContext";
import { error as toastError } from "@/utils/alert";

const ORDEN_SECCIONES = [
  "texto_propuesta",
  "introduccion",
  "problematica",
  "objetivo_general",
  "objetivos_especificos",
  "metodologia",
];

const TITULOS_SECCIONES: Record<string, string> = {
  texto_propuesta: "Texto propuesta",
  introduccion: "Introducción",
  problematica: "Problemática",
  objetivo_general: "Objetivo general",
  objetivos_especificos: "Objetivos específicos",
  metodologia: "Metodología",
};

export default function PropuestaTab() {
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
    registrarEditsPropuesta,

    numObjetivos,
    setNumObjetivos,
    hayEditsFechas,
    limpiarEditsFechas,
    persistirFechas,
  } = useExpedienteContext();

  const [edits, setEdits] = useState<Record<string, string>>({});
  const [guardando, setGuardando] = useState(false);
  const [guardadoOk, setGuardadoOk] = useState(false);
  const [seccionActiva, setSeccionActiva] = useState<string | null>(null);

  const secciones = expediente.propuesta_ia
    ? Object.entries(expediente.propuesta_ia)
        .filter(([, v]: any) => (v?.texto && v.texto.trim() !== "") || v?.path)
        .sort(([a], [b]) => {
          const ia = ORDEN_SECCIONES.indexOf(a);
          const ib = ORDEN_SECCIONES.indexOf(b);
          if (ia === -1) return 1;
          if (ib === -1) return -1;
          return ia - ib;
        })
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
    if (!tieneEdits && !hayEditsFechas) {
      toastError({ mensaje: "No hay cambios que guardar" });
      return;
    }

    setGuardando(true);
    try {
      if (tieneEdits) {
        await guardarPropuesta(edits);
        setEdits({});
      }
      if (hayEditsFechas) {
        await persistirFechas();
        limpiarEditsFechas();
      }
      setGuardadoOk(true);
      setTimeout(() => setGuardadoOk(false), 2000);
    } catch (err: any) {
      toastError({ mensaje: err.message || "Error al guardar" });
    } finally {
      setGuardando(false);
    }
  };

  useEffect(() => {
    registrarEditsPropuesta(Object.keys(edits).length > 0);
  }, [edits, registrarEditsPropuesta]);

  useEffect(() => {
    return () => {
      registrarEditsPropuesta(false);
    };
  }, [registrarEditsPropuesta]);

  const handleEjecutar = async () => {
    await handleGuardar();
  };

  return (
    <PropuestaTabView
      tema={tema}
      area={area}
      onTemaChange={setTema}
      onAreaChange={setArea}
      numObjetivos={numObjetivos}
      onNumObjetivosChange={setNumObjetivos}
      generating={generating}
      progreso={progreso}
      genError={genError}
      onGenerar={generarPropuesta}
      secciones={secciones}
      titulos={TITULOS_SECCIONES}
      activa={activa}
      edits={edits}
      tienePropuesta={tienePropuesta}
      tieneEdits={tieneEdits}
      guardando={guardando}
      guardadoOk={guardadoOk}
      onSeccionChange={setSeccionActiva}
      onCambio={handleCambio}
      onEjecutar={handleEjecutar}
      textoActivo={activa ? expediente.propuesta_ia?.[activa]?.texto ?? "" : ""}
      hayEditsFechas={hayEditsFechas}
    />
  );
}