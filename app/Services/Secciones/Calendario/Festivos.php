<?php

namespace App\Services\Secciones\Calendario;

use Carbon\Carbon;

/**
 * STUB — Implementación futura.
 *
 * Cuando migres a BD, implementa esFestivo() con la query real.
 * Por ahora devuelve false para que el switch no rompa si alguien
 * lo activa por error.
 */
class Festivos
{
    public function esFestivo(Carbon $fecha): bool
    {
        // TODO: consultar tabla de días no laborables
        // return Festivo::whereDate('fecha', $fecha->toDateString())
        //     ->where('activo', true)
        //     ->exists();

        return false;
    }
}