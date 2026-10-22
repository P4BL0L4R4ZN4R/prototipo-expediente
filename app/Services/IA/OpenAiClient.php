<?php

namespace App\Services\IA;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiClient
{
    public function generar(string $prompt): array
    {
        $key   = config('services.openai.key');
        $model = config('services.openai.model');
        $inicio = microtime(true);

        try {
            set_time_limit(120);

            $response = Http::withToken($key)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(45)
                ->retry(3, 1000)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'    => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7,
                    'max_tokens'  => 4000,
                ]);

            $tiempoMs = (int) round((microtime(true) - $inicio) * 1000);

            if ($response->failed()) {
                Log::error('OpenAI · API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'modelo' => $model,
                ]);
                return [
                    'ok' => false, 'texto' => null,
                    'error' => $response->json('error.message') ?? 'Error al llamar a OpenAI',
                    'modelo' => $model, 'tiempo_ms' => $tiempoMs,
                ];
            }

            $texto = $response->json('choices.0.message.content') ?? '';

            if (trim($texto) === '') {
                return [
                    'ok' => false, 'texto' => null,
                    'error' => 'OpenAI devolvió respuesta vacía',
                    'modelo' => $model, 'tiempo_ms' => $tiempoMs,
                ];
            }

            return [
                'ok' => true, 'texto' => $texto, 'error' => null,
                'modelo' => $model, 'tiempo_ms' => $tiempoMs,
            ];
        } catch (\Throwable $e) {
            $tiempoMs = (int) round((microtime(true) - $inicio) * 1000);
            Log::error('OpenAI · exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return [
                'ok' => false, 'texto' => null,
                'error' => $e->getMessage(),
                'modelo' => $model, 'tiempo_ms' => $tiempoMs,
            ];
        }
    }
}