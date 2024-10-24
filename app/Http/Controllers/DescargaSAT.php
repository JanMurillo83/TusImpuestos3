<?php

namespace App\Http\Controllers;

use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\ComplementoCfdi;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentStatus;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentType;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RfcMatch;
use PhpCfdi\SatWsDescargaMasiva\Shared\RfcOnBehalf;
use PhpCfdi\SatWsDescargaMasiva\Shared\Uuid;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use App\Models\Solicitudes;

class DescargaSAT extends Controller
{
    public function solicitud(Request $request)
    {
        $solicita = $request->solicita;
        $rutacer = $request->rutacer;
        $rutakey = $request->rutakey;
        $inicial = $request->inicio;
        $final = $request->final;
        $version = $request->version;
        $fielpass = $request->fielpass;
        $tiposol = $request->tipo;
        $fiel = Fiel::create(
            file_get_contents($rutacer),
            file_get_contents($rutakey),
            $fielpass
        );
        $webClient = new GuzzleWebClient();
        $requestBuilder = new FielRequestBuilder($fiel);
        $service = new Service($requestBuilder, $webClient);
        $fechainicial = $inicial.' '.'00:00:00';
        $fechafinal = $final.' '.'23:59:'.str_pad($version, 2, "0", STR_PAD_LEFT);
        if($tiposol == 'Emitidos')
        {
            $requestsol = QueryParameters::create()
            ->withPeriod(DateTimePeriod::createFromValues($fechainicial, $fechafinal))
            ->withRequestType(RequestType::xml())
            ->withDocumentStatus(DocumentStatus::active())
            ->withDownloadType(DownloadType::issued());
        }
        else
        {
            $requestsol = QueryParameters::create()
            ->withPeriod(DateTimePeriod::createFromValues($fechainicial, $fechafinal))
            ->withRequestType(RequestType::xml())
            ->withDocumentStatus(DocumentStatus::active())
            ->withDownloadType(DownloadType::received());
        }
        $query = $service->query($requestsol);
        if (! $query->getStatus()->isAccepted()) {
            //dd("Fallo al presentar la consulta: {$query->getStatus()->getMessage()}");
            return 'Error|'.$query->getStatus()->getMessage();
        }
        return 'Exito|'.$query->getRequestId();
    }

    public function verifica_solicitud(Request $request)
    {
        $solicita = $request->solicita;
        $rutacer = $request->rutacer;
        $rutakey = $request->rutakey;
        $requestId = $request->requestId;
        $fielpass = $request->fielpass;
        $fiel = Fiel::create(
            file_get_contents($rutacer),
            file_get_contents($rutakey),
            $fielpass
        );
        $webClient = new GuzzleWebClient();
        $requestBuilder = new FielRequestBuilder($fiel);
        $service = new Service($requestBuilder, $webClient);
        $verify = $service->verify($requestId);
        if (! $verify->getStatus()->isAccepted()) {
            return 'Error|'.$verify->getStatus()->getMessage();
        }

        if (! $verify->getCodeRequest()->isAccepted()) {
            return 'Error|'.$verify->getCodeRequest()->getMessage();
        }

        $statusRequest = $verify->getStatusRequest();
        if ($statusRequest->isExpired() || $statusRequest->isFailure() || $statusRequest->isRejected()) {
            Solicitudes::where('request_id',$requestId)->update([
                'status'=>'Error, Solicitud Invalida'
            ]);
            return 'Error|La Solicitud No se pudo completar';
        }
        if ($statusRequest->isInProgress() || $statusRequest->isAccepted()) {
            Solicitudes::where('request_id',$requestId)->update([
                'status'=>'Procesando Descarga'
            ]);
            return 'Proceso|La Solicitud se sigue Procesando';
        }

        foreach ($verify->getPackagesIds() as $packageId) {
            $download = $service->download($packageId);
            if (! $download->getStatus()->isAccepted()) {
                continue;
            }
            $zipfile = \storage_path().'/app/public/zipdescargas/'."$packageId.zip";
            file_put_contents($zipfile, $download->getPackageContent());
        }
        Solicitudes::where('request_id',$requestId)->update([
            'status'=>'Archivo Descargado'
        ]);
        return 'Exito|'.$zipfile;
    }
}
