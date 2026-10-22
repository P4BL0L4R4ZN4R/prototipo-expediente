<?php

namespace App\Http\Controllers\Word;

use App\Http\Controllers\Api\WordController;
use App\Services\Secciones\Calendario\Generador;
use App\Services\Word\ExpedienteContext;
use App\Services\Word\WordFieldDispatcher;
use App\Services\Word\WordFieldRegistry;
use App\Services\Word\WordFieldResolver;
use App\Services\Word\WordTemplateService;
use PhpOffice\PhpWord\TemplateProcessor;

class CalendarioWordController extends WordController
{
    public function __construct(
        private WordTemplateService $tpl,
        private Generador $calendario,  
        WordFieldResolver $resolver,
        WordFieldDispatcher $dispatcher,
    ) {
        parent::__construct($resolver, $dispatcher);
    }

    protected function plantilla(): string
    {
        return 'plantillas/4.CALENDARIO-DE-TRABAJO.docx';
    }

    protected function nombreArchivo(string $id): string
    {
        return "calendario_{$id}";
    }

    protected function campos(): array
    {
        return WordFieldRegistry::calendario();
    }

    protected function construir(TemplateProcessor $template, array $data, array $ia): void
    {
        $ctx   = new ExpedienteContext($this->expedienteId, $data, $ia);
        $filas = $this->calendario->filas($data, $ia); 

        $this->inyectarCampos($template, $ctx);
        $this->tpl->tablaCalendario($template, 'TABLA_CALENDARIO', $filas);
        $this->tpl->setEncabezadoEmpresa($template, $ctx, 'Calendario de Trabajo');
    }
}