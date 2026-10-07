import { useEffect, useRef, useState } from "react";

interface Props {
  idExpediente: string | null;
  open: boolean;
  onClose: () => void;
}

interface ExpedienteFull {
  id: string;
  ia: Record<string, any>;
  data: Record<string, any>;
  meta: Record<string, any> | null;
  propuesta_ia: Record<string, any> | null;
  entregable: Record<string, any> | null;
}

export default function ExpedienteDialog({ idExpediente, open, onClose }: Props) {
  const dialogRef = useRef<HTMLDialogElement>(null);
  const [expediente, setExpediente] = useState<ExpedienteFull | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Campos editables (estado local)
  const [estado, setEstado] = useState("");
  const [tema, setTema] = useState("");
  const [area, setArea] = useState("");

  // Abrir / cerrar el dialog
  useEffect(() => {
    const dlg = dialogRef.current;
    if (!dlg) return;

    if (open && !dlg.open) {
      dlg.showModal();
    } else if (!open && dlg.open) {
      dlg.close();
    }
  }, [open]);

  // Cerrar cuando el usuario presiona ESC
  useEffect(() => {
    const dlg = dialogRef.current;
    if (!dlg) return;

    const handleClose = () => onClose();
    dlg.addEventListener("close", handleClose);
    return () => dlg.removeEventListener("close", handleClose);
  }, [onClose]);

  // Cargar datos cuando se abre
  useEffect(() => {
    if (!open || !idExpediente) return;

    setLoading(true);
    setError(null);
    setExpediente(null);

    fetch(`/api/prototipo/excel/${idExpediente}`)
      .then((r) => {
        if (!r.ok) throw new Error("No se pudo cargar el expediente");
        return r.json();
      })
      .then((data: ExpedienteFull) => {
        setExpediente(data);
        setEstado(data.meta?.estado ?? "");
        setTema(data.meta?.tema ?? "");
        setArea(data.meta?.area ?? "");
      })
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  }, [open, idExpediente]);

  const handleClose = () => dialogRef.current?.close();

  return (
    <dialog
      ref={dialogRef}
      className="border-0 rounded-3 shadow-lg p-0"
      style={{ maxWidth: 1000, width: "90%" }}
    >
      {/* Header */}
      <div className="modal-header border-bottom">
        <h5 className="modal-title">Expediente {idExpediente}</h5>
        <button
          type="button"
          className="btn-close"
          onClick={handleClose}
          aria-label="Cerrar"
        ></button>
      </div>

      {/* Body */}
      <div
        className="modal-body"
        style={{ minHeight: 300, maxHeight: "75vh", overflowY: "auto" }}
      >
        {loading && (
          <div className="text-center py-5">
            <div className="spinner-border text-primary" role="status">
              <span className="visually-hidden">Cargando...</span>
            </div>
            <p className="mt-3 text-muted">Cargando expediente...</p>
          </div>
        )}

        {error && (
          <div className="alert alert-danger" role="alert">
            {error}
          </div>
        )}

        {!loading && !error && expediente && (
          <>
            {/* ==================== DATOS GENERALES ==================== */}
            <h6 className="text-muted mb-3">Datos generales</h6>
            <ul className="list-group mb-4">
              <li className="list-group-item d-flex justify-content-between">
                <span className="text-muted">Cliente</span>
                <span className="fw-bold">{expediente.ia?.cliente ?? "—"}</span>
              </li>
              <li className="list-group-item d-flex justify-content-between">
                <span className="text-muted">Empresa factura</span>
                <span>{expediente.ia?.empresa_factura ?? "—"}</span>
              </li>
              <li className="list-group-item d-flex justify-content-between">
                <span className="text-muted">Servicio</span>
                <span>{expediente.ia?.servicio ?? "—"}</span>
              </li>
              <li className="list-group-item d-flex justify-content-between">
                <span className="text-muted">Objetivo</span>
                <span style={{ maxWidth: "60%", textAlign: "right" }}>
                  {expediente.ia?.objetivo ?? "—"}
                </span>
              </li>
              <li className="list-group-item d-flex justify-content-between">
                <span className="text-muted">Total programa</span>
                <span className="fw-bold">
                  ${(expediente.data?.calculos?.total_programa ?? 0).toLocaleString()}
                </span>
              </li>
            </ul>

            {/* ==================== CONCEPTOS ==================== */}
            <h6 className="text-muted mb-3">Conceptos</h6>
            <div className="table-responsive mb-4">
              <table className="table table-sm table-bordered">
                <thead className="table-light">
                  <tr>
                    <th>#</th>
                    <th>Concepto</th>
                    <th className="text-end">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {(expediente.data?.conceptos ?? []).map((c: any) => (
                    <tr key={c.orden}>
                      <td>{c.orden}</td>
                      <td>{c.concepto}</td>
                      <td className="text-end">
                        ${(c.total ?? 0).toLocaleString()}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* ==================== CONFIGURACIÓN IA ==================== */}
            <h6 className="text-muted mb-3">Configuración IA</h6>
            <div className="card mb-4">
              <div className="card-body">
                <div className="mb-3">
                  <label className="form-label">Estado</label>
                  <select
                    className="form-select"
                    value={estado}
                    onChange={(e) => setEstado(e.target.value)}
                  >
                    <option value="">— Sin definir —</option>
                    <option value="borrador">Borrador</option>
                    <option value="en_revision">En revisión</option>
                    <option value="aprobado">Aprobado</option>
                    <option value="finalizado">Finalizado</option>
                  </select>
                </div>

                <div className="mb-3">
                  <label className="form-label">Tema</label>
                  <input
                    type="text"
                    className="form-control"
                    value={tema}
                    onChange={(e) => setTema(e.target.value)}
                    placeholder="Ej: Almacenamiento de información digital"
                  />
                </div>

                <div className="mb-3">
                  <label className="form-label">Área</label>
                  <input
                    type="text"
                    className="form-control"
                    value={area}
                    onChange={(e) => setArea(e.target.value)}
                    placeholder="Ej: Tecnologías de la información"
                  />
                </div>

                <button
                  type="button"
                  className="btn btn-primary"
                  disabled
                  title="Pendiente"
                >
                  Guardar
                </button>
              </div>
            </div>

            {/* ==================== PROPUESTA IA ==================== */}
            <h6 className="text-muted mb-3">Propuesta IA</h6>
            <div className="card mb-4">
              <div className="card-body">
                {expediente.propuesta_ia ? (
                  Object.entries(expediente.propuesta_ia).map(([key, val]: any) => (
                    <div key={key} className="mb-3">
                      <div className="fw-bold text-capitalize mb-1">
                        {key.replace(/_/g, " ")}
                      </div>
                      <div
                        className="text-muted"
                        style={{ whiteSpace: "pre-wrap", fontSize: 14 }}
                      >
                        {val?.texto || <em>Sin texto</em>}
                      </div>
                    </div>
                  ))
                ) : (
                  <p className="text-muted mb-0">
                    <em>Sin generar aún.</em>
                  </p>
                )}
              </div>
            </div>

            {/* ==================== ENTREGABLE IA ==================== */}
            <h6 className="text-muted mb-3">Entregable IA</h6>
            <div className="card mb-0">
              <div className="card-body">
                {expediente.entregable ? (
                  Object.entries(expediente.entregable).map(([key, val]: any) => (
                    <div key={key} className="mb-3">
                      <div className="fw-bold text-capitalize mb-1">
                        {key.replace(/_/g, " ")}
                      </div>
                      <div
                        className="text-muted"
                        style={{ whiteSpace: "pre-wrap", fontSize: 14 }}
                      >
                        {val?.texto || <em>Sin texto</em>}
                      </div>
                    </div>
                  ))
                ) : (
                  <p className="text-muted mb-0">
                    <em>Sin generar aún.</em>
                  </p>
                )}
              </div>
            </div>
          </>
        )}
      </div>

      {/* Footer */}
      <div className="modal-footer border-top">
        <button type="button" className="btn btn-secondary" onClick={handleClose}>
          Cerrar
        </button>
      </div>
    </dialog>
  );
}