import Select from "react-select";
import { useExpedienteContext } from "../context/ExpedienteContext";

const ESTADOS = [
  { value: "", label: "---" },
  { value: "borrador", label: "Borrador" },
  { value: "en_revision", label: "En revisión" },
  { value: "aprobado", label: "Aprobado" },
  { value: "finalizado", label: "Finalizado" },
];

export default function GeneralTab() {
  const {
    expediente,
    estado,
    setEstado,
    guardarEstado,
    guardandoEstado,
  } = useExpedienteContext();

  return (
    <>
      <h6 className="text-muted mb-3">Datos generales</h6>
      <ul className="list-group mb-0">
        <li className="list-group-item d-flex justify-content-between align-items-center">
          <span className="text-muted">Estado</span>
          <div className="d-flex align-items-center gap-2">
            <Select
              options={ESTADOS}
              value={ESTADOS.find((o) => o.value === estado) ?? ESTADOS[0]}
              onChange={(opt) => setEstado(opt?.value ?? "")}
              isDisabled={guardandoEstado}
              isSearchable={true}
              menuPortalTarget={document.body}
              styles={{
                container: (base) => ({ ...base, width: 180 }),
                control: (base) => ({
                  ...base,
                  minHeight: 31,
                  fontSize: 14,
                  borderRadius: 6,
                }),
                menuPortal: (base) => ({ ...base, zIndex: 9999 }),
              }}
            />
            <button
              type="button"
              className="btn btn-sm btn-primary"
              onClick={guardarEstado}
              disabled={guardandoEstado}
            >
              {guardandoEstado ? "Guardando..." : "Guardar"}
            </button>
          </div>
        </li>

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
          <span className="text-end" style={{ maxWidth: "60%" }}>
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