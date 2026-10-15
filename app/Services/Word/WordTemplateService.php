<?php

namespace App\Services\Word;

use PhpOffice\PhpWord\TemplateProcessor;

class WordTemplateService
{
    public function __construct(
        private WordTemplateHelper $helper,
        private PagosTableBuilder $pagos,
        private CalendarioTableBuilder $calendario,
    ) {}

    // ─── Inyecciones básicas ────────────────────────────────
    public function bloque(TemplateProcessor $t, string $var, string $texto): void
    {
        $this->helper->bloque($t, $var, $texto);
    }

    public function lista(TemplateProcessor $t, string $var, string $texto): void
    {
        $this->helper->lista($t, $var, $texto);
    }

    public function conNegritas(TemplateProcessor $t, string $var, string $texto): void
    {
        $this->helper->conNegritas($t, $var, $texto);
    }

    // ─── Placeholders comunes ───────────────────────────────
    public function setEncabezadoEmpresa(
        TemplateProcessor $t,
        ExpedienteContext $ctx,
        ?string $titulo = null
    ): void {
        $t->setValue('EMPRESA_BRINDA',  $ctx->empresaBrinda());
        $t->setValue('EMPRESA_RECIBE',  $ctx->empresaRecibe());
        $t->setValue('NOMBRE_PROGRAMA', $ctx->servicio());
        if ($titulo !== null) {
            $t->setValue('TITULO_1', $titulo);
        }
    }

    // ─── Tablas ─────────────────────────────────────────────
    public function tablaPagos(
        TemplateProcessor $t,
        string $placeholder,
        $grupos,              // ← sin type hint (o iterable)
        float $total,
        string $totalLetras,
        bool $conFechas = false,
        bool $conTotalLetras = true,
        bool $conTotal = true,
    ): void {
        // ✅ Normalizar a array
        if ($grupos instanceof \Illuminate\Support\Collection) {
            $grupos = $grupos->all();
        } elseif (!is_array($grupos)) {
            $grupos = (array) $grupos;
        }

        $tabla = $this->pagos->build(
            $grupos,
            $total,
            $totalLetras,
            $conFechas,
            $conTotalLetras,
            $conTotal
        );
        $t->setComplexBlock($placeholder, $tabla);
    }

    public function tablaCalendario(
        TemplateProcessor $t,
        string $placeholder,
        array $filas
    ): void {
        $tabla = $this->calendario->build($filas);
        $t->setComplexBlock($placeholder, $tabla);
    }
}