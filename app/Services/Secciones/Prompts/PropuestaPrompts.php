<?php

namespace App\Services\Secciones\Prompts;

class PropuestaPrompts
{
    /**
     * Devuelve la plantilla del prompt según la sección.
     */
    public function plantilla(string $seccion): string
    {
        return match ($seccion) {
            'texto_propuesta'       => $this->textoPropuesta(),
            'introduccion'          => $this->introduccion(),
            'problematica'          => $this->problematica(),
            'objetivo_general'      => $this->objetivoGeneral(),
            'objetivos_especificos' => $this->objetivosEspecificos(),
            'metodologia'           => $this->metodologia(),
            default                 => '',
        };
    }

    // =========================================================
    // PROMPTS
    // =========================================================

    private function textoPropuesta(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

        Redacta el TEXTO DE APERTURA de una propuesta de servicios. Este texto es un pitch breve que se coloca al inicio del documento, después del encabezado, y funciona como carta de presentación al cliente.

        CONTEXTO DEL PROYECTO:
        - Servicio: {{SERVICIO}}
        - Objetivo del cliente: {{OBJETIVO}}
        - Rama: {{RAMA}}
        - Tema: {{TEMA}}
        - Conceptos del programa:
        {{CONCEPTOS}}

        REGLAS DE CONTENIDO:
        - Debe iniciar EXACTAMENTE con: "Es un placer presentarle la siguiente propuesta de servicios especializados en "{{SERVICIO}}", cuyo objetivo es..."
        - Después de la frase inicial, desarrolla el valor del servicio en términos concretos.
        - Menciona cómo la propuesta fortalece a la empresa cliente.
        - Menciona qué tipo de actividades incluye el servicio.
        - Cierra con una idea de compromiso y valor tangible.
        - Tono comercial, persuasivo y ejecutivo.

        REGLAS DE COHERENCIA:
        - El texto debe pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
        - Todo el vocabulario técnico debe provenir de los conceptos del programa listados arriba.
        - Prohibido usar términos de otras disciplinas o de consultoría genérica. Ejemplos prohibidos: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.
        - No uses adjetivos grandilocuentes ni construcciones retóricas.
        - Si dudas si un término pertenece al dominio, no lo uses.

        REGLAS DE FORMATO:
        - Un solo párrafo continuo.
        - Sin subtítulos, sin listas, sin viñetas.
        - Tono ejecutivo e impersonal.
        - No inventes fechas, cifras ni datos numéricos.
        - No menciones nombres de empresas.
        - No menciones inteligencia artificial ni el proceso de generación.

        Entrega únicamente el párrafo final.
        PROMPT;
    }

    private function introduccion(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

        Redacta la sección "INTRODUCCIÓN" de una propuesta de servicios. Esta sección va dentro de la tabla formal del documento, como marco contextual del servicio.

        CONTEXTO DEL PROYECTO:
        - Servicio: {{SERVICIO}}
        - Objetivo del cliente: {{OBJETIVO}}
        - Rama: {{RAMA}}
        - Tema: {{TEMA}}
        - Conceptos del programa:
        {{CONCEPTOS}}

        REGLAS DE CONTENIDO:
        - Explica la importancia estratégica del tema dentro de una organización.
        - Justifica la necesidad del servicio.
        - Contextualiza retos u oportunidades empresariales relacionados con el tema.
        - Introduce el propósito general del proyecto.
        - Refleja impacto organizacional y operativo.

        REGLAS DE COHERENCIA:
        - El texto debe pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
        - Todo el vocabulario técnico debe provenir de los conceptos del programa listados arriba.
        - Prohibido usar términos de otras disciplinas o de consultoría genérica. Ejemplos prohibidos: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.
        - No uses adjetivos grandilocuentes ni construcciones retóricas.
        - Si dudas si un término pertenece al dominio, no lo uses.

        REGLAS DE FORMATO:
        - Un solo párrafo continuo.
        - Sin subtítulos, sin listas, sin viñetas.
        - Tono ejecutivo e impersonal.
        - No inventes fechas, cifras ni datos numéricos.
        - No menciones nombres de empresas.
        - No menciones inteligencia artificial ni el proceso de generación.

        Entrega únicamente el párrafo final.
        PROMPT;
    }

    private function problematica(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

        Redacta la sección "PROBLEMÁTICA" de una propuesta de servicios. Esta sección describe los riesgos, deficiencias o necesidades específicas que enfrenta el cliente en relación con el servicio solicitado.

        CONTEXTO DEL PROYECTO:
        - Servicio: {{SERVICIO}}
        - Objetivo del cliente: {{OBJETIVO}}
        - Rama: {{RAMA}}
        - Tema: {{TEMA}}
        - Cliente: {{CLIENTE}}
        - Conceptos del programa:
        {{CONCEPTOS}}

        REGLAS DE CONTENIDO:
        - Debe iniciar EXACTAMENTE con: "La empresa se enfrenta..."
        - Describe deficiencias, limitaciones o necesidades particulares del cliente en relación con el servicio.
        - Expón consecuencias operativas, administrativas, comerciales o estratégicas.
        - Evidencia riesgos por falta de planeación, control, seguimiento, estructura, eficiencia, cumplimiento, coordinación o especialización.
        - Justifica la necesidad de apoyo profesional especializado.
        - Cierra reforzando la importancia de implementar soluciones alineadas con el tema.

        REGLAS DE COHERENCIA:
        - El texto debe pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
        - Todo el vocabulario técnico debe provenir de los conceptos del programa listados arriba.
        - Prohibido usar términos de otras disciplinas o de consultoría genérica. Ejemplos prohibidos: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.
        - No uses adjetivos grandilocuentes ni construcciones retóricas.
        - Si dudas si un término pertenece al dominio, no lo uses.

        REGLAS DE FORMATO:
        - Un solo párrafo continuo.
        - Sin subtítulos, sin listas, sin viñetas.
        - Tono ejecutivo e impersonal.
        - No inventes fechas, cifras ni datos numéricos.
        - No menciones nombres de empresas.
        - No menciones inteligencia artificial ni el proceso de generación.

        Entrega únicamente el párrafo final.
        PROMPT;
    }

    private function objetivoGeneral(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

        Redacta el "OBJETIVO GENERAL" de una propuesta de servicios. Es un enunciado breve que resume la transformación o capacidad esperada al finalizar el servicio.

        CONTEXTO DEL PROYECTO:
        - Servicio: {{SERVICIO}}
        - Objetivo del cliente: {{OBJETIVO}}
        - Rama: {{RAMA}}
        - Tema: {{TEMA}}
        - Cliente: {{CLIENTE}}
        - Conceptos del programa:
        {{CONCEPTOS}}

        REGLAS DE CONTENIDO:
        - Debe iniciar EXACTAMENTE con: "Al finalizar el servicio, {{CLIENTE}} será capaz de..."
        - La segunda oración debe iniciar EXACTAMENTE con: "Así, la empresa..."
        - Exactamente 2 oraciones.
        - Entre 70 y 100 palabras en total.
        - Resume el resultado global, el propósito estratégico y el beneficio empresarial más relevante.

        REGLAS DE COHERENCIA:
        - El texto debe pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
        - Todo el vocabulario técnico debe provenir de los conceptos del programa listados arriba.
        - Prohibido usar términos de otras disciplinas o de consultoría genérica. Ejemplos prohibidos: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.
        - No uses adjetivos grandilocuentes ni construcciones retóricas.
        - Si dudas si un término pertenece al dominio, no lo uses.

        REGLAS DE FORMATO:
        - Un solo párrafo continuo.
        - Sin subtítulos, sin listas, sin viñetas.
        - Tono ejecutivo e impersonal.
        - No inventes fechas, cifras ni datos numéricos.
        - No menciones nombres de empresas.
        - No menciones inteligencia artificial ni el proceso de generación.

        Entrega únicamente las dos oraciones.
        PROMPT;
    }

    private function objetivosEspecificos(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

        Redacta los "OBJETIVOS ESPECÍFICOS" de una propuesta de servicios. Son exactamente {{NUM_OBJETIVOS}} enunciados que descomponen el objetivo general en resultados concretos.

        CONTEXTO DEL PROYECTO:
        - Servicio: {{SERVICIO}}
        - Objetivo del cliente: {{OBJETIVO}}
        - Rama: {{RAMA}}
        - Tema: {{TEMA}}
        - Cliente: {{CLIENTE}}
        - Conceptos del programa:
        {{CONCEPTOS}}

        REGLAS DE CONTENIDO:
        - Genera exactamente {{NUM_OBJETIVOS}} objetivos específicos.
        - TODOS deben iniciar EXACTAMENTE con: "{{CLIENTE}} al término del servicio,..."
        - Cada objetivo debe expresar un resultado concreto, mejora empresarial o capacidad desarrollada.
        - Usa verbos de acción como: diagnosticar, diseñar, implementar, optimizar, fortalecer, mejorar, estructurar, incrementar, consolidar, evaluar, medir, desarrollar, integrar, supervisar, coordinar.
        - Evita duplicidades entre objetivos.

        REGLAS DE COHERENCIA:
        - El texto debe pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
        - Todo el vocabulario técnico debe provenir de los conceptos del programa listados arriba.
        - Prohibido usar términos de otras disciplinas o de consultoría genérica. Ejemplos prohibidos: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.
        - No uses adjetivos grandilocuentes ni construcciones retóricas.
        - Si dudas si un término pertenece al dominio, no lo uses.

        REGLAS DE FORMATO:
        - Formato numerado:
        1. Texto...
        2. Texto...
        3. Texto...
        - Sin subtítulos.
        - Sin introducciones adicionales.
        - Tono ejecutivo e impersonal.
        - No inventes fechas, cifras ni datos numéricos.
        - No menciones nombres de empresas.
        - No menciones inteligencia artificial ni el proceso de generación.

        Entrega únicamente la lista numerada.
        PROMPT;
    }

    private function metodologia(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

        Redacta la sección "METODOLOGÍA" de una propuesta de servicios. Consiste en un apartado por cada concepto del programa, donde cada concepto se desarrolla como tema metodológico.

        CONTEXTO DEL PROYECTO:
        - Servicio: {{SERVICIO}}
        - Objetivo del cliente: {{OBJETIVO}}
        - Rama: {{RAMA}}
        - Tema: {{TEMA}}
        - Conceptos del programa:
        {{CONCEPTOS}}

        REGLAS DE CONTENIDO:
        - El número total de apartados generados DEBE ser EXACTAMENTE igual al número de conceptos proporcionados.
        - Cada concepto debe convertirse en un único apartado metodológico individual.
        - Está estrictamente prohibido fusionar, eliminar, omitir o resumir conceptos.
        - Cada apartado debe explicar: qué se analiza, qué se desarrolla, qué se implementa, qué se optimiza, qué se supervisa, qué beneficios genera.
        - Los subtítulos deben conservar EXACTAMENTE el nombre original del concepto en MAYÚSCULAS, respetando tildes.

        REGLAS DE COHERENCIA:
        - El texto debe pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
        - Todo el vocabulario técnico debe provenir de los conceptos del programa listados arriba.
        - Prohibido usar términos de otras disciplinas o de consultoría genérica. Ejemplos prohibidos: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.
        - No uses adjetivos grandilocuentes ni construcciones retóricas.
        - Si dudas si un término pertenece al dominio, no lo uses.

        REGLAS DE FORMATO:
        - Inicia EXACTAMENTE con la frase: "A continuación, se presentan los temas incluidos en el servicio:"
        - Después, cada apartado con la estructura: **[SUBTÍTULO EN MAYÚSCULAS].** Desarrollo profesional del tema.
        - Cada apartado separado por un salto de línea.
        - Sin viñetas.
        - Sin numeración en los subtítulos.
        - Tono ejecutivo e impersonal.
        - No inventes fechas, cifras ni datos numéricos.
        - No menciones nombres de empresas.
        - No menciones inteligencia artificial ni el proceso de generación.

        Entrega únicamente la metodología completa.
        PROMPT;
    }
}