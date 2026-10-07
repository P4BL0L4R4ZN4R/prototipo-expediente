<?php

namespace App\Http\Controllers\Word;

use PhpOffice\PhpWord\TemplateProcessor;
use App\Http\Controllers\Api\WordController; 
use Illuminate\Support\Facades\Log;

class AcuseWordController extends WordController
{
    protected function plantilla(): string
    {
        return 'plantillas/7.ACUSE.docx';
    }

    protected function nombreArchivo(string $id): string
    {
        return "acuse_{$id}";
    }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void 
    {

        Log::info('ACUSE · construir() iniciado', [
            'clase'     => static::class,
            'keys_data' => array_keys($data),
            'keys_ia'   => array_keys($ia),
        ]);

        // Los datos del cliente/programa viven en $ia (metadata del Excel),
        // no en $data (que trae conceptos, facturas y cálculos).
        $empresaRecibe = $ia['cliente']
            ?? $ia['empresa_cliente']
            ?? 'EMPRESA NO ESPECIFICADA';

        $empresaBrinda = $ia['empresa_factura']
            ?? config('services.empresa_brinda', 'EMPRESA NO ESPECIFICADA');

        $nombrePrograma = $ia['servicio']
            ?? $ia['nombre_programa']
            ?? 'SERVICIO NO ESPECIFICADO';

        Log::info('ACUSE · valores resueltos', [
            'empresaRecibe'  => $empresaRecibe,
            'empresaBrinda'  => $empresaBrinda,
            'nombrePrograma' => $nombrePrograma,
        ]);

        $template->setValues([
            'TITULO_1'        => 'ACUSE DE RECIBO DE INFORMACIÓN',
            'EMPRESA_RECIBE'  => $empresaRecibe,
            'EMPRESA_BRINDA'  => $empresaBrinda,
            'NOMBRE_PROGRAMA' => $nombrePrograma,
        ]);

        Log::info('ACUSE · setValues() aplicado', [
            'placeholders' => ['TITULO_1', 'EMPRESA_RECIBE', 'EMPRESA_BRINDA', 'NOMBRE_PROGRAMA'],
        ]);
    }
}