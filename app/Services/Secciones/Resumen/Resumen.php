<?php

namespace App\Services\Secciones;

use App\Models\Expediente;

class Resumen
{
    public function generar(string $id): array
    {
        $exp = Expediente::find($id);
        if (!$exp) {
            throw new \RuntimeException('Expediente no encontrado');
        }

        $ia   = $exp->ia   ?? [];
        $data = $exp->data ?? [];

        if (empty($ia) || empty($data)) {
            throw new \RuntimeException('Expediente incompleto');
        }

        return [
            'id'       => $id,
            'general'  => $this->datosGenerales($data),
            'admin'    => $this->datosAdministrativos($data),
            'calculos' => $this->calculos($data),
            'ia'       => $ia,
        ];
    }

    private function datosGenerales(array $data): array
    {
        return [
            'empresa_factura' => $data['empresa_factura'] ?? null,
            'cliente'         => $data['cliente'] ?? null,
            'servicio'        => $data['servicio'] ?? null,
            'objetivo'        => $data['objetivo'] ?? null,
            'conceptos'       => array_map(
                fn ($c) => [
                    'orden'    => $c['orden']    ?? null,
                    'concepto' => $c['concepto'] ?? null,
                ],
                $data['conceptos'] ?? []
            ),
        ];
    }

    private function datosAdministrativos(array $data): array
    {
        return [
            'fecha_inicio'  => $data['fecha_inicio']  ?? null,
            'fecha_termino' => $data['fecha_termino'] ?? null,
        ];
    }

    private function calculos(array $data): array
    {
        return [
            'total_programa' => $data['calculos']['total_programa'] ?? null,
            'fecha_legal'    => $data['calculos']['fecha_legal']    ?? null,
        ];
    }
}