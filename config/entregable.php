<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Campos que el entregable lee de la PROPUESTA
    |--------------------------------------------------------------------------
    |
    | clave = nombre en el contexto del prompt (lo que usa EntregablePromptBuilder)
    | valor = nombre del campo en el consolidado de propuesta
    |
    | Para añadir un campo nuevo: agrega una línea aquí + usa {{CLAVE}} en el
    | prompt del entregable. No toques EntregableService.
    |
    */
    'extraer' => [
        'objetivo_general'       => 'objetivo_general',
        'objetivos_especificos'  => 'objetivos_especificos',
        'problematica_propuesta' => 'problematica',
    ],
];