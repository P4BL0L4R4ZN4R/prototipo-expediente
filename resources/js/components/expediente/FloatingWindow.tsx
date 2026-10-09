import { Rnd } from "react-rnd";
import { ReactNode, useEffect, useState } from "react";
import { createPortal } from "react-dom";
// import "./FloatingWindow.css";
import "@/../css/expediente-floating-window.css";


interface Props {
  title: string;
  open: boolean;
  onClose: () => void;
  children: ReactNode;
  headerRight?: ReactNode;
  width?: number;
  height?: number;
  initialX?: number;
  initialY?: number;
  restoreKey?: number;
}

export default function FloatingWindow({
  title,
  open,
  onClose,
  children,
  headerRight,
  width = 900,
  height = 650,
  initialX = 80,
  initialY = 80,
  restoreKey,
}: Props) {
  const [size, setSize] = useState({ width, height });
  const [pos, setPos] = useState({ x: initialX, y: initialY });
  const [maximizado, setMaximizado] = useState(true);
  const [minimizado, setMinimizado] = useState(false);
  const [mainEl, setMainEl] = useState<HTMLElement | null>(null);
  const [barEl, setBarEl] = useState<HTMLElement | null>(null);

  useEffect(() => {
    setMainEl(document.querySelector("main"));
    setBarEl(document.getElementById("minimized-windows-bar"));
  }, []);

  useEffect(() => {
    if (open) {
      setMaximizado(true);
      setMinimizado(false);
    }
  }, [open]);

  // 👇 Restaurar cuando el padre lo pida
  useEffect(() => {
    if (restoreKey !== undefined && restoreKey > 0) {
      setMinimizado(false);
    }
  }, [restoreKey]);

  if (!open) return null;

  const renderHeader = () => (
    <div
      className={`rnd-drag-handle fw-window-header ${
        maximizado || minimizado
          ? "fw-window-header-static"
          : "fw-window-header-draggable"
      } ${minimizado ? "fw-window-header-minimized" : ""}`}
      onDoubleClick={() => {
        if (!minimizado) setMaximizado((v) => !v);
      }}
      onClick={() => {
        if (minimizado) setMinimizado(false);
      }}
    >
      <span className="fw-semibold text-truncate">{title}</span>

      {headerRight && !minimizado && (
        <div
          className="d-flex align-items-center gap-2"
          onMouseDown={(e) => e.stopPropagation()}
          onClick={(e) => e.stopPropagation()}
        >
          {headerRight}
        </div>
      )}

      <div className="d-flex gap-1 align-items-center">
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            setMinimizado((v) => !v);
          }}
          onMouseDown={(e) => e.stopPropagation()}
          title={minimizado ? "Restaurar" : "Minimizar"}
          className="fw-window-icon-btn"
        >
          {minimizado ? "▢" : "—"}
        </button>

        {!minimizado && (
          <button
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              setMaximizado((v) => !v);
            }}
            onMouseDown={(e) => e.stopPropagation()}
            title={maximizado ? "Restaurar" : "Maximizar"}
            className="fw-window-icon-btn"
          >
            {maximizado ? "❐" : "▢"}
          </button>
        )}

        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            onClose();
          }}
          onMouseDown={(e) => e.stopPropagation()}
          title="Cerrar"
          className="fw-window-icon-btn fw-window-close-btn"
        >
          ×
        </button>
      </div>
    </div>
  );

  const renderBody = () => <div className="fw-window-body">{children}</div>;

  // ── Modo minimizado ─────────────────────────────────────
  if (minimizado) {
    if (barEl) {
      return createPortal(
        <div className="fw-window-minimized">
          {renderHeader()}
        </div>,
        barEl
      );
    }
    return (
      <div className="fw-window-minimized-wrap">
        <div className="fw-window-minimized">
          {renderHeader()}
        </div>
      </div>
    );
  }

  // ── Modo maximizado ─────────────────────────────────────
  if (maximizado) {
    if (!mainEl) {
      return (
        <div className="fw-window-maximized fw-window-maximized-fixed">
          {renderHeader()}
          {renderBody()}
        </div>
      );
    }

    return createPortal(
      <div className="fw-window-maximized fw-window-maximized-absolute">
        {renderHeader()}
        {renderBody()}
      </div>,
      mainEl
    );
  }

  // ── Modo flotante ───────────────────────────────────────
  return (
    <Rnd
      size={size}
      position={pos}
      onDragStop={(e, d) => setPos({ x: d.x, y: d.y })}
      onResizeStop={(e, direction, ref, delta, position) => {
        setSize({ width: ref.offsetWidth, height: ref.offsetHeight });
        setPos(position);
      }}
      minWidth={500}
      minHeight={400}
      bounds="window"
      dragHandleClassName="rnd-drag-handle"
      style={{ zIndex: 9999 }}
    >
      <div className="fw-window-floating">
        {renderHeader()}
        {renderBody()}
      </div>
    </Rnd>
  );
}