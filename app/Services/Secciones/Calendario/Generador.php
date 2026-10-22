<?php

namespace App\Services\Secciones\Calendario;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class Generador
{
    private DiasHabiles $diasHabiles;

    public function __construct()
    {
        // ─── Switch: cambia esta línea cuando migres a BD ───
        $provider = new FestivosMexico();
        // $provider = new Festivos();

        $this->diasHabiles = new DiasHabiles($provider);
    }

    public function filas(array $data, array $ia = []): array
    {
        // Mapa orden → concepto desde ia.conceptos
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
            $interseccion = array_intersect($fechasGrupo, $bloque['fechas']);

            if (!empty($interseccion)) {
                $grupoActual[] = $bloque;
                $fechasGrupo = array_values(array_unique(array_merge($fechasGrupo, $bloque['fechas'])));
            } else {
                $grupos[] = ['bloques' => $grupoActual, 'fechas' => $fechasGrupo];
                $grupoActual = [$bloque];
                $fechasGrupo = $bloque['fechas'];
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
                $fin = $this->diasHabiles->anterior(Carbon::parse($iniciosGrupos[$i + 1]));
            } else {
                $maxFecha = collect($grupo['fechas'])->sort()->last();
                $fin = $this->diasHabiles->sumar(Carbon::parse($maxFecha), 5);
            }

            foreach ($grupo['bloques'] as &$bloque) {
                $bloque['inicio'] = $inicio;
                $bloque['fin']    = $fin->toDateString();
            }
        }
        unset($grupo, $bloque);

        // 4. Aplanar
        return collect($grupos)
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
    }
}