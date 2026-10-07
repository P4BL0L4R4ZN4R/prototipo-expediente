import { useEffect, useState } from "react";

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
        className="card-header d-flex justify-content-between align-items-center"
        role="button"
        onClick={() => setMinimizada((prev) => !prev)}
        style={{ cursor: "pointer" }}
      >
        <span className="fw-bold text-capitalize">
          {titulo}
        </span>

        <div className="d-flex align-items-center gap-2">
          {generando && (
            <span
              className="text-muted"
              style={{ fontSize: 12 }}
              onClick={(e) => e.stopPropagation()}
            >
              Bloqueado mientras se genera…
            </span>
          )}

          <span
            className="text-muted"
            style={{
              display: "inline-block",
              transition: "transform 0.25s ease",
              transform: minimizada
                ? "rotate(0deg)"
                : "rotate(180deg)",
            }}
          >
            ▼
          </span>
        </div>
      </div>

      <div
        style={{
          display: "grid",
          gridTemplateRows: minimizada ? "0fr" : "1fr",
          transition: "grid-template-rows 0.3s ease",
        }}
      >
        <div style={{ overflow: "hidden" }}>
          <div
            className="card-body"
            style={{
              opacity: minimizada ? 0 : 1,
              transition: "opacity 0.2s ease",
            }}
          >
            <textarea
              className="form-control"
              value={valor}
              onChange={handleChange}
              disabled={generando}
              rows={Math.max(4, valor.split("\n").length)}
              style={{
                fontSize: 14,
                fontFamily: "inherit",
                resize: "vertical",
              }}
            />
          </div>
        </div>
      </div>
    </div>
  );
}