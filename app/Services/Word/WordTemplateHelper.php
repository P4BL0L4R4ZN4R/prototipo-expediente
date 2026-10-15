<?php

namespace App\Services\Word;

use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;

class WordTemplateHelper
{
    public function bloque(TemplateProcessor $t, string $var, string $texto): void
    {
        $texto = $this->limpiar($texto);
        if ($texto === '') { $t->setValue($var, ''); return; }

        $run = new TextRun();
        $lineas = explode("\n", $texto);
        $primera = true;
        foreach ($lineas as $linea) {
            if (!$primera) $run->addTextBreak();
            $run->addText($linea);
            $primera = false;
        }
        $t->setComplexBlock($var, $run);
    }

    public function lista(TemplateProcessor $t, string $var, string $texto): void
    {
        $texto = $this->limpiar($texto);
        if ($texto === '') { $t->setValue($var, ''); return; }

        $lineas = array_values(array_filter(
            array_map('trim', explode("\n", $texto)),
            fn($l) => $l !== ''
        ));

        $xml = '';
        foreach ($lineas as $linea) {
            // Quitar numeración existente ("1. ", "2. ") porque Word la pone sola
            $lineaLimpia = preg_replace('/^\d+\.\s*/', '', $linea);

            $xml .= '<w:p>'
                . '<w:pPr>'
                . '<w:pStyle w:val="ListParagraph"/>'
                . '<w:numPr>'
                . '<w:ilvl w:val="0"/>'
                . '<w:numId w:val="1"/>'
                . '</w:numPr>'
                . '</w:pPr>'
                . '<w:r><w:t xml:space="preserve">' . htmlspecialchars($lineaLimpia) . '</w:t></w:r>'
                . '</w:p>';
        }

        $t->setValue($var, $xml);
    }
    public function conNegritas(TemplateProcessor $t, string $var, string $texto): void
    {
        $texto = $this->limpiar($texto);
        if ($texto === '') { $t->setValue($var, ''); return; }

        $run = new TextRun();
        $lineas = explode("\n", $texto);
        $primera = true;

        foreach ($lineas as $linea) {
            $linea = rtrim($linea);
            if ($linea === '') {
                if (!$primera) $run->addTextBreak();
                continue;
            }
            if (!$primera) $run->addTextBreak();

            $partes = preg_split('/(\*\*[^*]+\*\*)/u', $linea, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($partes as $parte) {
                if ($parte === '') continue;
                if (preg_match('/^\*\*(.+?)\*\*$/u', $parte, $m)) {
                    $run->addText($m[1], ['bold' => true]);
                } else {
                    $limpio = str_replace('*', '', $parte);
                    if ($limpio !== '') $run->addText($limpio);
                }
            }
            $primera = false;
        }
        $t->setComplexBlock($var, $run);
    }

    public function limpiar(string $texto): string
    {
        $texto = trim($texto);
        if (str_starts_with($texto, '"') && str_ends_with($texto, '"')) {
            $texto = trim(substr($texto, 1, -1));
        }
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto);
        return trim($texto);
    }

    public function esc(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}