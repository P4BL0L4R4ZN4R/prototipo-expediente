// resources/js/layouts/main.tsx

import { ReactNode } from "react";
import { Link } from "@inertiajs/react";
import "@/../css/admin-layout.css";







type Props = {
  children: ReactNode;
};

const navItems = [
  { label: "Documentos", href: "/documentos" },
  // { label: "Productos", href: "/productos" },
];

export default function AdminLayout({ children }: Props) {
  return (
    <div className="d-flex vh-100 bg-white text-dark admin-layout">
      {/* Sidebar */}
      <aside className="admin-sidebar d-flex flex-column">
        <div className="px-3 pb-3 border-bottom mb-3 fs-5 fw-semibold">
          AdminPanel
        </div>

        <nav className="d-flex flex-column">
          {navItems.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className="admin-nav-link py-2 px-3 text-decoration-none text-dark"
            >
              {item.label}
            </Link>
          ))}
        </nav>
      </aside>

      {/* Contenido dinámico */}
      <main className="flex-grow-1 p-4 position-relative admin-main">
        {children}
      </main>

      {/* Barra de ventanas minimizadas (se llena automáticamente) */}
      <div id="minimized-windows-bar" />
    </div>
  );
}