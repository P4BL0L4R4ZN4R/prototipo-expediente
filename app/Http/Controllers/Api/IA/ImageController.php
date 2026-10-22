<?php

namespace App\Http\Controllers\Api\IA;

use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    public function probar()
    {
        $base = storage_path('app/plantillas');

        $plantilla = "{$base}/test.docx";
        $salida    = "{$base}/resultado.docx";
        $temp      = "{$base}/_tmp.docx";

        $imagenes = [
            'foto_1' => "{$base}/descarga.png",
            'imagen_2' => "{$base}/test02.jpg",
            'ilustracion_3' => "{$base}/test03.jpg",
        ];

        $textos = [
            'parrafo_1' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
            'parrafo_2' => 'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim.',
            'parrafo_3' => 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur.',
            'parrafo_4' => 'Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.',
            'parrafo_5' => 'Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur.',
            'parrafo_6' => 'Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur.',
            'parrafo_7' => 'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.',
            'parrafo_8' => 'Similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio.',
            'parrafo_9' => 'Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est.',
        ];

        // ─── 1. Insertar textos y primera imagen ──────────────────
        Log::info('[ImageController] Paso 1: textos + foto_1');
        $template = new TemplateProcessor($plantilla);

        foreach ($textos as $placeholder => $texto) {
            $template->setValue($placeholder, $texto);
        }

        $template->setImageValueAlt('foto_1', $imagenes['foto_1']);
        $template->saveAs($temp);

        // ─── 2. Recargar y insertar segunda imagen ────────────────
        Log::info('[ImageController] Paso 2: imagen_2');
        $template = new TemplateProcessor($temp);
        $template->setImageValueAlt('imagen_2', $imagenes['imagen_2']);
        $template->saveAs($temp);

        // ─── 3. Recargar y insertar tercera imagen ────────────────
        Log::info('[ImageController] Paso 3: ilustracion_3');
        $template = new TemplateProcessor($temp);
        $template->setImageValueAlt('ilustracion_3', $imagenes['ilustracion_3']);
        $template->saveAs($salida);

        // ─── 4. Limpiar temporal ──────────────────────────────────
        if (file_exists($temp)) {
            @unlink($temp);
        }

        Log::info('[ImageController] FIN', [
            'existe_salida' => file_exists($salida),
            'size_salida'   => file_exists($salida) ? filesize($salida) : null,
        ]);

        return response()->download($salida, 'resultado.docx')
                         ->deleteFileAfterSend(false);
    }
}