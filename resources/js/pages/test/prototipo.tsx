import { useState, useEffect, useRef } from "react";
import AdminLayout from "@/layouts/main";
import { subirExcel, listarExpedientes, eliminarExpediente } from "@/api/excelApi";
import ExpedienteWindow from "@/components/expediente/ExpedienteWindow";
import "../../../css/prototipo.css";
import { confirmar, exito, error as toastError } from "@/utils/alert";


type ExpedienteResumen = {
  id: string;
  path?: string;
};

type VentanaAbierta = {
  id: string;
  restoreKey: number;
};




export default function Index() {
  const [file, setFile] = useState<File | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [expedientes, setExpedientes] = useState<ExpedienteResumen[]>([]);
  const [dragging, setDragging] = useState(false);

  const [ventanas, setVentanas] = useState<VentanaAbierta[]>([]);

  const inputRef = useRef<HTMLInputElement>(null);

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
      const lista = await listarExpedientes();
      setExpedientes(lista);
      setFile(null);
      abrirVentana(id);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setDragging(false);
    const dropped = e.dataTransfer.files?.[0];
    if (dropped) setFile(dropped);
  };

  const abrirVentana = (id: string) => {
    setVentanas((prev) => {
      const yaExiste = prev.some((v) => v.id === id);
      if (yaExiste) {
        return prev.map((v) =>
          v.id === id ? { ...v, restoreKey: v.restoreKey + 1 } : v
        );
      }
      return [...prev, { id, restoreKey: 0 }];
    });
  };




  const cerrarVentana = (id: string) => {
    setVentanas((prev) => prev.filter((v) => v.id !== id));
  };


  const handleEliminar = async (id: string) => {
    const result = await confirmar({
      titulo: "¿Eliminar expediente?",
      texto: `Se borrará ${id} y todos sus archivos. Esta acción no se puede deshacer.`,
      textoConfirmar: "Sí, eliminar",
      colorConfirmar: "#d33",
    });
  
    if (!result.isConfirmed) return;
  
    try {
      await eliminarExpediente(id);
      setExpedientes((prev) => prev.filter((e) => e.id !== id));
      setVentanas((prev) => prev.filter((v) => v.id !== id));
      exito({ mensaje: "Expediente eliminado" });
    } catch (err: any) {
      toastError({ mensaje: err.message || "No se pudo eliminar" });
    }
  };
  
  

  return (
    <AdminLayout>
      <div style={{ maxWidth: 960 }}>
        <h1 className="fs-4 fw-semibold mb-1">Expedientes</h1>
        <p className="text-secondary small mb-4">
          Sube un Excel para crear un expediente o consulta los existentes.
        </p>

        {/* Formulario de subida */}
        <form onSubmit={handleSubmit} className="mb-4">
          <div
            onDragOver={(e) => {
              e.preventDefault();
              setDragging(true);
            }}
            onDragLeave={() => setDragging(false)}
            onDrop={handleDrop}
            onClick={() => inputRef.current?.click()}
            className={`dropzone ${dragging ? "dropzone-active" : ""}`}
          >
            <input
              ref={inputRef}
              type="file"
              accept=".xlsx,.xls"
              onChange={(e) => setFile(e.target.files?.[0] ?? null)}
              className="d-none"
            />

            <div className={`icon-circle ${file ? "icon-circle-filled" : ""}`}>
              {file ? (
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                  <polyline points="20 6 9 17 4 12" />
                </svg>
              ) : (
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="17 8 12 3 7 8" />
                  <line x1="12" y1="3" x2="12" y2="15" />
                </svg>
              )}
            </div>

            {file ? (
              <>
                <p className="fw-medium mb-0">{file.name}</p>
                <p className="text-secondary small mb-0">
                  {(file.size / 1024).toFixed(1)} KB · Click para cambiar
                </p>
              </>
            ) : (
              <>
                <p className="fw-medium mb-0">Arrastrar aqui</p>
                <p className="text-secondary small mb-0">
                  o <span className="text-decoration-underline">selecciónalo</span> desde tu equipo · formatos admitidos .xlsx, .xls
                </p>
              </>
            )}
          </div>

          <div className="d-flex align-items-center gap-3 mt-3">
            <button
              type="submit"
              disabled={!file || loading}
              className="btn btn-dark"
            >
              {loading ? "Procesando..." : "Procesar archivo"}
            </button>

            {file && !loading && (
              <button
                type="button"
                onClick={() => setFile(null)}
                className="btn btn-link text-secondary p-0 text-decoration-underline"
              >
                Remover
              </button>
            )}
          </div>

          {error && <p className="text-danger small mt-2">{error}</p>}
        </form>

        {/* Tabla de expedientes */}
        <div className="border rounded-3 overflow-hidden">
          <table className="table table-hover align-middle mb-0">
            <thead className="table-light">
              <tr>
                <th className="text-uppercase small text-secondary fw-semibold">ID</th>
                <th className="text-end text-uppercase small text-secondary fw-semibold" style={{ width: 220 }}>
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody>
              {expedientes.length === 0 ? (
                <tr>
                  <td colSpan={2} className="text-center text-secondary py-4">
                    No hay expedientes todavía.
                  </td>
                </tr>
              ) : (
                expedientes.map((exp) => (
                  <tr key={exp.id}>
                    <td>{exp.id}</td>
                    <td className="text-end">
                      <button
                        className="btn btn-sm btn-outline-secondary"
                        onClick={() => abrirVentana(exp.id)}
                      >
                        Editar
                      </button>
                      <button
                        type="button"
                        className="btn btn-sm btn-outline-danger ms-2"
                        onClick={() => handleEliminar(exp.id)}
                        title="Eliminar expediente"
                      >
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

      {/* Ventanas abiertas */}
      {ventanas.map((v) => (
        <ExpedienteWindow
          key={v.id}
          idExpediente={v.id}
          open={true}
          restoreKey={v.restoreKey}
          onClose={() => cerrarVentana(v.id)}
        />
      ))}
    </AdminLayout>
  );
}