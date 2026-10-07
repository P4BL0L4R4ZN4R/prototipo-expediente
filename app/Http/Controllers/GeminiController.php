<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiController extends Controller
{
    /**
     * Llama a Gemini y, si falla, intenta con OpenAI.
     * Devuelve texto + metadata + qué proveedor respondió.
     */
    public function generar(string $prompt): array
    {
        $resultadoOpenAi = $this->llamarOpenAi($prompt);

        if ($resultadoOpenAi['ok']) {
            return [
                'ok'             => true,
                'texto'          => $this->limpiarRespuesta($resultadoOpenAi['texto']),
                'error'          => null,
                'proveedor'      => 'openai',
                'modelo'         => $resultadoOpenAi['modelo'],
                'usado_fallback' => false,
                'error_gemini'   => null,
                'tiempo_ms'      => $resultadoOpenAi['tiempo_ms'],
            ];
        }

        // OpenAI falló → intentar Gemini
        Log::warning('OpenAI · falló, intentando Gemini', [
            'error_openai' => $resultadoOpenAi['error'],
        ]);

        $resultadoGemini = $this->llamarGemini($prompt);

        if ($resultadoGemini['ok']) {
            return [
                'ok'             => true,
                'texto'          => $this->limpiarRespuesta($resultadoGemini['texto']),
                'error'          => null,
                'proveedor'      => 'gemini',
                'modelo'         => $resultadoGemini['modelo'],
                'usado_fallback' => true,
                'error_gemini'   => $resultadoOpenAi['error'],
                'tiempo_ms'      => $resultadoOpenAi['tiempo_ms'] + $resultadoGemini['tiempo_ms'],
            ];
        }

        // Ambos fallaron
        return [
            'ok'             => false,
            'texto'          => null,
            'error'          => 'OpenAI: ' . $resultadoOpenAi['error'] . ' | Gemini: ' . $resultadoGemini['error'],
            'proveedor'      => null,
            'modelo'         => null,
            'usado_fallback' => false,
            'error_gemini'   => $resultadoOpenAi['error'],
            'tiempo_ms'      => $resultadoOpenAi['tiempo_ms'] + $resultadoGemini['tiempo_ms'],
        ];
    }

    /**
     * Llamada específica a Gemini.
     */
    private function llamarGemini(string $prompt): array
    {
        $key   = config('services.gemini.key');
        $model = config('services.gemini.model');

        $inicio = microtime(true);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
                ->timeout(60)
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}",
                    [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature'     => 0.7,
                            'maxOutputTokens' => 10000,
                        ],
                    ]
                );

            $tiempoMs = (int) round((microtime(true) - $inicio) * 1000);

            if ($response->failed()) {
                Log::error('Gemini · API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'modelo' => $model,
                ]);

                return [
                    'ok'        => false,
                    'texto'     => null,
                    'error'     => $response->json('error.message') ?? 'Error al llamar a Gemini',
                    'modelo'    => $model,
                    'tiempo_ms' => $tiempoMs,
                ];
            }

            $texto = $response->json('candidates.0.content.parts.0.text') ?? '';

            if (trim($texto) === '') {
                return [
                    'ok'        => false,
                    'texto'     => null,
                    'error'     => 'Gemini devolvió respuesta vacía',
                    'modelo'    => $model,
                    'tiempo_ms' => $tiempoMs,
                ];
            }

            return [
                'ok'        => true,
                'texto'     => $texto,
                'error'     => null,
                'modelo'    => $model,
                'tiempo_ms' => $tiempoMs,
            ];

        } catch (\Throwable $e) {
            $tiempoMs = (int) round((microtime(true) - $inicio) * 1000);

            Log::error('Gemini · exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return [
                'ok'        => false,
                'texto'     => null,
                'error'     => $e->getMessage(),
                'modelo'    => $model,
                'tiempo_ms' => $tiempoMs,
            ];
        }
    }

    /**
     * Llamada específica a OpenAI.
     */
    private function llamarOpenAi(string $prompt): array
    {
        $key   = config('services.openai.key');
        $model = config('services.openai.model');

        $inicio = microtime(true);

        try {
            $response = Http::withToken($key)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->timeout(60)
                ->post(
                    'https://api.openai.com/v1/chat/completions',
                    [
                        'model'    => $model,
                        'messages' => [
                            [
                                'role'    => 'user',
                                'content' => $prompt,
                            ],
                        ],
                        'temperature' => 0.7,
                    ]
                );

            $tiempoMs = (int) round((microtime(true) - $inicio) * 1000);

            if ($response->failed()) {
                Log::error('OpenAI · API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'modelo' => $model,
                ]);

                return [
                    'ok'        => false,
                    'texto'     => null,
                    'error'     => $response->json('error.message') ?? 'Error al llamar a OpenAI',
                    'modelo'    => $model,
                    'tiempo_ms' => $tiempoMs,
                ];
            }

            $texto = $response->json('choices.0.message.content') ?? '';

            if (trim($texto) === '') {
                return [
                    'ok'        => false,
                    'texto'     => null,
                    'error'     => 'OpenAI devolvió respuesta vacía',
                    'modelo'    => $model,
                    'tiempo_ms' => $tiempoMs,
                ];
            }

            return [
                'ok'        => true,
                'texto'     => $texto,
                'error'     => null,
                'modelo'    => $model,
                'tiempo_ms' => $tiempoMs,
            ];

        } catch (\Throwable $e) {
            $tiempoMs = (int) round((microtime(true) - $inicio) * 1000);

            Log::error('OpenAI · exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return [
                'ok'        => false,
                'texto'     => null,
                'error'     => $e->getMessage(),
                'modelo'    => $model,
                'tiempo_ms' => $tiempoMs,
            ];
        }
    }

    /**
     * Endpoint de prueba manual.
     */
    public function test(Request $request)
    {
        $request->validate([
            'input' => 'required|string|max:5000',
        ]);

        $resultado = $this->generar($request->input('input'));

        if (!$resultado['ok']) {
            return response()->json([
                'error' => $resultado['error'],
            ], 502);
        }

        return response()->json([
            'texto'          => $resultado['texto'],
            'proveedor'      => $resultado['proveedor'],
            'modelo'         => $resultado['modelo'],
            'usado_fallback' => $resultado['usado_fallback'],
            'tiempo_ms'      => $resultado['tiempo_ms'],
        ]);
    }


    private function limpiarRespuesta(string $texto): string
    {
        // 1. Desescapar secuencias literales de JSON/markdown
        $texto = str_replace(
            ['\\n', '\\r', '\\t', '\\"', "\\'", '\\*'],
            ["\n",  "\r",  "\t",  '"',   "'",   '*'],
            $texto
        );

        // 2. Trim
        $texto = trim($texto);

        // 3. Quitar comillas envolventes (solo si abren y cierran)
        if (str_starts_with($texto, '"') && str_ends_with($texto, '"')) {
            $texto = trim(substr($texto, 1, -1));
        }

        // 4. Colapsar espacios múltiples horizontales (no saltos de línea)
        $texto = preg_replace('/[ \t]+/', ' ', $texto);

        // 5. Trim final
        return trim($texto);
    }

}