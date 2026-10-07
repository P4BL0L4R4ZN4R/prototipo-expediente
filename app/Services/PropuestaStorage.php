<?php

namespace App\Services;

use App\Models\Expediente;

class PropuestaStorage
{
    public const SECCIONES = [
        'texto_propuesta',
        'introduccion',
        'problematica',
        'objetivo_general',
        'objetivos_especificos',
        'metodologia',
    ];

    /** Guarda una sección individual. */
    public function guardarSeccion(string $id, string $seccion, array $payload): void
    {
        $exp = Expediente::find($id);
        if (!$exp) return;

        $propuesta = $exp->propuesta_ia ?? [];

        $propuesta[$seccion] = [
            'ok'          => $payload['ok']          ?? false,
            'texto'       => $payload['texto']       ?? '',
            'proveedor'   => $payload['proveedor']   ?? null,
            'modelo'      => $payload['modelo']      ?? null,
            'tiempo_ms'   => $payload['tiempo_ms']   ?? null,
            'generado_en' => $payload['generado_en'] ?? now()->toIso8601String(),
        ];

        $exp->propuesta_ia = $propuesta;
        $exp->save();
    }

    /** Lee una sección individual. */
    public function leerSeccion(string $id, string $seccion): ?array
    {
        $exp = Expediente::find($id);
        if (!$exp) return null;

        $propuesta = $exp->propuesta_ia ?? [];
        return $propuesta[$seccion] ?? null;
    }

    /** Devuelve todas las secciones consolidadas (texto plano). */
    public function leerConsolidado(string $id): array
    {
        $exp = Expediente::find($id);

        $consolidado = [
            'texto_propuesta'       => '',
            'introduccion'          => '',
            'problematica'          => '',
            'objetivo_general'      => '',
            'objetivos_especificos' => '',
            'metodologia'           => '',
        ];

        if (!$exp) return $consolidado;

        $propuesta = $exp->propuesta_ia  ?? [];

        foreach (self::SECCIONES as $sec) {
            $consolidado[$sec] = $propuesta[$sec]['texto'] ?? '';
        }

        return $consolidado;
    }

    /** Compatibilidad con código viejo: ya no consolida en disco. */
    public function consolidar(string $id): array
    {
        return $this->leerConsolidado($id);
    }
}