<?php

namespace App\Services\ExcelParser;

class Invoices
{
    public function extract(array $rows, int $headerRow): array
    {
        $headers = array_map(fn($v) => trim((string) $v), $rows[$headerRow]);

        $colConcept = array_search('Concepto', $headers);
        $colAmount  = array_search('Total Factura ($)', $headers);
        $colDate    = array_search('Fecha', $headers);

        $invoices = [];
        $currentConcept = 0;

        for ($i = $headerRow + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            $raw = trim((string) ($row[$colConcept] ?? ''));
            if ($raw !== '') {
                $currentConcept++;
            }

            $amount = $row[$colAmount] ?? null;
            if ($amount === null || $amount === '' || !is_numeric($amount)) {
                continue;
            }

            if ($currentConcept === 0) {
                continue;
            }

            $invoices[] = [
                'concepto_orden' => $currentConcept,
                'total_factura'  => (float) $amount,
                'fecha'          => $this->toDate($row[$colDate] ?? null),
            ];
        }

        return $invoices;
    }

    private function toDate($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            }
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}