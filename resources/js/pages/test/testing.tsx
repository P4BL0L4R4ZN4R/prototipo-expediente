import AdminLayout from "@/layouts/main";
import { useEffect, useState } from "react";

type Producto = {
  id: number;
  nombre: string;
  precio: number;
  fecha: string;
};

type Paginacion = {
  data: Producto[];
  current_page: number;
  last_page: number;
  total: number;
};

export default function Index() {
  const [productos, setProductos] = useState<Paginacion | null>(null);
  const [cargando, setCargando] = useState(true);
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);

  const [editando, setEditando] = useState<Producto | null>(null);
  const [form, setForm] = useState({ nombre: "", precio: 0, fecha: "" });
  const [guardando, setGuardando] = useState(false);
  const [modalAbierto, setModalAbierto] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const cargar = () => {
    setCargando(true);
    const params = new URLSearchParams();
    if (search) params.set("search", search);
    params.set("page", String(page));

    fetch(`/new/data?${params.toString()}`)
      .then((r) => r.json())
      .then((data) => {
        setProductos(data);
        setCargando(false);
      });
  };

  useEffect(() => {
    cargar();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [search, page]);

  const abrirCrear = () => {
    setEditando(null);
    setForm({ nombre: "", precio: 0, fecha: "" });
    setError(null);
    setModalAbierto(true);
  };

  const abrirEditar = (p: Producto) => {
    setEditando(p);
    setForm({ nombre: p.nombre, precio: p.precio, fecha: p.fecha });
    setError(null);
    setModalAbierto(true);
  };

  const cerrarModal = () => {
    setModalAbierto(false);
    setEditando(null);
    setForm({ nombre: "", precio: 0, fecha: "" });
    setError(null);
  };

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    setGuardando(true);
    setError(null);

    const url = editando ? `/new/${editando.id}` : "/new";
    const method = editando ? "PUT" : "POST";

    fetch(url, {
      method,
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN":
          (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || "",
      },
      body: JSON.stringify(form),
    })
      .then(async (r) => {
        if (!r.ok) {
          const data = await r.json().catch(() => ({}));
          throw new Error(data.message || "Error al guardar");
        }
        return r.json();
      })
      .then(() => {
        cerrarModal();
        cargar();
      })
      .catch((err) => setError(err.message))
      .finally(() => setGuardando(false));
  };

  const eliminar = (id: number) => {
    if (!confirm("¿Eliminar este producto?")) return;

    fetch(`/new/${id}`, {
      method: "DELETE",
      headers: {
        Accept: "application/json",
        "X-CSRF-TOKEN":
          (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || "",
      },
    }).then(() => cargar());
  };

  return (
    <AdminLayout>
      {/* Encabezado */}
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 24 }}>
        <div>
          <h1 style={{ fontSize: 24, fontWeight: 600, margin: 0 }}>Generacion de documentacion</h1>
          <p style={{ color: "#6b7280", fontSize: 14, margin: "4px 0 0" }}>
            Gestiona tus documentos
          </p>
        </div>
        <button onClick={abrirCrear} style={btnPrimary}>
          <i className="fa-solid fa-plus" style={{ margin: 0, fontSize: 25  }}></i>
          
        </button>
      </div>

      {/* Barra de búsqueda */}
      <div style={{ marginBottom: 16 }}>
        <input
          type="text"
          placeholder="🔍 Buscar por nombre..."
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1);
          }}
          style={inputStyle}
        />
      </div>

      {/* Tabla */}
      <div style={cardStyle}>
        <table style={{ width: "100%", borderCollapse: "collapse" }}>
          <thead>
            <tr style={{ background: "#f9fafb" }}>
              <th style={thStyle}>Nombre</th>
              <th style={thStyle}>Precio</th>
              <th style={thStyle}>Fecha</th>
              <th style={{ ...thStyle, textAlign: "right" }}>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {cargando ? (
              <tr>
                <td colSpan={5} style={{ ...tdStyle, textAlign: "center", padding: 32, color: "#6b7280" }}>
                  Cargando...
                </td>
              </tr>
            ) : !productos || productos.data.length === 0 ? (
              <tr>
                <td colSpan={5} style={{ ...tdStyle, textAlign: "center", padding: 32, color: "#6b7280" }}>
                  No hay datos aun
                </td>
              </tr>
            ) : (
              productos.data.map((p) => (
                <tr key={p.id} style={{ borderTop: "1px solid #e5e7eb" }}>
                  <td style={{ ...tdStyle, fontWeight: 500 }}>{p.nombre}</td>
                  <td style={tdStyle}>${Number(p.precio).toFixed(2)}</td>
                  <td style={tdStyle}>{p.fecha}</td>
                  <td style={{ ...tdStyle, textAlign: "right" }}>
                    <button onClick={() => abrirEditar(p)} style={btnGhost}>
                      Editar
                    </button>
                    <button onClick={() => eliminar(p.id)} style={btnDanger}>
                      Eliminar
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>

        {/* Paginación */}
        {productos && productos.last_page > 1 && (
          <div
            style={{
              display: "flex",
              justifyContent: "space-between",
              alignItems: "center",
              padding: "12px 16px",
              borderTop: "1px solid #e5e7eb",
            }}
          >
            <span style={{ fontSize: 13, color: "#6b7280" }}>
              Página {productos.current_page} de {productos.last_page} · {productos.total} registros
            </span>
            <div style={{ display: "flex", gap: 8 }}>
              <button
                disabled={productos.current_page <= 1}
                onClick={() => setPage(productos.current_page - 1)}
                style={productos.current_page <= 1 ? btnDisabled : btnGhost}
              >
                Anterior
              </button>
              <button
                disabled={productos.current_page >= productos.last_page}
                onClick={() => setPage(productos.current_page + 1)}
                style={productos.current_page >= productos.last_page ? btnDisabled : btnGhost}
              >
                Siguiente
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Modal Crear / Editar */}
      {modalAbierto && (
        <div style={overlayStyle} onClick={cerrarModal}>
          <div style={modalStyle} onClick={(e) => e.stopPropagation()}>
            <h2 style={{ margin: "0 0 16px", fontSize: 18, fontWeight: 600 }}>
              {editando ? "Editar producto" : "Nuevo producto"}
            </h2>

            <form onSubmit={submit}>
              <label style={labelStyle}>Nombre</label>
              <input
                type="text"
                value={form.nombre}
                onChange={(e) => setForm({ ...form, nombre: e.target.value })}
                required
                style={{ ...inputStyle, width: "100%", marginBottom: 12 }}
              />

              <label style={labelStyle}>Precio</label>
              <input
                type="number"
                step="0.01"
                value={form.precio}
                onChange={(e) => setForm({ ...form, precio: Number(e.target.value) })}
                required
                style={{ ...inputStyle, width: "100%", marginBottom: 12 }}
              />

              <label style={labelStyle}>Fecha</label>
              <input
                type="date"
                value={form.fecha}
                onChange={(e) => setForm({ ...form, fecha: e.target.value })}
                required
                style={{ ...inputStyle, width: "100%", marginBottom: 16 }}
              />

              {error && (
                <div style={{ background: "#fef2f2", color: "#b91c1c", padding: 10, borderRadius: 6, fontSize: 13, marginBottom: 12 }}>
                  {error}
                </div>
              )}

              <div style={{ display: "flex", justifyContent: "flex-end", gap: 8 }}>
                <button type="button" onClick={cerrarModal} style={btnGhost}>
                  Cancelar
                </button>
                <button type="submit" disabled={guardando} style={btnPrimary}>
                  {guardando ? "Guardando..." : editando ? "Actualizar" : "Crear"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </AdminLayout>
  );
}

// ─── Estilos ─────────────────────────────────────────────
const cardStyle: React.CSSProperties = {
  background: "#ffffff",
  border: "1px solid #e5e7eb",
  borderRadius: 10,
  overflow: "hidden",
};

const thStyle: React.CSSProperties = {
  textAlign: "left",
  padding: "12px 16px",
  fontSize: 13,
  fontWeight: 600,
  color: "#6b7280",
};

const tdStyle: React.CSSProperties = {
  padding: "12px 16px",
  fontSize: 14,
  color: "#111827",
};

const inputStyle: React.CSSProperties = {
  padding: "9px 12px",
  border: "1px solid #d1d5db",
  borderRadius: 6,
  fontSize: 14,
  outline: "none",
  minWidth: 260,
};

const labelStyle: React.CSSProperties = {
  display: "block",
  fontSize: 13,
  fontWeight: 500,
  color: "#374151",
  marginBottom: 4,
};

const btnPrimary: React.CSSProperties = {
  background: "#4f8cff",
  color: "#ffffff",
  border: "none",
  padding: "9px 16px",
  borderRadius: 6,
  fontSize: 14,
  fontWeight: 500,
  cursor: "pointer",
};

const btnGhost: React.CSSProperties = {
  background: "transparent",
  color: "#374151",
  border: "1px solid #d1d5db",
  padding: "6px 12px",
  borderRadius: 6,
  fontSize: 13,
  cursor: "pointer",
  marginLeft: 6,
};

const btnDanger: React.CSSProperties = {
  background: "transparent",
  color: "#b91c1c",
  border: "1px solid #fecaca",
  padding: "6px 12px",
  borderRadius: 6,
  fontSize: 13,
  cursor: "pointer",
  marginLeft: 6,
};

const btnDisabled: React.CSSProperties = {
  ...btnGhost,
  opacity: 0.5,
  cursor: "not-allowed",
};

const overlayStyle: React.CSSProperties = {
  position: "fixed",
  inset: 0,
  background: "rgba(0,0,0,0.4)",
  display: "grid",
  placeItems: "center",
  zIndex: 1000,
};

const modalStyle: React.CSSProperties = {
  background: "#ffffff",
  borderRadius: 10,
  padding: 24,
  width: "100%",
  maxWidth: 420,
  boxShadow: "0 20px 60px rgba(0,0,0,0.3)",
};

//fin de doc