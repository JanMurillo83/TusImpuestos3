<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJarInterface;
use PhpCfdi\CfdiSatScraper\Exceptions\SatHttpGatewayException;
use PhpCfdi\CfdiSatScraper\Exceptions\SatHttpGatewayResponseException;
use PhpCfdi\CfdiSatScraper\SatHttpGateway;
use Illuminate\Support\Facades\Log;

/**
 * Gateway HTTP para el SAT que tolera respuestas vacías en postCiecLoginData.
 *
 * El SAT cambió su comportamiento (Feb 2026) y ahora devuelve Content-Length: 0
 * en el POST intermedio a AUTH_LOGIN_CIEC durante el flujo de login FIEL.
 * La librería original lanza SatHttpGatewayResponseException::unexpectedEmptyResponse.
 */
class SatHttpGatewayFixed extends SatHttpGateway
{
    public function __construct(?ClientInterface $client = null, ?CookieJarInterface $cookieJar = null)
    {
        parent::__construct($client, $cookieJar);
    }

    /**
     * Override: tolera respuestas vacías del SAT en el paso CIEC intermedio.
     *
     * @param array<string, string> $formParams
     * @throws SatHttpGatewayException
     */
    public function postCiecLoginData(string $loginUrl, array $formParams): string
    {
        try {
            return parent::postCiecLoginData($loginUrl, $formParams);
        } catch (SatHttpGatewayResponseException $e) {
            if (str_contains($e->getMessage(), 'Unexpected empty content')) {
                Log::info('SatHttpGatewayFixed: Tolerando respuesta vacía en postCiecLoginData');
                return '';
            }
            throw $e;
        }
    }
}
