<?php

namespace App\Services\Secciones\Calendario;

use Carbon\Carbon;

class DiasHabiles
{
    public function __construct(
        private FestivosMexico|Festivos $festivos,
    ) {}

    public function anterior(Carbon $fecha): Carbon
    {
        $cursor = $fecha->copy()->subDay();
        while ($cursor->isWeekend() || $this->festivos->esFestivo($cursor)) {
            $cursor->subDay();
        }
        return $cursor;
    }

    public function sumar(Carbon $fecha, int $dias): Carbon
    {
        $cursor = $fecha->copy();
        $contados = 0;

        while ($contados < $dias) {
            $cursor->addDay();
            if (!$cursor->isWeekend() && !$this->festivos->esFestivo($cursor)) {
                $contados++;
            }
        }

        return $cursor;
    }
}