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
                if (str_starts_with($v, 'fecha de termino')) {
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
            if ($v !== '' && strtolower($v) !== 'nan') {
                return $v;
            }
        }
        return null;
    }
}