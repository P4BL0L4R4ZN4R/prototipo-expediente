<?php

namespace App\Services\Storage;

use App\Services\Secciones\Orquestador;
use App\Services\CalculosExpediente;
use App\Services\Storage\ExpedienteTextoStorage;

class PropuestaStorage
{
    public function __construct(
        private ExpedienteTextoStorage $textoStorage,
    ) {}

    public function guardarSeccion(string $id, string $seccion, array $payload): bool
    {
        $texto = $payload['texto'] ?? '';
        if ($texto === '') return false;

        return $this->textoStorage->guardarSeccion($id, 'propuesta', $seccion, $texto);
    }

    public function leerSeccion(string $id, string $seccion): string
    {
        return $this->textoStorage->leerSeccion($id, 'propuesta', $seccion);
    }

    public function leerConsolidado(string $id): array
    {
        $resultado = [];
        foreach (Orquestador::propuesta() as $seccion) {
            $resultado[$seccion->nombre()] = $this->textoStorage->leerSeccion($id, 'propuesta', $seccion->nombre());
        }
        return $resultado;
    }

    /**
     * Construye el contexto del entregable a partir de la propuesta.
     * Movido desde EntregableService::construirContexto().
     */
    public function construirContexto(string $id, CalculosExpediente $calc): array
    {
        $prop = $this->leerConsolidado($id);

        $ctx = [
            'periodo_servicio'      => $calc->periodoServicio(),
            'anio_reporte'          => $calc->anioReporte(),
            'semanas_totales'       => $calc->semanasTotales(),
            'periodos_por_concepto' => $calc->periodosPorConcepto(),
        ];

        // Campos leídos de la propuesta, según config/entregable.php
        foreach (config('entregable.extraer') as $claveCtx => $campoProp) {
            $ctx[$claveCtx] = $prop[$campoProp] ?? '';
        }

        return $ctx;
    }
}