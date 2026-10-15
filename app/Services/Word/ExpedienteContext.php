<?php

namespace App\Services\Word;

use App\Services\CalculosExpediente;
use Carbon\Carbon;

class ExpedienteContext
{
    public readonly string $id;
    public readonly array $data;
    public readonly array $ia;
    public readonly CalculosExpediente $calc;

    public function __construct(string $id, array $data, array $ia)
    {
        $this->id   = $id;
        $this->data = $data;
        $this->ia   = $ia;
        $this->calc = new CalculosExpediente($data);
    }

    // ─── Metadata del negocio ────────────────────────────────
    public function empresaBrinda(): string
    {
        return $this->ia['empresa_factura'] ?? '';
    }

    public function empresaRecibe(): string
    {
        return $this->ia['cliente'] ?? '';
    }

    public function servicio(): string
    {
        return $this->ia['servicio'] ?? '';
    }

    public function ciudad(): string
    {
        return $this->ia['ciudad'] ?? 'Mérida, Yucatán, México';
    }

    public function conceptosIA(): array
    {
        return $this->ia['conceptos'] ?? [];
    }

    // ─── Fechas ─────────────────────────────────────────────
    public function fechaLegalFormateada(): ?string
    {
        $raw = $this->data['calculos']['fecha_legal'] ?? null;
        if (!$raw) return null;

        try {
            return Carbon::parse($raw)->format('d/m/Y');
        } catch (\Throwable) {
            return $raw;
        }
    }

    public function fechaLegalISO(): ?string
    {
        return $this->data['calculos']['fecha_legal'] ?? null;
    }
}