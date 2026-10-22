<?php

namespace App\Services\Word;

use App\Models\Expediente;
use App\Services\Storage\ExpedienteTextoStorage;
use Carbon\Carbon;

class WordFieldResolver
{
    public function __construct(
        private ExpedienteTextoStorage $storage,
    ) {}

    /**
     * Resuelve el valor de un campo según su configuración del registry.
     *
     * Config esperada:
     * [
     *     'origen'    => 'propuesta|entregable|expediente|calc|contexto',
     *     'campo'     => 'nombre_del_campo',
     *     'metodo'    => 'bloque|lista|conNegritas|valor',  // lo usa el dispatcher
     *     'transform' => 'metodoDeCalc',                    // opcional, solo para origen=calc
     *     'default'   => 'texto por defecto',               // opcional
     * ]
     */
    public function resolver(ExpedienteContext $ctx, array $config): string
    {
        $origen    = $config['origen']    ?? 'contexto';
        $campo     = $config['campo']     ?? null;
        $default   = $config['default']   ?? '';
        $transform = $config['transform'] ?? null;

        return match ($origen) {
            'propuesta'  => $this->desdePropuesta($ctx, $campo, $default),
            'entregable' => $this->desdeEntregable($ctx, $campo, $default),
            'expediente' => $this->desdeExpediente($ctx, $campo, $default),
            'calc'       => $this->desdeCalc($ctx, $campo, $default, $transform),
            'contexto'   => $this->desdeContexto($ctx, $campo, $default),
            default      => $default,
        };
    }

    // =========================================================
    // ORÍGENES
    // =========================================================

    /**
     * Lee de una sección de la propuesta (archivo .txt vía ExpedienteTextoStorage).
     * El campo corresponde a una de SECCIONES_PROPUESTA:
     *   texto_propuesta, introduccion, problematica,
     *   objetivo_general, objetivos_especificos, metodologia
     */
    private function desdePropuesta(ExpedienteContext $ctx, ?string $campo, string $default): string
    {
        if (!$campo) return $default;

        $seccion = $this->storage->obtenerSeccion($ctx->id, 'propuesta', $campo);

        return $seccion['texto'] ?? $default;
    }

    /**
     * Lee de una sección del entregable (archivo .txt vía ExpedienteTextoStorage).
     * El campo corresponde a una de SECCIONES_ENTREGABLE:
     *   introduccion, problematica, desarrollo_conceptos,
     *   finalizacion_servicio, retrospectiva
     */
    private function desdeEntregable(ExpedienteContext $ctx, ?string $campo, string $default): string
    {
        if (!$campo) return $default;

        $seccion = $this->storage->obtenerSeccion($ctx->id, 'entregable', $campo);

        return $seccion['texto'] ?? $default;
    }

    /**
     * Lee una columna directa de la tabla `expedientes`.
     * Ejemplos: direccion, estado_expediente, fecha_propuesta, fecha_entregable.
     */
    private function desdeExpediente(ExpedienteContext $ctx, ?string $campo, string $default): string
    {
        if (!$campo) return $default;

        $exp = Expediente::find($ctx->id);

        if (!$exp) return $default;

        $valor = $exp->{$campo} ?? null;

        if ($valor === null) return $default;

        // Si es Carbon (por casts de fecha_propuesta / fecha_entregable)
        if ($valor instanceof Carbon) {
            return $valor->format('d/m/Y');
        }

        return (string) $valor;
    }

    /**
     * Lee de CalculosExpediente.
     *
     * Si el valor es Carbon y no hay transform, se formatea como 'd/m/Y'.
     * Si hay transform, se llama al método del transform con el valor crudo.
     *
     * Ejemplo de config:
     *   ['origen' => 'calc', 'campo' => 'fechaPropuesta', 'transform' => 'fechaDocumento']
     *   → $ctx->calc->fechaDocumento($ctx->calc->fechaPropuesta())
     */
    private function desdeCalc(
        ExpedienteContext $ctx,
        ?string $campo,
        string $default,
        ?string $transform
    ): string {
        if (!$campo || !method_exists($ctx->calc, $campo)) {
            return $default;
        }

        $valor = $ctx->calc->{$campo}();

        if ($valor === null || $valor === '') {
            return $default;
        }

        if ($transform !== null && method_exists($ctx->calc, $transform)) {
            $valor = $ctx->calc->{$transform}($valor);
        }

        // A partir de aquí, el valor puede ser Carbon, int, string, etc.
        if ($valor instanceof Carbon) {
            return $valor->format('d/m/Y');
        }

        return (string) $valor;
    }

    /**
     * Lee de un método de ExpedienteContext (sin argumentos).
     * Ejemplos: ciudad, servicio, empresaBrinda, empresaRecibe, conceptosIA.
     *
     * Nota: conceptosIA() devuelve array, se castea a string vacío si no es escalar.
     */
    private function desdeContexto(ExpedienteContext $ctx, ?string $campo, string $default): string
    {
        if (!$campo || !method_exists($ctx, $campo)) {
            return $default;
        }

        $valor = $ctx->{$campo}();

        if ($valor === null) return $default;

        if (is_array($valor)) return $default;

        return (string) $valor;
    }
}