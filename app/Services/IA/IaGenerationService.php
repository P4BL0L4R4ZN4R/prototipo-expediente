<?php

namespace App\Services\IA;

class IaGenerationService
{
    public function __construct(
        private OpenAiClient $openai,
        private TextCleaner  $cleaner,
    ) {}

    public function generar(string $prompt): array
    {
        $openai = $this->openai->generar($prompt);

        if ($openai['ok']) {
            return [
                'ok'             => true,
                'texto'          => $this->cleaner->limpiar($openai['texto']),
                'error'          => null,
                'proveedor'      => 'openai',
                'modelo'         => $openai['modelo'],
                'usado_fallback' => false,
                'error_gemini'   => null,
                'tiempo_ms'      => $openai['tiempo_ms'],
            ];
        }

        return [
            'ok'             => false,
            'texto'          => null,
            'error'          => 'OpenAI: ' . $openai['error'],
            'proveedor'      => null,
            'modelo'         => null,
            'usado_fallback' => false,
            'error_gemini'   => null,
            'tiempo_ms'      => $openai['tiempo_ms'],
        ];
    }
}