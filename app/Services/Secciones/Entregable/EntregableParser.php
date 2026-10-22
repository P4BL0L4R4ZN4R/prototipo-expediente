<?php

namespace App\Services\Secciones\Entregable;

use App\Models\Expediente;

class EntregableParser
{
    /**
     * Procesa el texto crudo de la IA según la sección.
     */
    public function parsear(string $seccion, string $texto, string $id): string
    {
        if (empty($texto)) {
            return '';
        }

        return match ($seccion) {
            'introduccion'          => $this->parsearIntroduccion($texto),
            'problematica'          => $this->parsearParrafos($texto),
            'retrospectiva'         => $this->parsearParrafos($texto),
            'finalizacion_servicio' => $this->parsearCierre($texto, $id),
            'desarrollo_conceptos' => $this->parsearParrafos($texto),
            default => $this->parsearParrafos($texto),
        };
    }

    // ─── Introducción (JSON → párrafos) ─────────────────────

    private function parsearIntroduccion(string $texto): string
    {
        $texto = $this->limpiarTexto($texto);

        $data = json_decode($texto, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            if (preg_match('/\{.*\}/s', $texto, $m)) {
                $data = json_decode($m[0], true);
            }
        }

        if (isset($data['parrafos']) && is_array($data['parrafos'])) {
            $parrafos = array_filter(
                array_map('trim', $data['parrafos']),
                fn($p) => $p !== ''
            );
            // return implode("\n\n", $parrafos);
            return implode("\n", $parrafos);
        }

        return $texto;
    }

    // ─── Problemática / Retrospectiva (párrafos) ────────────

    private function parsearParrafos(string $texto): string
    {
        $texto = $this->limpiarTexto($texto);
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        

        // Colapsar múltiples saltos a uno solo
        $texto = preg_replace("/\n{2,}/", "\n", $texto);

        return trim($texto);
    }

    // ─── Cierre del servicio ────────────────────────────────

    private function parsearCierre(string $texto, string $id): string
    {
        $descripcion = $this->limpiarDescripcion($texto);

        if ($descripcion !== '') {
            $exp = Expediente::find($id);
            if ($exp) {
                $meta = $exp->meta ?? [];
                $meta['descripcion_servicio'] = $descripcion;
                $exp->meta = $meta;
                $exp->save();
            }
        }

        return $this->construirCierre($id, $descripcion);
    }

    private function limpiarDescripcion(string $texto): string
    {
        $texto = $this->limpiarTexto($texto);

        if (str_starts_with($texto, '"') && str_ends_with($texto, '"')) {
            $texto = trim(substr($texto, 1, -1));
        }

        return rtrim($texto, '.');
    }

    private function construirCierre(string $id, string $descripcion): string
    {
        $exp = Expediente::find($id);
        $ia  = $exp->ia ?? [];

        $empresaRecibe  = $ia['cliente'] ?? '';
        $nombrePrograma = $ia['servicio'] ?? '';

        $servicio = $descripcion !== ''
            ? "{$nombrePrograma}, consistente en {$descripcion}"
            : $nombrePrograma;

        return "{$empresaRecibe}., doy por terminada la prestación de servicio profesional "
            . "mediante el cual obtuve un documento que proporciona {$servicio}. "
            . "Dicho documento me fue proporcionado en Mérida, Yucatán, México.";
    }

    // ─── Helper común ───────────────────────────────────────

    private function limpiarTexto(string $texto): string
    {
        $texto = trim($texto);
        $texto = preg_replace('/^```(?:\w+)?\s*|\s*```$/m', '', $texto);
        return trim($texto);
    }
}