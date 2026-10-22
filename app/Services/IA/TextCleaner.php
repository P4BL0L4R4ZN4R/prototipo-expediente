<?php

namespace App\Services\IA;

class TextCleaner
{
    public function limpiar(string $texto): string
    {
        $texto = str_replace(
            ['\\n', '\\r', '\\t', '\\"', "\\'", '\\*'],
            ["\n",  "\r",  "\t",  '"',   "'",   '*'],
            $texto
        );

        $texto = trim($texto);

        if (str_starts_with($texto, '"') && str_ends_with($texto, '"')) {
            $texto = trim(substr($texto, 1, -1));
        }

        $texto = preg_replace('/[ \t]+/', ' ', $texto);

        return trim($texto);
    }
}