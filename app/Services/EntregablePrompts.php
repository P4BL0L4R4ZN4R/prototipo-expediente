<?php

namespace App\Services;

class EntregablePrompts
{
    /**
     * Devuelve la plantilla del prompt según la sección.
     */
    public function plantilla(string $seccion): string
    {
        return match ($seccion) {
            'introduccion'          => $this->introduccion(),
            'problematica'          => $this->problematica(),
            'desarrollo_conceptos'  => $this->desarrolloConceptos(),
            'finalizacion_servicio' => $this->servicioPrestado(), 
            'retrospectiva' => $this->retrospectiva(),
            default                 => '',
        };
    }

    // =========================================================
    // PROMPTS
    // =========================================================

    //con empresa y cliente
    // private function introduccion(): string
    // {
    //     return <<<PROMPT
    //     Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

    //     Redacta la INTRODUCCIÓN de un ENTREGABLE retrospectivo (informe de cierre de servicios ya concluidos).

    //     CONTEXTO:
    //     - Servicio: {{SERVICIO}}
    //     - Cliente: Empresa
    //     - Objetivo general: {{OBJETIVO_GENERAL}}
    //     - Objetivos específicos: {{OBJETIVOS_ESPECIFICOS}}
    //     - Periodo de servicio: {{PERIODO_SERVICIO}}
    //     - Año de reporte: {{ANIO_REPORTE}}

    //     REGLAS DE CONTENIDO:
    //     - Redacta en PASADO (el servicio ya se prestó).
    //     - Menciona explícitamente el periodo: "{{PERIODO_SERVICIO}}".
    //     - Explica el propósito del proyecto y su justificación.
    //     - Anticipa brevemente los temas que se desarrollarán en el documento.
    //     - Refleja impacto organizacional y operativo del servicio concluido.

    //     REGLAS DE COHERENCIA:
    //     - Pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
    //     - Prohibido usar términos de otras disciplinas o consultoría genérica: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.
    //     - Sin adjetivos grandilocuentes.

    //     REGLAS DE FORMATO:
    //     - Un solo párrafo continuo.
    //     - Sin subtítulos, sin listas, sin viñetas.
    //     - Tono ejecutivo e impersonal.
    //     - No inventes fechas ni cifras.
    //     - No menciones inteligencia artificial.

    //     Entrega únicamente el párrafo final.
    //     PROMPT;
    // }

    //sin empresa y cliente
    private function introduccion(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

        Redacta la INTRODUCCIÓN de un ENTREGABLE retrospectivo (informe de cierre de servicios ya concluidos).

        CONTEXTO:
        - Servicio: {{SERVICIO}}
        - Objetivo general: {{OBJETIVO_GENERAL}}
        - Objetivos específicos: {{OBJETIVOS_ESPECIFICOS}}
        - Periodo de servicio: {{PERIODO_SERVICIO}}
        - Año de reporte: {{ANIO_REPORTE}}

        ESTRUCTURA OBLIGATORIA (en este orden):
        1. Encuadre del periodo y del servicio prestado.
        2. Contexto técnico que motivó el servicio.
        3. Justificación del proyecto.
        4. Propósito general derivado del objetivo.
        5. Alcance: actividades desarrolladas, derivadas de los objetivos específicos.
        6. Resultados obtenidos y su relación con el propósito.
        7. Proyección de las secciones que se presentan en el documento.

        REGLAS DE CONTENIDO:
        - Redacta en PASADO (el servicio ya se prestó).
        - Menciona explícitamente el periodo: "{{PERIODO_SERVICIO}}" en el primer párrafo.
        - NO menciones el nombre del cliente, ni razones sociales, ni siglas, ni la palabra "empresa" ni "organización". Usa construcciones impersonales o pasivas: "se llevó a cabo", "se desarrolló", "se implementó", "el servicio permitió".
        - Si el nombre del cliente aparece en el objetivo general, en el servicio o en cualquier otro campo del contexto, OMÍTELO por completo.
        - Explica el propósito del proyecto y su justificación.
        - Anticipa brevemente los temas que se desarrollarán en el documento.
        - Refleja impacto organizacional y operativo del servicio concluido.

        REGLAS DE EXTENSIÓN:
        - Genera entre 5 y 7 párrafos.
        - Cada párrafo debe tener entre 5 y 8 oraciones sustantivas.
        - Cada oración debe aportar información nueva; no repitas ideas con distintas palabras.
        - Usa relaciones de causa-efecto explícitas: "dado que", "en consecuencia", "debido a", "lo que permitió".
        - Distribuye la estructura en los párrafos: cada paso de la estructura debe ocupar al menos un párrafo.

        REGLAS DE COHERENCIA:
        - Pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
        - Prohibido usar términos de otras disciplinas o consultoría genérica: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.
        - Prohibido usar vocabulario académico de tesis: hipótesis, marco teórico, objeto de estudio, epistemología, corpus, perspectiva analítica, el presente estudio, la presente investigación.
        - Sin adjetivos grandilocuentes: significativo, notable, trascendental, clave, fundamental, crucial.
        - No inventes citas, autores ni referencias bibliográficas.

        REGLAS DE FORMATO:
        - Tono ejecutivo e impersonal.
        - No inventes fechas ni cifras.
        - No menciones inteligencia artificial.
        - NO uses markdown, ni subtítulos, ni viñetas, ni listas.

        FORMATO DE SALIDA OBLIGATORIO:
        Devuelve EXCLUSIVAMENTE un objeto JSON válido, sin texto adicional, sin bloques de código, sin comillas triples, con esta estructura exacta:

        {"parrafos":["Texto del primer párrafo.","Texto del segundo párrafo.","Texto del tercer párrafo."]}

        No incluyas ningún texto antes o después del JSON. No uses markdown. No envuelvas el JSON en bloques de código.
        PROMPT;
    }

    private function problematica(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

        Redacta la PROBLEMÁTICA de un ENTREGABLE retrospectivo, a partir de la problemática original de la propuesta.

        PROBLEMÁTICA ORIGINAL:
        "{{PROBLEMATICA_PROPUESTA}}"

        CONTEXTO:
        - Servicio: {{SERVICIO}}
        - Cliente: {{CLIENTE}}
        - Objetivo general: {{OBJETIVO_GENERAL}}
        - Periodo de servicio: {{PERIODO_SERVICIO}}

        ESTRUCTURA OBLIGATORIA:
        Genera EXACTAMENTE 3 párrafos, separados entre sí por un salto de línea doble.

        PÁRRAFO 1 — Situación inicial:
        Inicia EXACTAMENTE con: "{{CLIENTE}} enfrentaba...". Describe las deficiencias, limitaciones o carencias que existían antes del servicio. Menciona las consecuencias operativas, administrativas o técnicas que esas deficiencias generaban. Desarrolla entre 6 y 8 oraciones sustantivas. Cada oración debe aportar información nueva.

        PÁRRAFO 2 — Impacto de la problemática:
        Profundiza en el impacto que esas deficiencias tenían sobre la operación. Explica riesgos, costos, retrasos, errores o pérdidas derivadas del problema original. Mantén el mismo problema central de la propuesta original. Desarrolla entre 6 y 8 oraciones sustantivas. Cada oración debe aportar información nueva.

        PÁRRAFO 3 — Justificación del servicio:
        Cierra justificando la necesidad del servicio prestado. Explica cómo la intervención profesional atendió el problema central. Menciona el propósito del servicio y su relación con el objetivo general. Desarrolla entre 5 y 7 oraciones sustantivas. Cada oración debe aportar información nueva.

        REGLAS DE CONTENIDO:
        - Mantén el mismo problema central de la propuesta original.
        - Redacta en PASADO: el problema fue atendido durante el servicio.
        - Inicia EXACTAMENTE con: "{{CLIENTE}} enfrentaba..." en el primer párrafo.
        - Describe las deficiencias que existían antes del servicio.
        - Cierra justificando la necesidad del servicio prestado.

        REGLAS DE COHERENCIA:
        - Pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
        - Prohibido usar términos de otras disciplinas: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.
        - Sin adjetivos grandilocuentes.

        REGLAS DE FORMATO:
        - Entrega exactamente 3 párrafos continuos.
        - Separa cada párrafo con UN salto de línea doble. No uses más de dos saltos seguidos.
        - Sin subtítulos, sin listas, sin viñetas.
        - Tono ejecutivo e impersonal.
        - No inventes fechas ni cifras.
        - No uses markdown.
        - No repitas ideas con distintas palabras para rellenar.
        - No menciones inteligencia artificial.

        FORMATO DE SALIDA:
        Entrega únicamente los 3 párrafos separados por saltos de línea dobles. No agregues nada más.
        PROMPT;
    }

    private function desarrolloConceptos(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}.

        Redacta el DESARROLLO DE CONCEPTOS de un ENTREGABLE retrospectivo. Desarrolla TODOS los conceptos, cada uno como un apartado independiente, con nivel de profundidad descriptiva y densidad argumentativa propia de un documento técnico formal.

        CONTEXTO:
        - Servicio: {{SERVICIO}}
        - Cliente: {{CLIENTE}}
        - Objetivo general: {{OBJETIVO_GENERAL}}
        - Objetivos específicos: {{OBJETIVOS_ESPECIFICOS}}
        - Periodo de servicio: {{PERIODO_SERVICIO}}
        - Año de reporte: {{ANIO_REPORTE}}

        CONCEPTOS DEL PROGRAMA (con su periodo asignado):
        {{CONCEPTOS}}

        REGLAS DE ORDEN:
        - Ordena los apartados de forma CRONOLÓGICA según la semana de inicio de cada concepto (de menor a mayor).
        - Si dos conceptos comparten semana de inicio, consérvalos en el orden en que aparecen en la lista CONCEPTOS.
        - No alteres el nombre ni el periodo de los conceptos.

        REGLAS DE CONTENIDO:
        - El número de apartados DEBE ser EXACTAMENTE igual al número de conceptos listados.
        - Está prohibido fusionar, omitir o resumir conceptos.
        - Cada apartado describe: qué se analizó, qué se desarrolló, qué se implementó, qué se optimizó, y qué beneficio generó.
        - Menciona el periodo correspondiente en cada apartado (usando el texto "Semana X" o "Semana X a Semana Y"), en el primer párrafo del apartado.
        - Redacta en PASADO.

        REGLAS DE PROFUNDIDAD (NIVEL TÉCNICO FORMAL):
        - Cada apartado debe tener entre 4 y 5 párrafos.
        - Cada párrafo debe tener entre 5 y 7 oraciones sustantivas.
        - Cada oración debe aportar información nueva; no repitas ideas con distintas palabras.
        - Usa relaciones de causa-efecto explícitas: "dado que", "en consecuencia", "debido a", "lo que permitió".
        - Fundamenta las decisiones tomadas (por qué se analizó así, por qué se implementó de esa forma), no solo describas lo hecho.
        - Evita frases genéricas sin contenido técnico: "es importante destacar", "cabe mencionar", "en la actualidad".
        - No uses vocabulario académico de tesis: hipótesis, marco teórico, objeto de estudio, epistemología, corpus, perspectiva analítica.

        REGLAS DE COHERENCIA:
        - Pertenecer estrictamente al tema "{{TEMA}}" y a la rama "{{RAMA}}".
        - Prohibido usar términos de otras disciplinas: sostenibilidad, resiliencia, gobernanza, activo estratégico, ventaja competitiva, transformación, sinergia, ecosistema, pilar estratégico, holístico, robusto, intangible, patrimonial, gobierno institucional.
        - Sin adjetivos grandilocuentes: significativo, notable, trascendental, clave, fundamental, crucial.
        - No inventes cifras ni fechas exactas.

        REGLAS DE FORMATO:
        - Inicia EXACTAMENTE con: "A continuación, se presentan los temas desarrollados durante el servicio:"
        - Después, cada apartado con la estructura: **NOMBRE DEL CONCEPTO EN MAYÚSCULAS.** Desarrollo del concepto.
        - El nombre del concepto va en MAYÚSCULAS, seguido de punto, y luego el desarrollo en párrafos separados por doble salto de línea.
        - Cada apartado separado del siguiente por un doble salto de línea.
        - Sin viñetas, sin numeración visible en los subtítulos.
        - Tono ejecutivo e impersonal.
        - No menciones inteligencia artificial.

        Entrega únicamente el desarrollo completo.
        PROMPT;
    }

    private function servicioPrestado(): string
    {
        return <<<PROMPT
        Redacta UNA descripción breve del servicio prestado.

        Se insertará en la siguiente frase:
        "un documento que proporciona [NOMBRE DEL PROGRAMA], consistente en {{DESCRIPCION}}"

        DATOS DEL DOCUMENTO:
        - Nombre del programa: {{NOMBRE_PROGRAMA}}
        - Área: {{AREA}}
        - Tema: {{TEMA}}
        - Objetivo: {{OBJETIVO}}

        REGLAS:
        - La descripción debe ser coherente con el área "{{AREA}}" y el tema "{{TEMA}}".
        - Debe leerse como algo que el documento aporta al cliente.
        - NO debe repetir literalmente el nombre del programa.
        - Una sola línea, sin punto final, sin comillas, sin viñetas.
        - Prohibido usar términos genéricos vacíos: consultoría, asesoría, apoyo, solución integral.
        - Prohibido usar términos de otras disciplinas: sostenibilidad, resiliencia, gobernanza, sinergia, ecosistema, transformación, ventaja competitiva.
        - Prohibido mezclar vocabulario de otras áreas.

        Entrega únicamente la descripción.
        PROMPT;
    }


    private function retrospectiva(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}. Redacta una descripción de la ejecución de un proyecto concluido, utilizando exclusivamente la información proporcionada.

        CONTEXTO DEL PROYECTO
        - Servicio: {{SERVICIO}}
        - Cliente: {{CLIENTE}}
        - Periodo: {{PERIODO_SERVICIO}}
        - Duración: {{DURACION}}
        - Modalidad: {{MODALIDAD}}
        - Ubicación: {{UBICACION}}
        - Conceptos clave: {{CONCEPTOS}}

        ESTRUCTURA OBLIGATORIA

        Párrafo 1. Cronología y organización: Describe el periodo y la duración del proyecto. Explica cómo se organizaron las actividades y cómo se distribuyeron temporalmente, únicamente cuando la información proporcionada permita afirmarlo.

        Párrafo 2. Desarrollo, metodología y alcance: Describe las actividades realizadas, los enfoques de trabajo y los conceptos clave, integrándolos de forma natural en la narración. Menciona los resultados obtenidos únicamente cuando estén respaldados por la información disponible.

        Párrafo 3. Logística y modalidad: Describe la modalidad de prestación del servicio, la ubicación y los medios de comunicación o coordinación utilizados, según corresponda a los datos proporcionados. Explica cómo estos elementos contribuyeron al seguimiento y desarrollo del proyecto, sin atribuir actividades que no estén documentadas.

        REGLAS GENERALES
        - Entrega exactamente 3 párrafos continuos, separados por saltos de línea.
        - No incluyas subtítulos, listas, viñetas ni encabezados.
        - Utiliza un tono ejecutivo, formal, impersonal y en pasado.
        - No inventes fechas, actividades, metodologías, resultados, ubicaciones ni medios de trabajo.
        - No conviertas conceptos clave en una lista; intégralos en la narración cuando sean pertinentes.
        - Adapta la redacción a la modalidad real del servicio, sin presuponer sesiones presenciales o virtuales.
        - No menciones inteligencia artificial.
        - Entrega únicamente los 3 párrafos.
        PROMPT;
    }

   
}