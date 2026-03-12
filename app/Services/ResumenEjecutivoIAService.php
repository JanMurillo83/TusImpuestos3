<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ResumenEjecutivoIAService
{
    public function generarReporte(array $indicadores): string
    {
        $prompt = $this->construirPrompt($indicadores);

        $endpointIa = config('services.ia.endpoint');
        $tokenIa = config('services.ia.token');
        $modeloIa = config('services.ia.model', 'gpt-4.1-mini');

        if (! $endpointIa || ! $tokenIa) {
            throw new \RuntimeException('No hay configuración de API IA (services.ia).');
        }

        $respuesta = Http::withToken($tokenIa)
            ->timeout(30)
            ->post($endpointIa, [
                'model' => $modeloIa,
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un consultor financiero senior especializado en dirección empresarial.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
            ]);

        if (! $respuesta->ok()) {
            $detalle = Str::limit($respuesta->body(), 500);
            throw new \RuntimeException('Error en API IA: ' . $detalle);
        }

        $datosRespuesta = $respuesta->json();

        $contenido = $datosRespuesta['choices'][0]['message']['content']
            ?? $datosRespuesta['choices'][0]['text']
            ?? $datosRespuesta['output_text']
            ?? $datosRespuesta['data']['content']
            ?? $datosRespuesta['result']
            ?? $datosRespuesta['content']
            ?? null;

        if (! $contenido) {
            throw new \RuntimeException('Respuesta IA sin contenido.');
        }

        return trim($contenido);
    }

    private function construirPrompt(array $indicadores): string
    {
        $json = json_encode($indicadores, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Analiza los indicadores financieros y comerciales proporcionados.

Debes generar un REPORTE EJECUTIVO orientado a dirección general.

El reporte debe incluir, en este orden y con estos encabezados exactos:
Resumen Ejecutivo
Indicadores Clave
Análisis Financiero
Análisis Comercial
Hallazgos Estratégicos
Recomendaciones Ejecutivas
Conclusión General

El lenguaje debe ser claro, analítico y orientado a toma de decisiones.

Datos:
$json
PROMPT;
    }
}
