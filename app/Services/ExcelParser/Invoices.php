<?php

namespace App\Services\ExcelParser;

class Invoices
{
    public function extract(array $rows, int $headerRow): array
    {
        $headers = array_map(fn($v) => trim((string) $v), $rows[$headerRow]);

        $colConcept = array_search('Concepto', $headers);
        $colAmount  = array_search('Total Factura ($)', $headers);
        $colInvoice = array_search('No. Factura', $headers);
        $colFolio   = array_search('Folio Fiscal', $headers);
        $colDate    = array_search('Fecha', $headers);
        $colNotes   = array_search('Observaciones', $headers);

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
                'no_factura'     => trim((string) ($row[$colInvoice] ?? '')),
                'folio_fiscal'   => trim((string) ($row[$colFolio] ?? '')),
                'fecha'          => $this->toDate($row[$colDate] ?? null),
                'observaciones'  => trim((string) ($row[$colNotes] ?? '')),
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