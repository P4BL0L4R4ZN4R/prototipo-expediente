// resources/js/layouts/main.tsx

import { ReactNode } from "react";
import { Link } from "@inertiajs/react";

type Props = {
  children: ReactNode;
};

const navItems = [
  { label: "Documentos", href: "/documentos" },
  // { label: "Productos", href: "/productos" },
];

export default function AdminLayout({ children }: Props) {
  return (
    <div
      style={{
        display: "flex",
        minHeight: "100vh",
        fontFamily: "system-ui, sans-serif",
        background: "#ffffff",
        color: "#111111",
      }}
    >
      {/* Sidebar */}
      <aside
        style={{
          width: 220,
          padding: "20px 0",
          borderRight: "1px solid #e5e5e5",
        }}
      >
        <div
          style={{
            padding: "0 20px 20px",
            fontSize: 18,
            fontWeight: 600,
            borderBottom: "1px solid #e5e5e5",
            marginBottom: 16,
          }}
        >
          AdminPanel
        </div>

        <nav style={{ display: "flex", flexDirection: "column" }}>
          {navItems.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              style={{
                padding: "10px 20px",
                textDecoration: "none",
                color: "inherit",
                fontSize: 14,
              }}
            >
              {item.label}
            </Link>
          ))}
        </nav>
      </aside>

      {/* Contenido dinámico */}
      <main style={{ flex: 1, padding: "24px 32px",  position: "relative" }}>
        {children}
      </main>
      
    </div>
  );
}