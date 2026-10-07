<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// use Illuminate\Support\Facades\Storage;
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

        return response()->json([
            'id'    => $id,
            'filas' => $this->filas($data),
        ]);
    }

    public function filas(array $data): array
    {
        // ============================================================
        // LOG 1: qué conceptos llegan y con qué fechas
        // ============================================================
        $resumenFechas = collect($data['conceptos'] ?? [])
            ->map(function ($c) {
                return [
                    'orden'  => $c['orden'] ?? '?',
                    'fechas' => collect($c['facturas'] ?? [])
                        ->pluck('fecha')->filter()->unique()->sort()->values()->all(),
                ];
            })->all();

        \Log::info('[Calendario] Conceptos con sus fechas', [
            'total'    => count($resumenFechas),
            'detalle'  => $resumenFechas,
        ]);

        // 1. Construir bloques
        $bloques = collect($data['conceptos'] ?? [])
            ->map(function ($c, $i) {
                $fechas = collect($c['facturas'] ?? [])
                    ->pluck('fecha')->filter()
                    ->map(fn($f) => Carbon::parse($f)->toDateString())
                    ->unique()->values()->all();

                return [
                    'numero'    => $c['orden']   ?? ($i + 1),
                    'actividad' => $c['proceso'] ?? '',
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

        // ============================================================
        // LOG 2: decisión de agrupación paso a paso
        // ============================================================
        \Log::info('[Calendario] Iniciando agrupación', [
            'concepto_inicial' => $bloques[0]['numero'],
            'fechas_iniciales' => $fechasGrupo,
        ]);

        foreach (array_slice($bloques, 1) as $bloque) {
            $fechasBloque = $bloque['fechas'];
            $interseccion = array_intersect($fechasGrupo, $fechasBloque);

            // 👇 LOG por cada comparación
            \Log::info('[Calendario] Comparando concepto', [
                'concepto'       => $bloque['numero'],
                'fechas_concepto'=> $fechasBloque,
                'fechas_grupo'   => $fechasGrupo,
                'interseccion'   => array_values($interseccion),
                'decision'       => !empty($interseccion) ? 'MISMO GRUPO' : 'NUEVO GRUPO',
            ]);

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

        // ============================================================
        // LOG 3: cuántos grupos se formaron
        // ============================================================
        \Log::info('[Calendario] Grupos formados', [
            'total_grupos' => count($grupos),
            'grupos'       => collect($grupos)->map(fn($g, $i) => [
                'grupo'          => $i + 1,
                'num_conceptos'  => count($g['bloques']),
                'conceptos'      => collect($g['bloques'])->pluck('numero')->all(),
                'fechas'         => $g['fechas'],
            ])->all(),
        ]);

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

        // ============================================================
        // LOG 4: filas finales que se mandan al Word
        // ============================================================
        \Log::info('[Calendario] Filas finales', [
            'total' => count($filas),
            'filas' => $filas,
        ]);

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