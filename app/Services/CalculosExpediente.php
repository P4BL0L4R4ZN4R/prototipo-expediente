<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalculosExpediente
{
    private const MONTO_MAXIMO_MENSUAL = 1_000_000;

    private Collection $conceptos;   // conceptos con facturas anidadas
    private Collection $facturas;    // facturas aplanadas (una fila por factura)

    public function __construct(array $data)
    {
        $this->conceptos = collect($data['conceptos'] ?? []);

        $this->facturas = $this->conceptos->flatMap(function ($c) {
            return collect($c['facturas'] ?? [])->map(fn($f) => array_merge($f, [
                'concepto_orden' => $f['concepto_orden'] ?? $c['orden'] ?? null,
                'concepto'       => $c['concepto']       ?? null,
            ]));
        });
    }

    // =========================================================
    // FECHAS REALES
    // =========================================================

    public function fechaRealInicio(): ?Carbon
    {
        return $this->fechasValidas()->min();
    }

    public function fechaRealFin(): ?Carbon
    {
        return $this->fechasValidas()->max();
    }

    private function fechasValidas(): Collection
    {
        return $this->facturas
            ->pluck('fecha')
            ->filter(fn($f) => !empty($f))
            ->map(fn($f) => Carbon::parse($f))
            ->sort()
            ->values();
    }

    // =========================================================
    // PASO 1 — Agrupar facturas por concepto (sumar montos)
    // =========================================================

    /**
     * Devuelve [{concepto, monto}] en orden de aparición,
     * donde cada item es UN concepto con su monto total.
     */
    private function agruparFacturasPorConcepto(): array
    {
        $resultado = [];
        $indice    = [];

        foreach ($this->conceptos as $c) {
            $nombre = trim((string) ($c['concepto'] ?? ''));
            if ($nombre === '') continue;

            $monto = collect($c['facturas'] ?? [])
                ->sum(fn($f) => (float) ($f['total_factura'] ?? 0));

            if (!isset($indice[$nombre])) {
                $indice[$nombre] = count($resultado);
                $resultado[] = ['concepto' => $nombre, 'monto' => $monto];
            } else {
                $resultado[$indice[$nombre]]['monto'] += $monto;
            }
        }

        return $resultado;
    }

    // =========================================================
    // PASO 2 — Ajuste por monto ($1M/mes)
    // =========================================================

    private function calcularMesesAproximados(Carbon $ini, Carbon $fin): int
    {
        $dias = $ini->diffInDays($fin) + 1;
        return max(1, (int) ceil($dias / 30));
    }

    /**
     * Réplica de restar_meses_fecha() del .py: resta meses cuidando
     * que el día exista en el mes destino.
     */
    private function restarMeses(Carbon $fecha, int $meses): Carbon
    {
        if ($meses <= 0) return $fecha->copy();

        $mesTotal = $fecha->month - $meses;
        $anio     = $fecha->year + intdiv($mesTotal - 1, 12);
        $mes      = (($mesTotal - 1) % 12 + 12) % 12 + 1;

        $ultimoDia = Carbon::create($anio, $mes, 1)->daysInMonth;
        $dia       = min($fecha->day, $ultimoDia);

        return Carbon::create($anio, $mes, $dia)->startOfDay();
    }

    private function ajustarPeriodoPorMonto(
        Carbon $ini,
        Carbon $fin,
        float $totalFacturado
    ): array {
        $mesesReales   = $this->calcularMesesAproximados($ini, $fin);
        $mesesPorMonto = max(1, (int) ceil($totalFacturado / self::MONTO_MAXIMO_MENSUAL));
        $mesesFinales  = max($mesesReales, $mesesPorMonto);
        $mesesExtra    = $mesesFinales - $mesesReales;

        return [
            'fecha_inicio_real'     => $ini,
            'fecha_fin_real'        => $fin,
            'fecha_inicio_ajustada' => $this->restarMeses($ini, $mesesExtra),
            'fecha_fin_ajustada'    => $fin,
            'meses_extra'           => $mesesExtra,
        ];
    }

    // =========================================================
    // PASO 3 — Semanas totales
    // =========================================================

    private function contarDiasLaboralesLunesSabado(Carbon $ini, Carbon $fin): int
    {
        $dias = 0;
        $cur  = $ini->copy()->startOfDay();
        $end  = $fin->copy()->startOfDay();

        while ($cur->lte($end)) {
            // 0=domingo en Carbon; lunes-sábado son 1..6
            if ($cur->dayOfWeek !== Carbon::SUNDAY) {
                $dias++;
            }
            $cur->addDay();
        }

        return $dias;
    }

    /**
     * Réplica del round() de Python (banker's rounding).
     * PHP round() redondea 0.5 hacia arriba; Python va al par más cercano.
     */
    private function roundPython(float $n): int
    {
        $piso = floor($n);
        $frac = $n - $piso;

        if ($frac < 0.5) return (int) $piso;
        if ($frac > 0.5) return (int) $piso + 1;

        // exactamente .5 → al par
        return ((int) $piso % 2 === 0) ? (int) $piso : (int) $piso + 1;
    }

    public function semanasTotales(): int
    {
        $ini = $this->fechaRealInicio();
        $fin = $this->fechaRealFin();
        if (!$ini || !$fin) return 0;

        $facturas = $this->agruparFacturasPorConcepto();
        $total    = array_sum(array_column($facturas, 'monto'));

        $ajuste = $this->ajustarPeriodoPorMonto($ini, $fin, $total);

        $dias = $this->contarDiasLaboralesLunesSabado(
            $ajuste['fecha_inicio_ajustada'],
            $ajuste['fecha_fin_ajustada']
        );

        return max(1, $this->roundPython($dias / 6));
    }

    // =========================================================
    // PASO 4 — Distribuir semanas por monto
    // =========================================================

    private function distribuirSemanasPorMonto(array $facturas, int $semanasTotales): array
    {
        $totalMonto = array_sum(array_column($facturas, 'monto'));

        if ($totalMonto <= 0) {
            $base = max(1, intdiv($semanasTotales, max(1, count($facturas))));
            foreach ($facturas as &$f) {
                $f['semanas_decimal'] = $base;
                $f['semanas']         = $base;
            }
            unset($f);
        } else {
            foreach ($facturas as &$f) {
                $prop = $f['monto'] / $totalMonto;
                $f['semanas_decimal'] = $prop * $semanasTotales;
                $f['semanas']         = max(1, (int) floor($f['semanas_decimal']));
            }
            unset($f);
        }

        $asignadas  = array_sum(array_column($facturas, 'semanas'));
        $diferencia = $semanasTotales - $asignadas;

        if ($diferencia > 0) {
            // Ordenar por parte decimal descendente
            usort($facturas, function ($a, $b) {
                $da = $a['semanas_decimal'] - floor($a['semanas_decimal']);
                $db = $b['semanas_decimal'] - floor($b['semanas_decimal']);
                return $db <=> $da;
            });

            $i = 0;
            $n = count($facturas);
            while ($diferencia > 0 && $n > 0) {
                $facturas[$i % $n]['semanas']++;
                $diferencia--;
                $i++;
            }
        } elseif ($diferencia < 0) {
            // Quitar a los de menor monto
            usort($facturas, fn($a, $b) => $a['monto'] <=> $b['monto']);

            $i = 0;
            $n = count($facturas);
            $vueltas = 0;

            while ($diferencia < 0 && $vueltas < $n && $n > 0) {
                $idx = $i % $n;
                if ($facturas[$idx]['semanas'] > 1) {
                    $facturas[$idx]['semanas']--;
                    $diferencia++;
                    $vueltas = 0;
                } else {
                    $vueltas++;
                }
                $i++;
            }
        }

        return $facturas;
    }

    private function asignarRangosSemanales(array $facturas): array
    {
        $semanaActual = 1;

        foreach ($facturas as &$f) {
            $duracion = (int) $f['semanas'];
            $f['semana_inicio'] = $semanaActual;
            $f['semana_fin']    = $semanaActual + $duracion - 1;
            $semanaActual       = $f['semana_fin'] + 1;
        }
        unset($f);

        return $facturas;
    }

    // =========================================================
    // API PÚBLICA
    // =========================================================

    /**
     * @return array<string, array{
     *     semana_inicio:int, semana_fin:int, semanas:int,
     *     periodo_texto:string, monto:float
     * }>
     */
    public function periodosPorConcepto(): array
    {
        $ini = $this->fechaRealInicio();
        $fin = $this->fechaRealFin();
        if (!$ini || !$fin) return [];

        $facturas = $this->agruparFacturasPorConcepto();
        if (empty($facturas)) return [];

        $total    = array_sum(array_column($facturas, 'monto'));
        $ajuste   = $this->ajustarPeriodoPorMonto($ini, $fin, $total);

        $dias = $this->contarDiasLaboralesLunesSabado(
            $ajuste['fecha_inicio_ajustada'],
            $ajuste['fecha_fin_ajustada']
        );
        $semanasTotales = max(1, $this->roundPython($dias / 6));

        $facturas = $this->distribuirSemanasPorMonto($facturas, $semanasTotales);
        $facturas = $this->asignarRangosSemanales($facturas);

        $resultado = [];
        foreach ($facturas as $f) {
            $ini = (int) $f['semana_inicio'];
            $fin = (int) $f['semana_fin'];

            $periodoTexto = ($ini === $fin)
                ? "Semana {$ini}"
                : "Semana {$ini} a Semana {$fin}";

            $resultado[$f['concepto']] = [
                'semana_inicio' => $ini,
                'semana_fin'    => $fin,
                'semanas'       => (int) $f['semanas'],
                'periodo_texto' => $periodoTexto,
                'monto'         => (float) $f['monto'],
            ];
        }

        return $resultado;
    }

    public function periodoServicio(): string
    {
        $ini = $this->fechaRealInicio();
        $fin = $this->fechaRealFin();
        if (!$ini || !$fin) return '';

        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        $iniTxt = sprintf('%d de %s del %d', $ini->day, $meses[$ini->month], $ini->year);
        $finTxt = sprintf('%d de %s del %d', $fin->day, $meses[$fin->month], $fin->year);

        return "{$iniTxt} al {$finTxt}";
    }

    public function anioReporte(): int
    {
        $fin = $this->fechaRealFin();
        return $fin ? (int) $fin->format('Y') : (int) date('Y');
    }
}