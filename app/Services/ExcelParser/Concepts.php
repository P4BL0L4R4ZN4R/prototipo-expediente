<?php

namespace App\Services\ExcelParser;

class Concepts
{
    public function extract(array $rows, int $headerRow): array
    {
        $headers = array_map(fn($v) => trim((string) $v), $rows[$headerRow]);

        $colConcept = array_search('Concepto', $headers);
        $colProcess = array_search('Proceso', $headers);
        $colKey     = array_search('Clave', $headers);
        $colDesc    = array_search('Descripción', $headers);
        $colCost    = array_search('Costo P/MES', $headers);

        $concepts = [];
        $order = 0;

        for ($i = $headerRow + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $raw = trim((string) ($row[$colConcept] ?? ''));

            if ($raw === '') {
                continue;
            }

            $order++;
            $concepts[] = [
                'orden'             => $order,
                'concepto'          => $this->inParens($raw),
                'concepto_completo' => $raw,
                'proceso'           => trim((string) ($row[$colProcess] ?? '')),
                'clave'             => trim((string) ($row[$colKey] ?? '')),
                'descripcion'       => trim((string) ($row[$colDesc] ?? '')),
                'costo_p_mes'       => $row[$colCost] ?? null,
            ];
        }

        return $concepts;
    }

    private function inParens(string $text): string
    {
        if (preg_match_all('/\((.*?)\)/', $text, $matches)) {
            return trim(end($matches[1]));
        }
        return trim($text);
    }
}