<?php

namespace App\Services;

class EntregableRetro
{
    public function plantilla(): string
    {
        return $this->resumenEjecutivo();
    }

    private function resumenEjecutivo(): string
    {
        return <<<PROMPT
        Actúa como consultor especializado en {{RAMA}}, específicamente en {{TEMA}}. Redacta una **descripción de la ejecución de un proyecto concluido**, utilizando exclusivamente la información proporcionada.

        **CONTEXTO DEL PROYECTO**
        - Servicio: {{SERVICIO}}
        - Cliente: {{CLIENTE}}
        - Periodo: {{PERIODO_SERVICIO}}
        - Duración: {{DURACION}}
        - Modalidad: {{MODALIDAD}}
        - Ubicación: {{UBICACION}}
        - Conceptos clave: {{CONCEPTOS}}

        **ESTRUCTURA OBLIGATORIA**

        **Párrafo 1. Cronología y organización:** Describe el periodo y la duración del proyecto. Explica cómo se organizaron las actividades y cómo se distribuyeron temporalmente, únicamente cuando la información proporcionada permita afirmarlo.

        **Párrafo 2. Desarrollo, metodología y alcance:** Describe las actividades realizadas, los enfoques de trabajo y los conceptos clave, integrándolos de forma natural en la narración. Menciona los resultados obtenidos únicamente cuando estén respaldados por la información disponible.

        **Párrafo 3. Logística y modalidad:** Describe la modalidad de prestación del servicio, la ubicación y los medios de comunicación o coordinación utilizados, según corresponda a los datos proporcionados. Explica cómo estos elementos contribuyeron al seguimiento y desarrollo del proyecto, sin atribuir actividades que no estén documentadas.

        **REGLAS GENERALES**
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


}
