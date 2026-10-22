<?php

namespace App\Services\Word;

class TextNormalizer
{
    /**
     * Normaliza el texto: limpia markdown, unifica saltos
     * y colapsa múltiples saltos a uno solo.
     */
    public function normalizar(string $texto): string
    {
        if ($texto === '') {
            return '';
        }

        $texto = trim($texto);
        $texto = preg_replace('/^```(?:\w+)?\s*|\s*```$/m', '', $texto);
        $texto = trim($texto);
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace("/\n{2,}/", "\n", $texto);

        return trim($texto);
    }
}