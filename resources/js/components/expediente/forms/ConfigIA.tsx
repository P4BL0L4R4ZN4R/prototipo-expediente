import React from 'react';

interface Props {
  tema: string;
  area: string;
  numObjetivos: number;
  onTemaChange: (v: string) => void;
  onAreaChange: (v: string) => void;
  onNumObjetivosChange: (v: number) => void;
  readonlyInputs?: boolean;
}

export default function ConfigIA({
  tema,
  area,
  numObjetivos,
  onTemaChange,
  onAreaChange,
  onNumObjetivosChange,
  readonlyInputs = false,
}: Props) {
  return (
    <div className="card mb-3">
      <div className="card-body py-2 px-3">
        <div className="row g-2">
          {/* Tema */}
          <div className="col-md-6">
            <label className="form-label small mb-1">Tema</label>
            <input
              type="text"
              className="form-control form-control-sm"
              value={tema}
              onChange={(e) => onTemaChange(e.target.value)}
              placeholder="Ej: Almacenamiento de información digital"
              disabled={readonlyInputs}
            />
          </div>

          {/* Área */}
          <div className="col-md-6">
            <label className="form-label small mb-1">Área</label>
            <input
              type="text"
              className="form-control form-control-sm"
              value={area}
              onChange={(e) => onAreaChange(e.target.value)}
              placeholder="Ej: Tecnologías de la información"
              disabled={readonlyInputs}
            />
          </div>

          {/* Cantidad de objetivos específicos */}
          <div className="col-12">
            <label className="form-label small mb-1">Número de objetivos específicos</label>
            <input
              type="number"
              className="form-control form-control-sm"
              value={numObjetivos}
              onChange={(e) => onNumObjetivosChange(Number(e.target.value))}
              min={2}
              max={10}
              step={1}
              placeholder="Ej: 5"
              disabled={readonlyInputs}
            />
          </div>
        </div>
      </div>
    </div>
  );
}