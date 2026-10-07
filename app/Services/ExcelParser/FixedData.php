<?php

namespace App\Services\ExcelParser;

class FixedData
{
    public function extract(array $rows): array
    {
        $data = [
            'fecha_inicio'  => null,
            'fecha_termino' => null,
            'costo_p_mes'   => null,
        ];

        foreach (array_slice($rows, 0, 10) as $row) {
            foreach ($row as $j => $value) {
                $v = strtolower(trim((string) $value));

                if (str_starts_with($v, 'fecha de inicio')) {
                    $data['fecha_inicio'] = $this->next($row, $j);
                }
                if (str_starts_with($v, 'fecha de termino')) {
                    $data['fecha_termino'] = $this->next($row, $j);
                }
            }
        }

        foreach ($rows as $i => $row) {
            $lower = array_map(fn($v) => strtolower(trim((string) $v)), $row);

            if (in_array('costo p/mes', $lower)) {
                $col = array_search('costo p/mes', $lower);
                for ($k = $i + 1; $k < count($rows); $k++) {
                    $v = $rows[$k][$col] ?? null;
                    if (is_numeric($v)) {
                        $data['costo_p_mes'] = (float) $v;
                        break 2;
                    }
                }
            }
        }

        return $data;
    }

    private function next(array $row, int $from): ?string
    {
        for ($k = $from + 1; $k < count($row); $k++) {
            $v = trim((string) $row[$k]);
            if ($v !== '' && strtolower($v) !== 'nan') {
                return $v;
            }
        }
        return null;
    }
}