<?php

namespace App\Services\Secciones\Entregable;

use App\Services\Secciones\Entregable\EntregableParser;
use App\Services\Secciones\Entregable\EntregablePromptBuilder;

class Problematica
{
    public function __construct(
        private EntregablePromptBuilder $builder,
        private EntregableParser $parser,
    ) {}

    public function nombre(): string
    {
        return 'problematica';
    }

    public function metodo(): string
    {
        return 'problematica';
    }

    public function prompt(array $expediente): string
    {
        return $this->builder->construir(
            $this->nombre(),
            $expediente['ia'],
            $expediente['ctx'],
            $expediente['meta'],
            $expediente['direccion'] ?? null,
        );
    }

    public function parsear(string $texto, string $id): string
    {
        return $this->parser->parsear($this->nombre(), $texto, $id);
    }
}