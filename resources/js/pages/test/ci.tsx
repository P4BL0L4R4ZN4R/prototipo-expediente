import { useMemo, Fragment } from "react";
import { useConceptos, type Concepto } from "@/hooks/use-carta-conceptos";

// ============================================================
// TIPOS LOCALES
// ============================================================
interface GrupoConcepto {
  concepto: string;
  items: Concepto[];
}

// ============================================================
// COMPONENTE
// ============================================================
interface Props {
  idExpediente: string;
}

export default function TablaConceptos({ idExpediente }: Props) {
  const { datos, total, totalTextual, loading, error } =
    useConceptos(idExpediente);

  const agrupado = useMemo<GrupoConcepto[]>(() => {
    const map = new Map<string, GrupoConcepto>();
    for (const item of datos) {
      if (!map.has(item.concepto)) {
        map.set(item.concepto, { concepto: item.concepto, items: [] });
      }
      map.get(item.concepto)!.items.push(item);
    }
    return Array.from(map.values());
  }, [datos]);

  if (loading) return <p>Cargando datos...</p>;

  if (error)
    return (
      <div>
        <p style={{ color: "red" }}>Error: {error}</p>
      </div>
    );

  return (
    <div style={{ padding: 20, fontFamily: "sans-serif" }}>

      <div style={{ marginBottom: 16 }}>
        <button
          onClick={() =>
            window.open(`/api/prototipo/excel/${idExpediente}/word`, "_blank")
          }
          style={{
            padding: "8px 16px",
            background: "#2563eb",
            color: "#fff",
            border: "none",
            borderRadius: 6,
            cursor: "pointer",
          }}
        >
          📄 Exportar a Word
        </button>
      </div>
            
      
      <table
        border={1}
        cellPadding={8}
        style={{ borderCollapse: "collapse", width: "100%" }}
      >
        <thead style={{ background: "#f0f0f0" }}>
          <tr>
            <th style={{ textAlign: "left", width: "80%",  padding: "12px 20px", }}>Concepto</th>
            <th style={{ textAlign: "right", width: "20%",  padding: "12px 20px", }}>Monto</th>
          </tr>
        </thead>

        <tbody>
          {agrupado.map((grupo) => (
            <Fragment key={grupo.concepto}>
              {grupo.items.map((item, idx) => (
                <tr key={item.id}>
                  {idx === 0 && (
                    <td
                      rowSpan={grupo.items.length}
                      style={{
                        verticalAlign: "top",
                        fontWeight: "bold",
                        background: "#fafafa",
                        padding: "12px 20px",
                      }}
                    >
                      {grupo.concepto}
                    </td>
                  )}

                  <td
                    style={{
                      textAlign: "right",
                      fontVariantNumeric: "tabular-nums",
                      padding: "12px 20px",
                    }}
                  >
                    ${item.monto.toLocaleString()}
                  </td>
                </tr>
              ))}
            </Fragment>
          ))}
        </tbody>

        <tfoot>
          <tr style={{ background: "#333", color: "#fff", fontWeight: "bold" }}>
            <td style={{ textAlign: "center",  padding: "12px 20px", }}>Total</td>
            <td style={{ textAlign: "center",  padding: "12px 20px", }}>${total.toLocaleString()}</td>
          </tr>

          <tr style={{ background: "#333", color: "#fff", fontStyle: "italic" }}>
            <td colSpan={2} style={{ textAlign: "center", padding: "12px 20px", }}>
              {totalTextual}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  );
}