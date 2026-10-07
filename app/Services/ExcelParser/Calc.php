<?php

namespace App\Services\ExcelParser;

use Carbon\Carbon;

class Calc
{
    public function calculate(array $concepts, array $invoices): array
    {
        $totalByConcept = [];
        $fechaLegalByConcept = [];

        foreach ($concepts as $c) {
            $orden = $c['orden'];

         
            $total = 0;
            foreach ($invoices as $f) {
                if ($f['concepto_orden'] === $orden) {
                    $total += $f['total_factura'];
                }
            }
            $totalByConcept[(string) $orden] = round($total, 2);

        
            $fechaLegalByConcept[(string) $orden] = $this->legalDateForConcept($orden, $invoices);
        }

        $programTotal = round(array_sum($totalByConcept), 2);

        return [
            'total_programa'           => $programTotal,
            'total_por_concepto'       => $totalByConcept,

            // Fecha legal por cada concepto (lo que pediste)
            'fecha_legal_por_concepto' => $fechaLegalByConcept,

            // Fecha legal global (compatibilidad con lo que ya existía)
            'fecha_legal'              => $this->legalDateGlobal($invoices),
        ];
    }

    private function legalDateForConcept(int|string $orden, array $invoices): ?string
    {
        $dates = [];

        foreach ($invoices as $f) {
            if ($f['concepto_orden'] !== $orden) {
                continue;
            }
            if (empty($f['fecha'])) {
                continue;
            }
            try {
                $dates[] = Carbon::parse($f['fecha']);
            } catch (\Throwable) {}
        }

        if (empty($dates)) {
            return null;
        }

        $oldest = collect($dates)->sort()->first();

        return $this->plusBusinessDays($oldest, 15)->format('Y-m-d');
    }

    private function legalDateGlobal(array $invoices): ?string
    {
        $dates = [];

        foreach ($invoices as $f) {
            if (empty($f['fecha'])) {
                continue;
            }
            try {
                $dates[] = Carbon::parse($f['fecha']);
            } catch (\Throwable) {}
        }

        if (empty($dates)) {
            return null;
        }

        $oldest = collect($dates)->sort()->first();

        return $this->plusBusinessDays($oldest, 15)->format('Y-m-d');
    }

    /**
     * Suma N días hábiles (salta domingos y festivos MX).
     * Si debe saltar también sábados, cambiar la condición por !$current->isWeekend().
     */
    private function plusBusinessDays(Carbon $date, int $days): Carbon
    {
        $count   = 0;
        $current = $date->copy();

        while ($count < $days) {
            $current->addDay();

            if ($current->dayOfWeek !== Carbon::SUNDAY && !$this->isHoliday($current)) {
                $count++;
            }
        }

        return $current;
    }

    private function isHoliday(Carbon $date): bool
    {
        $year = $date->year;

        # fechas de ejemplo, deben desactivarse para usar bd para almacenamiento estandar
        $holidays = [
            Carbon::create($year, 1, 1),   // Año Nuevo
            Carbon::create($year, 5, 1),   // Día del Trabajo
            Carbon::create($year, 9, 16),  // Independencia
            Carbon::create($year, 12, 25), // Navidad
            $this->firstMonday($year, 2),  // Constitución
            $this->thirdMonday($year, 3),  // Benito Juárez
            $this->thirdMonday($year, 11), // Revolución
        ];

        foreach ($holidays as $h) {
            if ($date->isSameDay($h)) {
                return true;
            }
        }

        return false;
    }

    private function firstMonday(int $year, int $month): Carbon
    {
        $d = Carbon::create($year, $month, 1);
        while ($d->dayOfWeek !== Carbon::MONDAY) {
            $d->addDay();
        }
        return $d;
    }

    private function thirdMonday(int $year, int $month): Carbon
    {
        return $this->firstMonday($year, $month)->addDays(14);
    }
}