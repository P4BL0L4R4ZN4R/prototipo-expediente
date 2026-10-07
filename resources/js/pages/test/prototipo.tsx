import { useState, useEffect } from "react";
import AdminLayout from "@/layouts/main";
import { subirExcel, listarExpedientes } from "@/api/excelApi";
import ExpedienteWindow from "@/components/expediente/ExpedienteWindow";

type ExpedienteResumen = {
  id: string;
  path?: string;
};

export default function Index() {
  const [file, setFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [expedientes, setExpedientes] = useState<ExpedienteResumen[]>([]);

  // Estado de la ventana
  const [windowOpen, setWindowOpen] = useState(false);
  const [selectedId, setSelectedId] = useState<string | null>(null);

  useEffect(() => {
    listarExpedientes()
      .then(setExpedientes)
      .catch(() => setExpedientes([]));
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!file) return;

    setLoading(true);
    setError(null);

    try {
      const { id } = await subirExcel(file);

      // refresca la lista para que aparezca el nuevo expediente
      const lista = await listarExpedientes();
      setExpedientes(lista);

      // limpia el input
      setFile(null);

      // abre la ventana con el expediente recién creado
      setSelectedId(id);
      setWindowOpen(true);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const abrirVentana = (id: string) => {
    setSelectedId(id);
    setWindowOpen(true);
  };

  const cerrarVentana = () => {
    setWindowOpen(false);
  };

  return (
    <AdminLayout>
      <div style={{ maxWidth: 960 }}>
        <h1 style={{ fontSize: 22, fontWeight: 600, marginBottom: 6 }}>
          Expedientes
        </h1>
        <p style={{ color: "#666", fontSize: 14, marginBottom: 24 }}>
          Sube un Excel para crear un expediente o consulta los existentes.
        </p>

        {/* Formulario de subida */}
        <div
          style={{
            border: "1px solid #e5e5e5",
            borderRadius: 8,
            padding: 20,
            marginBottom: 24,
          }}
        >
          <form
            onSubmit={handleSubmit}
            style={{ display: "flex", flexDirection: "column", gap: 14 }}
          >
            <div>
              <label
                style={{
                  display: "block",
                  fontSize: 13,
                  fontWeight: 500,
                  marginBottom: 6,
                }}
              >
                Archivo Excel
              </label>
              <input
                type="file"
                accept=".xlsx,.xls"
                onChange={(e) => setFile(e.target.files?.[0] ?? null)}
                style={{ fontSize: 14 }}
              />
            </div>

            <div style={{ display: "flex", gap: 12, alignItems: "center" }}>
              <button
                type="submit"
                disabled={!file || loading}
                style={{
                  background: loading || !file ? "#ccc" : "#111",
                  color: "#fff",
                  border: "none",
                  padding: "8px 18px",
                  borderRadius: 6,
                  cursor: loading || !file ? "not-allowed" : "pointer",
                  fontSize: 14,
                  fontWeight: 500,
                }}
              >
                {loading ? "Procesando..." : "Procesar"}
              </button>

              {file && (
                <span style={{ fontSize: 13, color: "#666" }}>{file.name}</span>
              )}
            </div>

            {error && (
              <p style={{ color: "#c00", fontSize: 13, margin: 0 }}>{error}</p>
            )}
          </form>
        </div>

        {/* Tabla de expedientes */}
        <div
          style={{
            border: "1px solid #e5e5e5",
            borderRadius: 8,
            overflow: "hidden",
          }}
        >
          <table
            style={{
              width: "100%",
              borderCollapse: "collapse",
              fontSize: 14,
            }}
          >
            <thead>
              <tr style={{ background: "#f9fafb" }}>
                <th style={thStyle}>ID</th>
                <th style={{ ...thStyle, textAlign: "right", width: 220 }}>
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody>
              {expedientes.length === 0 ? (
                <tr>
                  <td
                    colSpan={2}
                    style={{
                      ...tdStyle,
                      textAlign: "center",
                      color: "#888",
                      padding: 24,
                    }}
                  >
                    No hay expedientes todavía.
                  </td>
                </tr>
              ) : (
                expedientes.map((exp) => (
                  <tr key={exp.id} style={{ borderTop: "1px solid #f0f0f0" }}>
                    <td style={tdStyle}>{exp.id}</td>
                    <td style={{ ...tdStyle, textAlign: "right" }}>
                      <button
                        style={btnGhost}
                        onClick={() => abrirVentana(exp.id)}
                      >
                        Editar
                      </button>
                      <button style={btnDanger} disabled title="Pendiente">
                        Eliminar
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Ventana flotante */}
      <ExpedienteWindow
        idExpediente={selectedId}
        open={windowOpen}
        onClose={cerrarVentana}
      />
    </AdminLayout>
  );
}

// ─── Estilos inline ─────────────────────────────────────
const thStyle: React.CSSProperties = {
  textAlign: "left",
  padding: "12px 16px",
  fontSize: 12,
  fontWeight: 600,
  color: "#6b7280",
  textTransform: "uppercase",
  letterSpacing: "0.04em",
};

const tdStyle: React.CSSProperties = {
  padding: "12px 16px",
  fontSize: 14,
  color: "#111827",
};

const btnGhost: React.CSSProperties = {
  background: "transparent",
  color: "#374151",
  border: "1px solid #d1d5db",
  padding: "5px 12px",
  borderRadius: 6,
  fontSize: 13,
  cursor: "pointer",
  marginLeft: 6,
};

const btnDanger: React.CSSProperties = {
  background: "transparent",
  color: "#b91c1c",
  border: "1px solid #fecaca",
  padding: "5px 12px",
  borderRadius: 6,
  fontSize: 13,
  cursor: "not-allowed",
  marginLeft: 6,
  opacity: 0.6,
};