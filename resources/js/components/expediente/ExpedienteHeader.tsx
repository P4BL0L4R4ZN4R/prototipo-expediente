import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faFileZipper } from "@fortawesome/free-solid-svg-icons";
import "@/../css/tooltip.css";

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
  const deshabilitado = disabledExport || exportando;

  return (
    <div className="position-relative bg-primary px-3 pt-3 border-bottom">
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

      <div className="position-absolute" style={{ top: 8, right: 50 }}>
        <div className="tooltip-wrapper">
          <button
            type="button"
            onClick={onExport}
            disabled={deshabilitado}
            style={{
              width: 40,
              height: 40,
              display: "inline-flex",
              alignItems: "center",
              justifyContent: "center",
              border: "none",
              borderRadius: 6,
              background: "transparent",
              color: "#fff",
              fontSize: 22,
              cursor: deshabilitado ? "not-allowed" : "pointer",
              opacity: deshabilitado ? 0.5 : 1,
              transition: "background-color 0.15s ease, color 0.15s ease",
            }}
            onMouseEnter={(e) => {
              if (deshabilitado) return;
              e.currentTarget.style.background = "#fff";
              e.currentTarget.style.color = "#0d6efd";
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.background = "transparent";
              e.currentTarget.style.color = "#fff";
            }}
          >
            {exportando ? (
              <span
                style={{
                  width: 18,
                  height: 18,
                  border: "2px solid currentColor",
                  borderRightColor: "transparent",
                  borderRadius: "50%",
                  animation: "spin 0.7s linear infinite",
                  display: "inline-block",
                }}
              />
            ) : (
              <FontAwesomeIcon icon={faFileZipper} />
            )}
          </button>

          <span className="tooltip-bubble">Descargar ZIP</span>
        </div>
      </div>

      <style>{`
        @keyframes spin {
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}