type Tab = "general" | "propuesta" | "entregable";

interface Props {
  activeTab: Tab;
  onTabChange: (t: Tab) => void;
  onExport: () => void;
  exportando: boolean;
  disabledExport: boolean;
}

const TABS: { id: Tab; label: string }[] = [
  { id: "general", label: "General" },
  { id: "propuesta", label: "Propuesta" },
  { id: "entregable", label: "Entregable" },
];

export default function ExpedienteHeader({
  activeTab,
  onTabChange,
  onExport,
  exportando,
  disabledExport,
}: Props) {
  return (
    <div className="d-flex justify-content-between align-items-center bg-primary px-3 pt-2 border-bottom">
      <ul className="nav nav-tabs border-0">
        {TABS.map((t) => (
          <li className="nav-item" key={t.id}>
            <button
              type="button"
              className={`nav-link px-3 py-2 ${
                activeTab === t.id
                  ? "active bg-white text-primary fw-semibold"
                  : "text-white bg-transparent"
              }`}
              onClick={() => onTabChange(t.id)}
            >
              {t.label}
            </button>
          </li>
        ))}
      </ul>

      <button
        type="button"
        className="btn btn-sm btn-outline-light mb-2"
        onClick={onExport}
        disabled={disabledExport || exportando}
        title="Descarga los 7 documentos Word en un ZIP"
      >
        {exportando ? (
          <>
            <span className="spinner-border spinner-border-sm me-2" />
            Generando ZIP...
          </>
        ) : (
          "Exportar todo (ZIP)"
        )}
      </button>
    </div>
  );
}