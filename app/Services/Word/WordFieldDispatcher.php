<?php

namespace App\Services\Word;

use InvalidArgumentException;
use PhpOffice\PhpWord\TemplateProcessor;

class WordFieldDispatcher
{
    public function __construct(
        private WordTemplateService $tpl,
    ) {}

    /**
     * Inyecta un valor en la plantilla según el método indicado.
     *
     * Métodos soportados:
     *   - 'bloque'      → párrafos con saltos de línea reales
     *   - 'lista'       → lista numerada con viñetas de Word
     *   - 'conNegritas' → párrafos con **negritas** convertidas a bold real
     *   - 'valor'       → reemplazo plano (una línea)
     *
     * Métodos reservados para futuro:
     *   - 'imagen'      → insertar imagen
     *   - 'tabla'       → insertar tabla programática
     */
    public function inyectar(
        TemplateProcessor $template,
        string $placeholder,
        string $texto,
        string $metodo
    ): void {
        match ($metodo) {
            'bloque'      => $this->tpl->bloque($template, $placeholder, $texto),
            'lista'       => $this->tpl->lista($template, $placeholder, $texto),
            'conNegritas' => $this->tpl->conNegritas($template, $placeholder, $texto),
            'valor'       => $template->setValue($placeholder, $texto),
            default       => throw new InvalidArgumentException(
                "Método de inyección desconocido: '{$metodo}' para el placeholder '{$placeholder}'."
            ),
        };
    }
}