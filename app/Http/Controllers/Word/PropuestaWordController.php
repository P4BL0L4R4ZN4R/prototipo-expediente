<?php

namespace App\Http\Controllers\Word;

use App\Http\Controllers\Api\WordController;
// use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Log;
use App\Models\Expediente;

class PropuestaWordController extends WordController
{
    private const SECCIONES = [
        'texto_propuesta',
        'introduccion',
        'problematica',
        'objetivo_general',
        'objetivos_especificos',
        'metodologia',
    ];

    protected function plantilla(): string
    {
        return 'plantillas/1.PROPUESTA.docx';
    }

    protected function nombreArchivo(string $id): string
    {
        return "propuesta_{$id}";
    }

    protected function construir(
        TemplateProcessor $template,
        array $data,
        array $ia
    ): void 
    {

    
        $id = $this->expedienteId;

        // ---------------------------------------------------------
        // 1. Cargar meta.json (empresa, fecha)
        // ---------------------------------------------------------
        $exp = Expediente::find($id);
        $meta = $exp->meta ?? [];

        $empresaBrinda = $meta['empresa_brinda'] ?? 'SPRITZAC';
        $empresaRecibe = $meta['empresa_recibe'] ?? ($ia['cliente'] ?? '');
        $fecha         = $meta['fecha'] ?? ($data['calculos']['fecha_legal'] ?? date('Y-m-d'));

        // Formatear fecha bonita
        $fechaFmt = $this->formatearFecha($fecha);

        // ---------------------------------------------------------
        // 2. Inyectar datos simples (body, no tabla)
        // ---------------------------------------------------------
        $template->setValue('EMPRESA_BRINDA', $this->esc($empresaBrinda));
        $template->setValue('EMPRESA_RECIBE', $this->esc($empresaRecibe));
        $template->setValue('FECHA',          $this->esc($fechaFmt));

        $template->setValue('NOMBRE_PROGRAMA', $this->esc($ia['servicio'] ?? ''));

        // Hack para el placeholder roto "${EMPRESA RECIB" de la Page 1
        // PhpWord no lo va a encontrar, pero por si acaso:
        try {
            $template->setValue('EMPRESA RECIB', $this->esc($empresaRecibe));
        } catch (\Throwable $e) {
            // Silencioso: si no existe, no pasa nada
        }

        // ---------------------------------------------------------
        // 3. Cargar secciones IA
        // ---------------------------------------------------------
        $propuesta = $this->cargarPropuesta($id);

        // ---------------------------------------------------------
        // 4. Inyectar secciones respetando formato
        // ---------------------------------------------------------

        // Texto propuesta: está en body, no tabla
        $this->inyectarBloque(
            $template,
            'TEXTO_PROPUESTA',
            $propuesta['texto_propuesta']['texto'] ?? ''
        );

        // Introducción: celda de tabla
        $this->inyectarBloque(
            $template,
            'INTRODUCCION',
            $propuesta['introduccion']['texto'] ?? ''
        );

        // Problemática: celda de tabla
        $this->inyectarBloque(
            $template,
            'PROBLEMATICA',
            $propuesta['problematica']['texto'] ?? ''
        );

        // Objetivo general: celda de tabla
        $this->inyectarBloque(
            $template,
            'OBJETIVO_GENERAL',
            $propuesta['objetivo_general']['texto'] ?? ''
        );

        // Objetivos específicos: celda de tabla, viene lista numerada
        $this->inyectarLista(
            $template,
            'OBJETIVOS_ESPECIFICOS',
            $propuesta['objetivos_especificos']['texto'] ?? ''
        );

        // Metodología: celda de tabla, viene con **SUBTÍTULO.**
        $this->inyectarConNegritas(
            $template,
            'METODOLOGIA',
            $propuesta['metodologia']['texto'] ?? ''
        );
    }

    // =========================================================
    // CARGA DE DATOS
    // =========================================================

    private function cargarPropuesta(string $id): array
    {
        $exp = Expediente::find($id);

        $propuesta = [];

        if (!$exp) {
            foreach (self::SECCIONES as $sec) {
                $propuesta[$sec] = [];
            }
            return $propuesta;
        }

        $bd = $exp->propuesta_ia ?? [];

        foreach (self::SECCIONES as $sec) {
            $propuesta[$sec] = $bd[$sec] ?? [];
        }

        return $propuesta;
    }

    // private function leerJson(string $path): ?array
    // {
    //     if (!Storage::exists($path)) {
    //         return null;
    //     }

    //     $data = json_decode(Storage::get($path), true);

    //     return is_array($data) ? $data : null;
    // }

    // =========================================================
    // INYECCIÓN
    // =========================================================

    /**
     * Inyecta texto respetando saltos de línea. Funciona tanto en
     * body como en celdas de tabla.
     */
    private function inyectarBloque(
        TemplateProcessor $template,
        string $var,
        string $texto
    ): void {
        $texto = $this->limpiar($texto);

        if ($texto === '') {
            $template->setValue($var, '');
            return;
        }

        $run = new TextRun();
        $lineas = explode("\n", $texto);
        $primera = true;

        foreach ($lineas as $linea) {
            if (!$primera) {
                $run->addTextBreak();
            }
            $run->addText($linea);
            $primera = false;
        }

        $template->setComplexBlock($var, $run);
    }

    /**
     * Igual que inyectarBloque, pero además detecta líneas que
     * empiezan con "1. ", "2. ", etc. y las separa con un salto extra
     * para que se vean como lista.
     */
    private function inyectarLista(
        TemplateProcessor $template,
        string $var,
        string $texto
    ): void {
        $texto = $this->limpiar($texto);

        if ($texto === '') {
            $template->setValue($var, '');
            return;
        }

        // Separar por líneas y limpiar
        $lineas = array_values(array_filter(
            array_map('trim', explode("\n", $texto)),
            fn($l) => $l !== ''
        ));

        $run = new TextRun();
        $primera = true;

        foreach ($lineas as $linea) {
            if (!$primera) {
                $run->addTextBreak();
            }
            $run->addText($linea);
            $primera = false;
        }

        $template->setComplexBlock($var, $run);
    }

    /**
     * Inyecta texto donde **...** se convierte en negrita real,
     * y cada bloque (separado por línea vacía o salto simple) se
     * separa visualmente.
     */
    private function inyectarConNegritas(
        TemplateProcessor $template,
        string $var,
        string $texto
    ): void {
        $texto = $this->limpiar($texto);

        if ($texto === '') {
            $template->setValue($var, '');
            return;
        }

        $run = new TextRun();

        // Dividir por saltos de línea
        $lineas = explode("\n", $texto);
        $primera = true;

        foreach ($lineas as $linea) {
            $linea = rtrim($linea);

            if ($linea === '') {
                // Línea vacía → doble salto
                if (!$primera) {
                    $run->addTextBreak();
                }
                continue;
            }

            if (!$primera) {
                $run->addTextBreak();
            }

            // Parsear **negritas**
            $partes = preg_split('/(\*\*[^*]+\*\*)/u', $linea, -1, PREG_SPLIT_DELIM_CAPTURE);

            foreach ($partes as $parte) {
                if ($parte === '') continue;

                if (preg_match('/^\*\*(.+?)\*\*$/u', $parte, $m)) {
                    $run->addText($m[1], ['bold' => true]);
                } else {
                    // Quitar asteriscos sueltos
                    $limpio = str_replace('*', '', $parte);
                    if ($limpio !== '') {
                        $run->addText($limpio);
                    }
                }
            }

            $primera = false;
        }

        $template->setComplexBlock($var, $run);
    }

    // =========================================================
    // UTILIDADES
    // =========================================================

    private function limpiar(string $texto): string
    {
        $texto = trim($texto);

        // Quitar comillas envolventes
        if (str_starts_with($texto, '"') && str_ends_with($texto, '"')) {
            $texto = trim(substr($texto, 1, -1));
        }

        // Normalizar saltos
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);

        // Colapsar 3+ saltos a 2
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto);

        return trim($texto);
    }

    private function esc(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function formatearFecha(string $fecha): string
    {
        try {
            return \Carbon\Carbon::parse($fecha)->format('d/m/Y');
        } catch (\Throwable $e) {
            return $fecha;
        }
    }
}