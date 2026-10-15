import { useEffect, useState } from "react";
import "@/../css/SeccionEditable.css";

interface Props {
  seccion: string;
  titulo: string;
  texto: string;
  generando: boolean;
  onCambio: (seccion: string, texto: string) => void;
}

export default function SeccionEditable({
  seccion,
  titulo,
  texto,
  generando,
  onCambio,
}: Props) {
  const [valor, setValor] = useState(texto);
  const [minimizada, setMinimizada] = useState(false);

  useEffect(() => {
    setValor(texto);
  }, [texto]);

  const handleChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    const nuevo = e.target.value;
    setValor(nuevo);
    onCambio(seccion, nuevo);
  };

  return (
    <div className="card mb-3 shadow-sm">
      <div
        className="card-header d-flex justify-content-between align-items-center seccion-header"
        role="button"
        onClick={() => setMinimizada((prev) => !prev)}
      >
        <span className="fw-bold text-capitalize">{titulo}</span>

        <div className="d-flex align-items-center gap-2">
          {generando && (
            <span
              className="text-muted small seccion-bloqueado"
              onClick={(e) => e.stopPropagation()}
            >
              Bloqueado mientras se genera…
            </span>
          )}

          <span
            className={`text-muted seccion-chevron ${
              minimizada ? "seccion-chevron-cerrado" : ""
            }`}
          >
            ▼
          </span>
        </div>
      </div>

      <div className={`seccion-collapse ${minimizada ? "seccion-collapse-cerrado" : ""}`}>
        <div className="seccion-collapse-inner">
          <div
            className={`card-body seccion-body ${
              minimizada ? "seccion-body-oculto" : ""
            }`}
          >
            <textarea
              className="form-control seccion-textarea"
              value={valor}
              onChange={handleChange}
              disabled={generando}
              rows={Math.max(4, valor.split("\n").length)}
            />
          </div>
        </div>
      </div>
    </div>
  );
}