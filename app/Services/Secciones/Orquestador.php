<?php

namespace App\Services\Secciones;

use App\Http\Controllers\Api\IA\IAGenerationController;
use App\Models\Expediente;
use App\Services\CalculosExpediente;
// use App\Services\Entregable\EntregableService;
use App\Services\Storage\ExpedienteTextoStorage;
use App\Services\Storage\PropuestaStorage;
use App\Services\Secciones\Entregable\{
    DesarrolloConceptos,
    FinalizacionServicio,
    Introduccion,
    Problematica,
    Retrospectiva
};

use App\Services\Secciones\Propuesta\{
Metodologia,
ObjetivoGeneral,
ObjetivosEspecificos,
Problematica as PropuestaProblematica,
TextoPropuesta,
Introduccion as PropuestaIntroduccion,
};


class Orquestador
{
    public static function propuesta(): array
    {
        return [
            app(TextoPropuesta::class),
            app(PropuestaIntroduccion::class),
            app(PropuestaProblematica::class),
            app(ObjetivoGeneral::class),
            app(ObjetivosEspecificos::class),
            app(Metodologia::class),
        ];
    }

    public static function entregable(): array
    {
        return [
            app(Introduccion::class),
            app(Problematica::class),
            app(DesarrolloConceptos::class),
            app(FinalizacionServicio::class),
            app(Retrospectiva::class),
        ];
    }

    public static function buscar(string $documento, string $seccion): ?object
    {
        $secciones = $documento === 'propuesta'
            ? self::propuesta()
            : self::entregable();

        foreach ($secciones as $s) {
            if ($s->nombre() === $seccion) {
                return $s;
            }
        }

        return null;
    }

    public static function generar(string $documento, string $id, string $seccion): array
    {
        $seccionObj = self::buscar($documento, $seccion);
        if (!$seccionObj) {
            throw new \RuntimeException('Sección no válida');
        }

        $exp = Expediente::find($id);
        if (!$exp) {
            throw new \RuntimeException('Expediente no encontrado');
        }

        $ia   = $exp->ia   ?? [];
        $data = $exp->data ?? [];
        $meta = $exp->meta ?? [];

        if (empty($ia)) {
            throw new \RuntimeException('Expediente incompleto');
        }

        if (empty($meta['tema']) || empty($meta['area'])) {
            throw new \RuntimeException('Faltan "tema" y "area"');
        }

        // Contexto para entregable (lee propuesta)
        $ctx = [];
        if ($documento === 'entregable') {
            $calc = new CalculosExpediente($data);
            $ctx  = app(PropuestaStorage::class)->construirContexto($id, $calc);
        }

        $expediente = [
            'id'        => $id,
            'ia'        => $ia,
            'data'      => $data,
            'meta'      => $meta,
            'direccion' => $exp->direccion,
            'ctx'       => $ctx,
        ];

        $prompt = $seccionObj->prompt($expediente);

        $resultado = app(IAGenerationController::class)->generar($prompt);

        $textoFinal = $seccionObj->parsear($resultado['texto'] ?? '', $id);

        app(ExpedienteTextoStorage::class)->guardarSeccion($id, $documento, $seccion, $textoFinal);

        $base = [
            'id'             => $id,
            'seccion'        => $seccion,
            'ok'             => $resultado['ok'],
            'proveedor'      => $resultado['proveedor'],
            'modelo'         => $resultado['modelo'],
            'usado_fallback' => $resultado['usado_fallback'],
            'tiempo_ms'      => $resultado['tiempo_ms'],
            'prompt'         => $prompt,
            'respuesta'      => $textoFinal,
            'error'          => $resultado['error'],
        ];

        if (method_exists($seccionObj, 'metadata')) {
            $metadata = $seccionObj->metadata($expediente);

            // Persistir metadata en meta (ej. descripcion_servicio)
            if (!empty($metadata)) {
                $exp->meta = array_merge($meta, $metadata);
                $exp->save();
            }

            $base = array_merge($base, $metadata);
        }

        return $base;
    }
}