<?php

namespace App\Services\ExcelParser;

class FixedData
{
    public function extract(array $rows): array
    {
        $data = [
            'fecha_inicio'  => null,
            'fecha_termino' => null,
        ];

        foreach (array_slice($rows, 0, 10) as $row) {
            foreach ($row as $j => $value) {
                $v = strtolower(trim((string) $value));

                if (str_starts_with($v, 'fecha de inicio')) {
                    $data['fecha_inicio'] = $this->next($row, $j);
                }
                if (str_starts_with($v, 'fecha de termino') || str_starts_with($v, 'fecha de término')) {
                    $data['fecha_termino'] = $this->next($row, $j);
                }
            }
        }

        return $data;
    }

    private function next(array $row, int $from): ?string
    {
        for ($k = $from + 1; $k < count($row); $k++) {
            $v = trim((string) $row[$k]);

            // Ignorar vacíos
            if ($v === '' || strtolower($v) === 'nan') {
                continue;
            }

            // Rechazar si es otra etiqueta (evita devolver "Fecha de Termino:" como fecha)
            $lower = strtolower($v);
            if (str_starts_with($lower, 'fecha de ') ||
                str_starts_with($lower, 'fecha:') ||
                str_ends_with($lower, ':')) {
                return null;
            }

            // Rechazar si no parece fecha (permite YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY)
            if (!preg_match('/\d{1,4}[\/\-]\d{1,2}[\/\-]\d{1,4}/', $v)) {
                return null;
            }

            return $v;
        }
        return null;
    }
}