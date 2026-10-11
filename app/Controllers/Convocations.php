<?php

namespace App\Controllers;

use App\Libraries\ConvocationService;
use App\Libraries\PdfService;
use CodeIgniter\HTTP\ResponseInterface;

class Convocations extends BaseController
{
    public function generate(int $reunionId): ResponseInterface
    {
        $document = (new ConvocationService())->preparer($reunionId);
        $result = (new PdfService())->generateFromView(
            $document['vue'],
            $document['data'],
            [
                'societe_id'  => $document['societe_id'],
                'reunion_id'  => $document['reunion_id'],
                'nature'      => $document['nature'],
                'nom_fichier' => $document['nom_fichier'],
            ]
        );

        return $this->response->download($result['chemin'], null)->setFileName(
            basename($result['chemin'])
        );
    }
}
