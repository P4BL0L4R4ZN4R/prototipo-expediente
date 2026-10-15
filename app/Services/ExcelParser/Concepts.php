<?php

namespace App\Services\ExcelParser;

class Concepts
{
    public function extract(array $rows, int $headerRow): array
    {
        $headers = array_map(fn($v) => trim((string) $v), $rows[$headerRow]);

        $colConcept = array_search('Concepto', $headers);
        // $colProcess = array_search('Proceso', $headers);

        $concepts = [];
        $order = 0;

        for ($i = $headerRow + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $raw = trim((string) ($row[$colConcept] ?? ''));

            if ($raw === '') {
                continue;
            }

            // Solo procesar si tiene paréntesis
            $concepto = $this->extraerDeParentesis($raw);
            if ($concepto === null) {
                continue;
            }

            $order++;
            $concepts[] = [
                'orden'    => $order,
                'concepto' => $concepto,
                // 'proceso'  => trim((string) ($row[$colProcess] ?? '')),
            ];
        }

        return $concepts;
    }

    /**
     * Extrae SOLO lo que está dentro del último paréntesis.
     * Si no hay paréntesis, devuelve null.
     *
     * Ejemplo:
     *   "ASESORÍA EN TÉCNICAS... (Metodologías para el diseño...)"
     *   → "Metodologías para el diseño..."
     */
    private function extraerDeParentesis(string $texto): ?string
    {
        if (preg_match_all('/\((.*?)\)/', $texto, $matches)) {
            $contenido = trim(end($matches[1]));
            return $contenido !== '' ? $contenido : null;
        }
        return null;
    }
}