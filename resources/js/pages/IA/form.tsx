import type { ResultadoIA } from "./prompt";

type Props = {
  input: string;
  onInputChange: (v: string) => void;
  resultados: ResultadoIA[];
  cargando: boolean;
  error: string | null;
  onProbar: () => void;
  onLimpiar: () => void;
  iteraciones: number;
};

export default function TestConsistenciaIAView({
  input,
  onInputChange,
  resultados,
  cargando,
  error,
  onProbar,
  onLimpiar,
  iteraciones,
}: Props) {
  const botonDeshabilitado = cargando || !input.trim();

  return (
    <>
      {/* Encabezado */}
      <div style={{ marginBottom: 24 }}>
        <h1 style={{ fontSize: 24, fontWeight: 600, margin: 0 }}>
          Test Gemini
        </h1>
        <p style={{ color: "#6b7280", fontSize: 14, margin: "4px 0 0" }}>
          Pruebas restantes {iteraciones} 
        </p>
      </div>

      {/* Input */}
      <div style={{ marginBottom: 16 }}>
        <label style={labelStyle}>Prompt</label>
        <textarea
          placeholder="texto"
          value={input}
          onChange={(e) => onInputChange(e.target.value)}
          rows={4}
          disabled={cargando}
          style={textareaStyle}
        />
      </div>

      {/* Botones */}
      <div style={{ display: "flex", gap: 8, marginBottom: 24 }}>
        <button
          onClick={onProbar}
          disabled={botonDeshabilitado}
          style={{
            ...btnPrimary,
            opacity: botonDeshabilitado ? 0.6 : 1,
            cursor: botonDeshabilitado ? "not-allowed" : "pointer",
          }}
        >
          {cargando
            ? `Espere (${resultados.length}/${iteraciones})...`
            : 'Probar'}
        </button>

        <button
          onClick={onLimpiar}
          disabled={cargando}
          style={{
            ...btnGhost,
            opacity: cargando ? 0.6 : 1,
            cursor: cargando ? "not-allowed" : "pointer",
          }}
        >
          Limpiar
        </button>
      </div>

      {/* Error */}
      {error && <div style={errorStyle}>Error: {error}</div>}

      {/* Resultados */}
      {resultados.length > 0 && (
        <div style={{ display: "grid", gap: 12 }}>
          {resultados.map((r) => (
            <TarjetaResultado key={r.iteracion} resultado={r} />
          ))}
        </div>
      )}
    </>
  );
}

// ─── Subcomponente: tarjeta de resultado ───────────────
function TarjetaResultado({ resultado }: { resultado: ResultadoIA }) {
  return (
    <div style={cardStyle}>
      <div style={cardHeaderStyle}>
        <strong style={{ color: "#111827" }}>
          Iteración {resultado.iteracion}
        </strong>
        <span>{resultado.tiempo} ms</span>
      </div>
      <p style={textoStyle}>{resultado.texto}</p>
    </div>
  );
}

// ─── Estilos ───────────────────────────────────────────
const labelStyle: React.CSSProperties = {
  display: "block",
  fontSize: 13,
  fontWeight: 500,
  color: "#374151",
  marginBottom: 6,
};

const textareaStyle: React.CSSProperties = {
  width: "100%",
  padding: 12,
  borderRadius: 6,
  border: "1px solid #d1d5db",
  fontSize: 14,
  fontFamily: "inherit",
  resize: "vertical",
  outline: "none",
};

const btnPrimary: React.CSSProperties = {
  background: "#4f8cff",
  color: "#ffffff",
  border: "none",
  padding: "9px 16px",
  borderRadius: 6,
  fontSize: 14,
  fontWeight: 500,
};

const btnGhost: React.CSSProperties = {
  background: "transparent",
  color: "#374151",
  border: "1px solid #d1d5db",
  padding: "9px 16px",
  borderRadius: 6,
  fontSize: 14,
};

const errorStyle: React.CSSProperties = {
  background: "#fef2f2",
  color: "#b91c1c",
  padding: 12,
  borderRadius: 6,
  marginBottom: 16,
  fontSize: 14,
};

const cardStyle: React.CSSProperties = {
  border: "1px solid #e5e7eb",
  borderRadius: 8,
  padding: 16,
  background: "#fafafa",
};

const cardHeaderStyle: React.CSSProperties = {
  display: "flex",
  justifyContent: "space-between",
  alignItems: "center",
  marginBottom: 8,
  fontSize: 13,
  color: "#6b7280",
};

const textoStyle: React.CSSProperties = {
  margin: 0,
  whiteSpace: "pre-wrap",
  fontSize: 14,
  lineHeight: 1.6,
  color: "#111827",
};