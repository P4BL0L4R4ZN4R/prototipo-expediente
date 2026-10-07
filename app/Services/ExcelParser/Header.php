<?php

namespace App\Services\ExcelParser;

class Header
{
    public function extract(array $rows): array
    {
        $context = [
            'empresa_factura'  => '',
            'cliente'          => '',
            'servicio'         => '',
            'objetivo'         => '',
            'fecha_autorizado' => '',
        ];

        $labels = [
            'facturar por:'        => 'empresa_factura',
            'cliente:'             => 'cliente',
            'nombre del programa:' => 'servicio',
            'autorizado'           => 'fecha_autorizado',
        ];

        foreach ($rows as $row) {
            foreach ($row as $j => $value) {
                $lower = strtolower(trim((string) $value));

                foreach ($labels as $label => $field) {
                    if (str_starts_with($lower, $label)) {
                        $context[$field] = $this->next($row, $j);
                    }
                }

                if (str_starts_with($lower, 'objetivo:')) {
                    $context['objetivo'] = trim(
                        substr((string) $value, strpos($value, ':') + 1)
                    );
                }
            }
        }

        return $context;
    }

    private function next(array $row, int $from): string
    {
        for ($k = $from + 1; $k < count($row); $k++) {
            $v = trim((string) $row[$k]);
            if ($v !== '' && strtolower($v) !== 'nan') {
                return $v;
            }
        }
        return '';
    }
}