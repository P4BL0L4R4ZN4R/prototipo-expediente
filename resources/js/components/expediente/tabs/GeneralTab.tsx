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
    guardandoEstado,
    direccion,
    setDireccion,
    guardandoDireccion,
    guardarEstado,
    guardarDireccion,
    hayCambiosSinGuardar,
  } = useExpedienteContext();

  const guardando = guardandoEstado || guardandoDireccion;

  const guardarTodo = async () => {
    await Promise.all([
      guardarEstado(),
      guardarDireccion(),
    ]);
  };

  return (
    <>
      <div className="d-flex justify-content-between align-items-center mb-3">
        <h6 className="text-muted mb-0">Datos generales</h6>
        <button
          type="button"
          className="btn btn-sm btn-primary"
          onClick={guardarTodo}
          disabled={!hayCambiosSinGuardar || guardando}
        >
          {guardando ? "Guardando..." : "Guardar"}
        </button>
      </div>

      <ul className="list-group mb-0">
        <li className="list-group-item d-flex justify-content-between align-items-center">
          <span className="text-muted flex-shrink-0 me-3">Estado</span>
          <Select
            options={ESTADOS}
            value={ESTADOS.find((o) => o.value === estado) ?? ESTADOS[0]}
            onChange={(opt) => setEstado(opt?.value ?? "")}
            isDisabled={guardando}
            isSearchable={true}
            menuPortalTarget={document.body}
            styles={{
              container: (base) => ({ ...base, width: 220 }),
              control: (base) => ({
                ...base,
                minHeight: 31,
                fontSize: 14,
                borderRadius: 6,
              }),
              menuPortal: (base) => ({ ...base, zIndex: 9999 }),
            }}
          />
        </li>

        <li className="list-group-item d-flex justify-content-between align-items-start">
          <span className="text-muted flex-shrink-0 me-3">Dirección</span>
          <textarea
            className="form-control form-control-sm flex-grow-1"
            value={direccion}
            onChange={(e) => setDireccion(e.target.value)}
            placeholder="Calle, número, colonia, CP, ciudad, estado"
            disabled={guardando}
            rows={2}
            style={{ fontSize: 14, resize: "vertical" }}
          />
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