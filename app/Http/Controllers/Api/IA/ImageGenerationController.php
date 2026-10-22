<?php

namespace App\Http\Controllers\Api\IA;

use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageGenerationController extends Controller
{

    // =========================================================
    // SERVICIO DE PROMPTS (Director de Arte)
    // =========================================================
    // private function construirPrompts(string $tema, string $area): array
    // {
    //     return [
    //         // Estilo 1: Geométrico Plano (Minimalista)
    //         "Una ilustración conceptual flat design sobre el tema '{$tema}' en el área de '{$area}'. Utiliza formas geométricas puras y siluetas abstractas. Paleta de colores fríos (azul y gris). Composición centrada. El fondo debe ser una superficie limpia, lisa y completamente en blanco, con amplio espacio negativo. Solo formas abstractas, sin marcas ni símbolos.",
            
    //         // Estilo 2: 3D Suave (Plastilina)
    //         "Una ilustración conceptual 3D isométrica sobre el tema '{$tema}' en el área de '{$area}'. Utiliza bloques y esferas con textura suave (plastilina). Colores vibrantes (naranja y azul). Composición equilibrada. Fondo limpio, minimalista y despejado, sin elementos decorativos. Solo objetos abstractos y volúmenes.",
            
    //         // Estilo 3: Flat Corporativo (Corporate Memphis)
    //         // Reemplazamos la acuarela por este estilo limpio y profesional
    //         "Una ilustración flat corporativa moderna (estilo Corporate Memphis) sobre el tema '{$tema}' en el área de '{$area}'. Utiliza figuras humanas estilizadas y abstractas interactuando con formas geométricas simples. Colores planos y vibrantes (morado, verde y amarillo). Composición dinámica. Fondo limpio y vacío, sin texto ni detalles innecesarios. Solo figuras y formas definidas."
    //     ];
    // }



    private function construirPromptsAleatorios(string $tema, string $area, int $cantidad): array
    {
        // 1. BANCO DE ESTILOS ARTÍSTICOS (10 opciones)
        $estilos = [
            "estilo flat design minimalista, con formas geométricas puras, líneas limpias y trazos vectoriales.",
            "estilo 3D isométrico suave, con textura de plastilina, volúmenes definidos y sombras suaves.",
            "estilo flat corporativo moderno (Corporate Memphis), con figuras humanas estilizadas y abstractas interactuando con formas geométricas.",
            "estilo de ilustración vectorial lineal (line-art), con trazos gruesos de color oscuro y rellenos planos.",
            "estilo isométrico técnico, como un plano de ingeniería moderno, con líneas precisas y detalles de luz neón.",
            "estilo flat 2.0, con degradados sutiles, sombras suaves y una estética de aplicación móvil moderna.",
            "estilo de ilustración geométrica abstracta, con superposición de círculos, triángulos y cuadrados.",
            "estilo pop-art moderno, con colores contrastantes, contornos gruesos y puntos de semitono (halftone).",
            "estilo de ilustración conceptual surrealista, con objetos flotantes y escalas exageradas.",
            "estilo futurista limpio, con líneas de luz, interfaces holográficas y formas cristalinas."
        ];

        // 2. BANCO DE PALETAS DE COLORES (10 opciones)
        $paletas = [
            "paleta de colores fríos (azul marino, celeste y blanco).",
            "paleta de colores vibrantes (naranja, amarillo y azul eléctrico).",
            "paleta monocromática (diferentes tonos de morado).",
            "paleta de colores pastel (verde menta, rosa suave y lila).",
            "paleta de alto contraste (negro, blanco y un toque de rojo).",
            "paleta de colores tecnológicos (cian, azul oscuro y gris).",
            "paleta de colores cálidos (terracota, mostaza y crema).",
            "paleta de colores corporativos (azul marino, verde oliva y dorado).",
            "paleta de colores neón (verde lima, magenta y cian).",
            "paleta de colores tierra (marrón, beige y verde bosque)."
        ];

        // 3. BANCO DE COMPOSICIONES (5 opciones)
        $composiciones = [
            "Composición centrada, con un elemento principal grande.",
            "Composición diagonal, que sugiera movimiento y dinamismo.",
            "Composición simétrica, con elementos equilibrados a ambos lados.",
            "Composición en primer plano, con gran detalle en el objeto central.",
            "Composición de paisaje amplio, con el concepto integrado en el entorno."
        ];

        $prompts = [];

        for ($i = 0; $i < $cantidad; $i++) {
            // Seleccionamos un elemento aleatorio de cada banco
            $estiloElegido = $estilos[array_rand($estilos)];
            $paletaElegida = $paletas[array_rand($paletas)];
            $composicionElegida = $composiciones[array_rand($composiciones)];

            // Construimos el prompt final
            $prompts[] = "Una ilustración conceptual sobre el tema '{$tema}' en el área de '{$area}'. " .
                         "Utiliza un {$estiloElegido} " .
                         "Aplica una {$paletaElegida} " .
                         "{$composicionElegida} " .
                         "El fondo debe ser limpio, vacío y despejado, con amplio espacio negativo. " .
                         "Solo formas visuales puras y abstractas.";
        }

        return $prompts;
    }


        // =========================================================
    // ENDPOINT PRINCIPAL DE PRUEBA
    // =========================================================


    public function testGeneracion($id, Request $request)
    {
        set_time_limit(0);
        $startTotal = microtime(true);

        // 1. Recibir la cantidad de imágenes (por defecto 3, máximo 10)
        $cantidad = (int) $request->query('n', 3);
        if ($cantidad < 1) $cantidad = 1;
        if ($cantidad > 10) $cantidad = 10;

        // 2. Buscar expediente
        $exp = Expediente::find($id);
        if (!$exp) {
            return response()->json(['error' => 'Expediente no encontrado'], 404);
        }

        $meta = $exp->meta ?? [];
        $area = $meta['area'] ?? null;
        $tema = $meta['tema'] ?? null;

        if (empty($area) || empty($tema)) {
            return response()->json(['error' => 'Faltan "tema" o "area" en el expediente'], 422);
        }

        // 3. Construir los prompts aleatorios
        $variaciones = $this->construirPromptsAleatorios($tema, $area, $cantidad);
        $resultados = [];

        // 4. Iterar y generar
        foreach ($variaciones as $index => $prompt) {
            $start = microtime(true);
            
            try {
                $imagenBinaria = $this->generarConCloudflareSDXL($prompt);

                $temaLimpio = Str::slug($tema);
                $nombreArchivo = "expedientes/{$id}/graficos/{$temaLimpio}_variacion_" . ($index + 1) . '.png';
                Storage::disk('local')->put($nombreArchivo, $imagenBinaria);

                $tiempo = round(microtime(true) - $start, 2);
                
                $resultados[] = [
                    'variacion' => $index + 1,
                    'ok' => true,
                    'tiempo' => "{$tiempo} seg",
                    'archivo' => $nombreArchivo,
                    'prompt_usado' => $prompt // Opcional: para que veas qué estilo tocó
                ];

            } catch (\Exception $e) {
                $resultados[] = [
                    'variacion' => $index + 1,
                    'ok' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $tiempoTotal = round(microtime(true) - $startTotal, 2);

        return response()->json([
            'ok' => true,
            'tema' => $tema,
            'area' => $area,
            'cantidad_solicitada' => $cantidad,
            'tiempo_total' => "{$tiempoTotal} segundos",
            'resultados' => $resultados
        ]);
    }



    // =========================================================
    // PROVEEDOR DE IA (Cloudflare SDXL)
    // =========================================================
    private function generarConCloudflareSDXL(string $prompt): string
    {
        $accountId = env('CLOUDFLARE_ACCOUNT_ID');
        $apiToken  = env('CLOUDFLARE_API_TOKEN');
        
        // Volvemos al modelo rápido y limpio
        $modelo = '@cf/black-forest-labs/flux-1-schnell';

        if (empty($accountId)) {
            throw new \Exception('Falta CLOUDFLARE_ACCOUNT_ID en .env');
        }

        $response = Http::withToken($apiToken)
            ->timeout(30)
            ->post("https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/{$modelo}", [
                'prompt' => $prompt,
                'steps'  => 8, //  pasos para velocidad
            ]);

        if ($response->failed()) {
            throw new \Exception('Error en Cloudflare: ' . json_encode($response->json()));
        }

        $data = $response->json();

        if (!isset($data['result']['image'])) {
            throw new \Exception('Cloudflare no devolvió la imagen esperada: ' . json_encode($data));
        }

        return base64_decode($data['result']['image']);
    }


}