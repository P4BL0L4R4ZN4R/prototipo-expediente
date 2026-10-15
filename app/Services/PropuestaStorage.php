<?php

namespace App\Services;

use App\Models\Expediente;

class PropuestaStorage
{
    public function __construct(
        private ExpedienteTextoStorage $textoStorage,
    ) {}

    /**
     * Guarda una sección de la propuesta.
     * Ahora escribe en archivo, no en BD.
     */
    public function guardarSeccion(string $id, string $seccion, array $payload): bool
    {
        $texto = $payload['texto'] ?? '';
        if ($texto === '') return false;

        return $this->textoStorage->guardarSeccion($id, 'propuesta', $seccion, $texto);
    }

    /**
     * Lee una sección de la propuesta.
     * Ahora lee del archivo, no de BD.
     */
    public function leerSeccion(string $id, string $seccion): string
    {
        return $this->textoStorage->leerSeccion($id, 'propuesta', $seccion);
    }

    /**
     * Devuelve TODAS las secciones de la propuesta con su texto.
     * Usado por EntregableController::construirContexto().
     */
    public function leerConsolidado(string $id): array
    {
        $secciones = [
            'texto_propuesta',
            'introduccion',
            'problematica',
            'objetivo_general',
            'objetivos_especificos',
            'metodologia',
        ];

        $resultado = [];
        foreach ($secciones as $seccion) {
            $resultado[$seccion] = $this->textoStorage->leerSeccion($id, 'propuesta', $seccion);
        }

        return $resultado;
    }
}