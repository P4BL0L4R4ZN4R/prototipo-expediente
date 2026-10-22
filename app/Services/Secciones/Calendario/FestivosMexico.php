<?php

namespace App\Services\Secciones\Calendario;

use Carbon\Carbon;

class FestivosMexico
{
    public function esFestivo(Carbon $fecha): bool
    {
        $year = $fecha->year;

        $festivos = [
            Carbon::create($year, 1, 1),
            Carbon::create($year, 5, 1),
            Carbon::create($year, 9, 16),
            Carbon::create($year, 12, 25),
            $this->primerLunes($year, 2),
            $this->tercerLunes($year, 3),
            $this->tercerLunes($year, 11),
        ];

        foreach ($festivos as $f) {
            if ($fecha->isSameDay($f)) return true;
        }
        return false;
    }

    private function primerLunes(int $year, int $month): Carbon
    {
        $d = Carbon::create($year, $month, 1);
        while ($d->dayOfWeek !== Carbon::MONDAY) {
            $d->addDay();
        }
        return $d;
    }

    private function tercerLunes(int $year, int $month): Carbon
    {
        return $this->primerLunes($year, $month)->addDays(14);
    }
}