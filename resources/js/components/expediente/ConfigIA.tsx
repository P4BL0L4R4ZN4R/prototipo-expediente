interface Props {
  tema: string;
  area: string;
  onTemaChange: (v: string) => void;
  onAreaChange: (v: string) => void;
}

export default function ConfigIA({
  tema,
  area,
  onTemaChange,
  onAreaChange,
}: Props) {
  return (
    <div className="card mb-3">
      <div className="card-body py-2 px-3">
        <div className="row g-2">
          <div className="col-md-6">
            <label className="form-label small mb-1">
              Tema
            </label>
            <input
              type="text"
              className="form-control form-control-sm"
              value={tema}
              onChange={(e) => onTemaChange(e.target.value)}
              placeholder="Ej: Almacenamiento de información digital"
            />
          </div>

          <div className="col-md-6">
            <label className="form-label small mb-1">
              Área
            </label>
            <input
              type="text"
              className="form-control form-control-sm"
              value={area}
              onChange={(e) => onAreaChange(e.target.value)}
              placeholder="Ej: Tecnologías de la información"
            />
          </div>
        </div>
      </div>
    </div>
  );
}