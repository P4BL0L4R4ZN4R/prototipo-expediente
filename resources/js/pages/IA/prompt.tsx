// resources/js/pages/test/TestConsistenciaIA.tsx

import { useState } from "react";
import AdminLayout from "@/layouts/main";
import TestConsistenciaIAView from "./form";

export type ResultadoIA = {
  iteracion: number;
  texto: string;
  tiempo: number;
};

const ITERACIONES = 5;
const PAUSA_MS = 4500;

/**
 * Lee el token CSRF desde el meta tag o, si no existe,
 * desde la cookie XSRF-TOKEN que Laravel envía por defecto.
 */
function getCsrfToken(): string {
  const meta = document.querySelector(
    'meta[name="csrf-token"]'
  ) as HTMLMetaElement | null;

  if (meta?.content) return meta.content;

  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return match ? decodeURIComponent(match[1]) : "";
}

export default function TestConsistenciaIA() {
  const [input, setInput] = useState("");
  const [resultados, setResultados] = useState<ResultadoIA[]>([]);
  const [cargando, setCargando] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const probarConsistencia = async () => {
    if (!input.trim()) return;

    setCargando(true);
    setError(null);
    setResultados([]);

    const nuevos: ResultadoIA[] = [];

    for (let i = 1; i <= ITERACIONES; i++) {
      const inicio = performance.now();

      try {
        // Leer el token fresco en cada iteración (por si la sesión se regeneró)
        const csrf = getCsrfToken();

        const respuesta = await fetch("/gemini/test", {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": csrf,
            "X-Requested-With": "XMLHttpRequest",
          },
          body: JSON.stringify({ input }),
        });

        const fin = performance.now();

        // Leer como texto primero, para no romper si Laravel devuelve HTML de error
        const raw = await respuesta.text();

        if (!respuesta.ok) {
          // Intenta parsear JSON de error, si no, muestra el texto crudo
          let mensaje = `HTTP ${respuesta.status}`;
          try {
            const parsed = JSON.parse(raw);
            mensaje = parsed.error || parsed.message || mensaje;
          } catch {
            mensaje = raw.slice(0, 200) || mensaje;
          }
          throw new Error(mensaje);
        }

        const data = JSON.parse(raw);

        nuevos.push({
          iteracion: i,
          texto: data.texto,
          tiempo: Math.round(fin - inicio),
        });

        setResultados([...nuevos]);

        // Pausa entre iteraciones (excepto después de la última)
        if (i < ITERACIONES) {
          await new Promise((r) => setTimeout(r, PAUSA_MS));
        }
      } catch (err: any) {
        setError(err.message || "Error desconocido");
        break;
      }
    }

    setCargando(false);
  };

  const limpiar = () => {
    setInput("");
    setResultados([]);
    setError(null);
  };

  return (
    <AdminLayout>
      <TestConsistenciaIAView
        input={input}
        onInputChange={setInput}
        resultados={resultados}
        cargando={cargando}
        error={error}
        onProbar={probarConsistencia}
        onLimpiar={limpiar}
        iteraciones={ITERACIONES}
      />
    </AdminLayout>
  );
}