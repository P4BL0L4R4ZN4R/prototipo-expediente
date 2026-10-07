import { Rnd } from "react-rnd";
import { ReactNode, useEffect, useRef, useState } from "react";
import { createPortal } from "react-dom";

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
}: Props) {
  const [size, setSize] = useState({ width, height });
  const [pos, setPos] = useState({ x: initialX, y: initialY });
  const [maximizado, setMaximizado] = useState(true);
  const [sizeAntes, setSizeAntes] = useState({ width, height });
  const [posAntes, setPosAntes] = useState({ x: initialX, y: initialY });
  const [mainEl, setMainEl] = useState<HTMLElement | null>(null);

  // Encontrar el <main> cuando monta
  useEffect(() => {
    setMainEl(document.querySelector("main"));
  }, []);

  // Resetear al abrir
  useEffect(() => {
    if (open) {
      setMaximizado(true);
    }
  }, [open]);

  if (!open) return null;

  const renderHeader = () => (
    <div
      className="rnd-drag-handle"
      style={{
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        padding: "12px 20px",
        background: "#f9fafb",
        borderBottom: "1px solid #e5e7eb",
        cursor: maximizado ? "default" : "move",
        userSelect: "none",
        gap: 12,
        flexShrink: 0,
      }}
      onDoubleClick={() => setMaximizado((v) => !v)}
    >
      <span style={{ fontSize: 15, fontWeight: 600, color: "#111827" }}>
        {title}
      </span>

      {headerRight && (
        <div
          style={{ display: "flex", alignItems: "center", gap: 8 }}
          onMouseDown={(e) => e.stopPropagation()}
        >
          {headerRight}
        </div>
      )}

      <div style={{ display: "flex", gap: 6, alignItems: "center" }}>
        <button
          type="button"
          onClick={() => setMaximizado((v) => !v)}
          onMouseDown={(e) => e.stopPropagation()}
          title={maximizado ? "Restaurar" : "Maximizar"}
          style={{
            background: "transparent",
            border: "none",
            cursor: "pointer",
            fontSize: 16,
            lineHeight: 1,
            color: "#6b7280",
            padding: 4,
          }}
        >
          {maximizado ? "❐" : "▢"}
        </button>

        <button
          type="button"
          onClick={onClose}
          onMouseDown={(e) => e.stopPropagation()}
          title="Cerrar"
          style={{
            background: "transparent",
            border: "none",
            cursor: "pointer",
            fontSize: 20,
            lineHeight: 1,
            color: "#6b7280",
            padding: 4,
          }}
        >
          ×
        </button>
      </div>
    </div>
  );

  const renderBody = () => (
    <div
      style={{
        flex: 1,
        minHeight: 0,
        overflowY: "auto",
        padding: 20,
      }}
    >
      {children}
    </div>
  );

  // =========================================================
  // MODO MAXIMIZADO: absoluto dentro del <main>
  // =========================================================
  if (maximizado) {
    // Si no hay <main>, hacemos fallback a fixed (cubre todo)
    if (!mainEl) {
      return (
        <div
          style={{
            position: "fixed",
            inset: 0,
            zIndex: 9999,
            background: "#fff",
            display: "flex",
            flexDirection: "column",
          }}
        >
          {renderHeader()}
          {renderBody()}
        </div>
      );
    }

    return createPortal(
      <div
        style={{
          position: "absolute",
          inset: 0,
          zIndex: 10,
          background: "#fff",
          display: "flex",
          flexDirection: "column",
          overflow: "hidden",
        }}
      >
        {renderHeader()}
        {renderBody()}
      </div>,
      mainEl
    );
  }

  // =========================================================
  // MODO FLOTANTE: Rnd normal
  // =========================================================
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
      <div
        style={{
          width: "100%",
          height: "100%",
          background: "#fff",
          border: "1px solid #d1d5db",
          borderRadius: 8,
          boxShadow: "0 10px 40px rgba(0, 0, 0, 0.20)",
          display: "flex",
          flexDirection: "column",
          overflow: "hidden",
        }}
      >
        {renderHeader()}
        {renderBody()}
      </div>
    </Rnd>
  );
}