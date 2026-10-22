<?php

namespace App\Services\Word;

/**
 * ============================================================================
 *  WordFieldRegistry — Configuración de placeholders de plantillas Word
 * ============================================================================
 *
 *  ÚNICO lugar donde se declara cómo llenar cada {{PLACEHOLDER}} de las
 *  plantillas .docx. Para agregar un campo nuevo, casi siempre basta con
 *  una línea aquí y poner {{PLACEHOLDER}} en la plantilla.
 *
 *  No se tocan controladores, resolver ni dispatcher.
 *
 * ----------------------------------------------------------------------------
 *  ESTRUCTURA
 * ----------------------------------------------------------------------------
 *
 *      'PLACEHOLDER' => [
 *          'origen'    => '...',   // ¿de dónde sale el dato?
 *          'campo'     => '...',   // ¿cuál dato específicamente?
 *          'metodo'    => '...',   // ¿cómo se inyecta?
 *          'transform' => '...',   // (opcional, solo calc) transformación
 *          'default'   => '...',   // (opcional) valor si no hay dato
 *      ],
 *
 * ----------------------------------------------------------------------------
 *  ORIGEN — ¿De dónde sale el dato?
 * ----------------------------------------------------------------------------
 *
 *      'propuesta'   → .txt de propuesta (texto_propuesta, introduccion,
 *                      problematica, objetivo_general, objetivos_especificos,
 *                      metodologia)
 *      'entregable'  → .txt de entregable (introduccion, problematica,
 *                      desarrollo_conceptos, finalizacion_servicio,
 *                      retrospectiva)
 *      'expediente'  → columna de tabla `expedientes` (direccion,
 *                      estado_expediente, telefono, etc.)
 *      'calc'        → método de CalculosExpediente (fechaPropuesta,
 *                      periodoServicio, fechaCalendario, etc.)
 *      'contexto'    → método de ExpedienteContext (ciudad, servicio,
 *                      empresaBrinda, empresaRecibe, etc.)
 *
 * ----------------------------------------------------------------------------
 *  METODO — ¿Cómo se inyecta en el Word?
 * ----------------------------------------------------------------------------
 *
 *      'valor'       → Reemplazo plano (CP, RFC, fechas cortas, direcciones)
 *      'bloque'      → Párrafos con saltos de línea (introducciones, textos largos)
 *      'lista'       → Lista numerada con viñetas (como objetivos específicos)
 *      'conNegritas' → Markdown **negritas** convertidas a bold real (metodología)
 *
 * ----------------------------------------------------------------------------
 *  TRANSFORM — Opcional, solo para origen 'calc'
 * ----------------------------------------------------------------------------
 *
 *  Convierte el valor crudo antes de inyectarlo. Caso típico: pasar un
 *  Carbon por fechaDocumento() para formatearlo como string largo.
 *
 * ----------------------------------------------------------------------------
 *  AGREGAR UN CAMPO NUEVO
 * ----------------------------------------------------------------------------
 *
 *  Caso 1 — El dato ya existe en un origen soportado:
 *      Agrega una línea aquí + {{PLACEHOLDER}} en el .docx. Listo.
 *
 *  Caso 2 — Necesita transformación custom:
 *      Agrega el método a ExpedienteContext + entrada aquí + placeholder.
 *
 *  Caso 3 — El dato no existe en ningún lado:
 *      Migración + $fillable + Excel-Controller::update() + entrada aquí + placeholder.
 *
 * ----------------------------------------------------------------------------
 *  NOTAS
 * ----------------------------------------------------------------------------
 *
 *  - EMPRESA_BRINDA, EMPRESA_RECIBE, NOMBRE_PROGRAMA y TITULO_1 NO van aquí.
 *    Los inyecta setEncabezadoEmpresa() porque TITULO_1 varía por documento.
 *
 *  - Si el placeholder no existe en la plantilla, PhpWord lo ignora
 *    silenciosamente. Si existe pero no está aquí, queda como {{TEXTO}}
 *    literal en el Word generado.
 *
 * ============================================================================
 */
class WordFieldRegistry
{
    /** ENTREGABLE — 5.ENTREGABLE.docx */
    public static function entregable(): array
    {
        return [
            'INTRODUCCION'          => ['origen' => 'entregable', 'campo' => 'introduccion',         'metodo' => 'bloque'],
            'PROBLEMATICA'          => ['origen' => 'entregable', 'campo' => 'problematica',         'metodo' => 'bloque'],
            'DESARROLLO_CONCEPTOS'  => ['origen' => 'entregable', 'campo' => 'desarrollo_conceptos', 'metodo' => 'conNegritas'],
            'RETROSPECTIVA'         => ['origen' => 'entregable', 'campo' => 'retrospectiva',        'metodo' => 'bloque'],
            'OBJETIVO_GENERAL'      => ['origen' => 'propuesta',  'campo' => 'objetivo_general',     'metodo' => 'bloque'],
            'OBJETIVOS_ESPECIFICOS' => ['origen' => 'propuesta',  'campo' => 'objetivos_especificos','metodo' => 'lista'],
            'FECHA'                 => ['origen' => 'calc',       'campo' => 'periodoServicio',      'metodo' => 'valor'],
            'DIRECCION_CLIENTE'     => ['origen' => 'expediente', 'campo' => 'direccion',            'metodo' => 'valor'],
            
        ];
    }

    /** PROPUESTA — 1.PROPUESTA.docx */
    public static function propuesta(): array
    {
        return [
            'TEXTO_PROPUESTA'       => ['origen' => 'propuesta', 'campo' => 'texto_propuesta',       'metodo' => 'bloque'],
            'INTRODUCCION'          => ['origen' => 'propuesta', 'campo' => 'introduccion',          'metodo' => 'bloque'],
            'PROBLEMATICA'          => ['origen' => 'propuesta', 'campo' => 'problematica',          'metodo' => 'bloque'],
            'OBJETIVO_GENERAL'      => ['origen' => 'propuesta', 'campo' => 'objetivo_general',      'metodo' => 'bloque'],
            'OBJETIVOS_ESPECIFICOS' => ['origen' => 'propuesta', 'campo' => 'objetivos_especificos', 'metodo' => 'lista'],
            'METODOLOGIA'           => ['origen' => 'propuesta', 'campo' => 'metodologia',           'metodo' => 'conNegritas'],
            'FECHA'                 => ['origen' => 'calc',      'campo' => 'fechaPropuesta',        'metodo' => 'valor', 'transform' => 'fechaDocumento'],
            'DIRECCION_CLIENTE'     => ['origen' => 'expediente','campo' => 'direccion',             'metodo' => 'valor'],
        ];
    }

    /** RESUMEN EJECUTIVO — 6.RESUMEN-EJECUTIVO.docx (CIUDAD y LISTA_PROCESOS van custom) */
    public static function resumen(): array
    {
        return [
            'OBJETIVO'          => ['origen' => 'propuesta',  'campo' => 'objetivo_general', 'metodo' => 'valor'],
            'RANGO_FECHA'       => ['origen' => 'calc',       'campo' => 'periodoServicio',  'metodo' => 'valor'],
            'DIRECCION_CLIENTE' => ['origen' => 'expediente', 'campo' => 'direccion',        'metodo' => 'valor'],
        ];
    }

    /** ACUSE — 7.ACUSE.docx */
    public static function acuse(): array
    {
        return [
            'DIRECCION_CLIENTE' => ['origen' => 'expediente', 'campo' => 'direccion', 'metodo' => 'valor'],
        ];
    }

    /** CALENDARIO — 4.CALENDARIO-DE-TRABAJO.docx (TABLA_CALENDARIO va custom) */
    public static function calendario(): array
    {
        return [
            'FECHA'             => ['origen' => 'calc',       'campo' => 'fechaCalendario', 'metodo' => 'valor', 'transform' => 'fechaDocumento'],
            'DIRECCION_CLIENTE' => ['origen' => 'expediente', 'campo' => 'direccion',       'metodo' => 'valor'],
        ];
    }

    /**
     * COTIZACIÓN — 2.COTIZACION-INICIAL / 3.COTIZACION-FINAL / 4.COTIZACION-FINAL-FECHAS
     * $tipo: 'inicial' | 'final' | 'final_fechas'
     */
    public static function cotizacion(string $tipo = 'inicial'): array
    {
        $campoFecha = match ($tipo) {
            'final', 'final_fechas' => 'fechaCotizacionFinal',
            default                 => 'fechaCotizacionInicial',
        };

        return [
            'FECHA'             => ['origen' => 'calc',       'campo' => $campoFecha, 'metodo' => 'valor', 'transform' => 'fechaDocumento'],
            'SERVICIO'          => ['origen' => 'contexto',   'campo' => 'servicio',  'metodo' => 'valor'],
            'DIRECCION_CLIENTE' => ['origen' => 'expediente', 'campo' => 'direccion', 'metodo' => 'valor'],
        ];
    }
}