<?php

namespace App\Services\Secciones\Propuesta;

use App\Services\Secciones\Prompts\PropuestaPrompts;
use App\Services\Word\TextNormalizer;

class Problematica
{
    public function __construct(
        private PropuestaPrompts $prompts,
        private TextNormalizer $normalizer,
    ) {}

    public function nombre(): string { return 'problematica'; }
    public function metodo(): string { return 'problematica'; }

    public function prompt(array $expediente): string
    {
        $ia   = $expediente['ia'];
        $meta = $expediente['meta'];

        $variables = [
            '{{RAMA}}'          => $meta['area'] ?? '',
            '{{TEMA}}'          => $meta['tema'] ?? '',
            '{{SERVICIO}}'      => $ia['servicio'] ?? '',
            '{{OBJETIVO}}'      => $ia['objetivo'] ?? '',
            '{{CLIENTE}}'       => $ia['cliente']  ?? '',
            '{{CONCEPTOS}}'     => collect($ia['conceptos'] ?? [])->pluck('concepto')->filter()->implode("\n"),
            '{{NUM_OBJETIVOS}}' => max(2, min((int) ($meta['num_objetivos'] ?? 5), 10)),
        ];

        return strtr($this->prompts->plantilla($this->nombre()), $variables);
    }

    public function parsear(string $texto, string $id = ''): string
    {
        return $this->normalizer->normalizar($texto);
    }

    public function metadata(array $expediente): array
    {
        $meta = $expediente['meta'];
        return [
            'rama' => $meta['area'] ?? '',
            'tema' => $meta['tema'] ?? '',
        ];
    }
}