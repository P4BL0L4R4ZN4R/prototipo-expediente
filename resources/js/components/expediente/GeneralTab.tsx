import type { Expediente } from "./useExpediente";

interface Props {
  expediente: Expediente;
}

export default function GeneralTab({ expediente }: Props) {
  return (
    <>
      <h6 className="text-muted mb-3">Datos generales</h6>
      <ul className="list-group mb-0">
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
    </>
  );
}