<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Secciones de PROPUESTA
    |--------------------------------------------------------------------------
    | Cada clave es el nombre de la sección (usado para el archivo .txt y la URL).
    | El valor es el método de PropuestaController que la genera.
    |
    | OJO: si añades una sección aquí, añádela también en:
    |   - PropuestaController (método público)
    |   - PropuestaPrompts (plantilla del prompt)
    |   - excelApi.ts (SECCIONES_PROPUESTA)
    */
    'propuesta' => [
        'texto_propuesta'       => 'textoPropuesta',
        'introduccion'          => 'introduccion',
        'problematica'          => 'problematica',
        'objetivo_general'      => 'objetivoGeneral',
        'objetivos_especificos' => 'objetivosEspecificos',
        'metodologia'           => 'metodologia',
    ],

    /*
    |--------------------------------------------------------------------------
    | Secciones de ENTREGABLE
    |--------------------------------------------------------------------------
    | Los nombres pueden coincidir con los de propuesta, pero son secciones
    | distintas (contenido y generación distintos).
    |
    | OJO: si añades una sección aquí, añádela también en:
    |   - EntregableController (método público)
    |   - EntregablePromptBuilder (plantilla del prompt)
    |   - excelApi.ts (SECCIONES_ENTREGABLE)
    */
    'entregable' => [
        'introduccion'           => 'introduccion',
        'problematica'           => 'problematica',
        'desarrollo_conceptos'   => 'desarrolloConceptos',
        'finalizacion_servicio'  => 'finalizacionServicio',
        'retrospectiva'          => 'retrospectiva',
    ],
];