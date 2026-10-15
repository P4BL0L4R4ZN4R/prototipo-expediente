<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Expediente;


class CalendarioController extends Controller
{
    public function show(string $id)
    {
        $exp = Expediente::find($id);

        if (!$exp) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        $data = $exp->data ?? [];
        $ia   = $exp->ia   ?? [];

        return response()->json([
            'id'    => $id,
            'filas' => $this->filas($data, $ia),
        ]);
    }

    public function filas(array $data, array $ia = []): array
    {
        // ✅ Mapa orden → concepto desde ia.conceptos
        $conceptosPorOrden = collect($ia['conceptos'] ?? [])
            ->keyBy('orden')
            ->map(fn($c) => $c['concepto'] ?? '')
            ->all();

        // 1. Construir bloques
        $bloques = collect($data['conceptos'] ?? [])
            ->map(function ($c, $i) use ($conceptosPorOrden) {
                $orden = $c['orden'] ?? ($i + 1);

                $fechas = collect($c['facturas'] ?? [])
                    ->pluck('fecha')->filter()
                    ->map(fn($f) => Carbon::parse($f)->toDateString())
                    ->unique()->values()->all();

                return [
                    'numero'    => $orden,
                    'actividad' => $conceptosPorOrden[$orden] ?? '',
                    'fechas'    => $fechas,
                ];
            })
            ->filter(fn($b) => $b['actividad'] !== '')
            ->values()
            ->all();

        if (empty($bloques)) {
            return [];
        }

        // 2. Agrupar por intersección transitiva
        $grupos = [];
        $grupoActual = [$bloques[0]];
        $fechasGrupo = $bloques[0]['fechas'];

        foreach (array_slice($bloques, 1) as $bloque) {
            $fechasBloque = $bloque['fechas'];
            $interseccion = array_intersect($fechasGrupo, $fechasBloque);

            if (!empty($interseccion)) {
                $grupoActual[] = $bloque;
                $fechasGrupo = array_values(array_unique(array_merge($fechasGrupo, $fechasBloque)));
            } else {
                $grupos[] = ['bloques' => $grupoActual, 'fechas' => $fechasGrupo];
                $grupoActual = [$bloque];
                $fechasGrupo = $fechasBloque;
            }
        }
        $grupos[] = ['bloques' => $grupoActual, 'fechas' => $fechasGrupo];

        // 3. Calcular inicio y fin de cada grupo
        $iniciosGrupos = array_map(
            fn($g) => collect($g['fechas'])->sort()->first(),
            $grupos
        );

        foreach ($grupos as $i => &$grupo) {
            $inicio = $iniciosGrupos[$i];

            if ($i < count($grupos) - 1) {
                $fin = $this->diaHabilAnterior(Carbon::parse($iniciosGrupos[$i + 1]));
            } else {
                $maxFecha = collect($grupo['fechas'])->sort()->last();
                $fin = $this->sumarDiasHabiles(Carbon::parse($maxFecha), 5);
            }

            foreach ($grupo['bloques'] as &$bloque) {
                $bloque['inicio'] = $inicio;
                $bloque['fin']    = $fin->toDateString();
            }
        }

        // 4. Aplanar
        $filas = collect($grupos)
            ->flatMap(function ($g) {
                return collect($g['bloques'])
                    ->values()
                    ->map(fn($b, $idx) => [
                        'numero'          => $b['numero'],
                        'inicio'          => Carbon::parse($b['inicio'])->format('d/m/Y'),
                        'fin'             => Carbon::parse($b['fin'])->format('d/m/Y'),
                        'actividad'       => $b['actividad'],
                        'fusionar_fechas' => $idx === 0,
                    ]);
            })
            ->values()
            ->all();

        return $filas;
    }

    // Helpers
    private function diaHabilAnterior(Carbon $fecha): Carbon
    {
        $cursor = $fecha->copy()->subDay();
        while ($cursor->isWeekend() || $this->esFestivo($cursor)) {
            $cursor->subDay();
        }
        return $cursor;
    }

    private function sumarDiasHabiles(Carbon $fecha, int $dias): Carbon
    {
        $cursor = $fecha->copy();
        $contados = 0;
        while ($contados < $dias) {
            $cursor->addDay();
            if (!$cursor->isWeekend() && !$this->esFestivo($cursor)) {
                $contados++;
            }
        }
        return $cursor;
    }

    private function esFestivo(Carbon $fecha): bool
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