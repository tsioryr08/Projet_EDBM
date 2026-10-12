<?php

namespace App\Controllers;

use App\Libraries\AccesDossier;
use App\Libraries\PdfService;
use App\Libraries\StatutsService;
use CodeIgniter\HTTP\ResponseInterface;

class Statuts extends BaseController
{
    public function generate(int $societeId): ResponseInterface
    {
        if (! AccesDossier::ouvert($societeId)) {
            return AccesDossier::redirection($societeId);
        }

        $document = (new StatutsService())->preparer($societeId);
        $result = (new PdfService())->generateFromView(
            $document['vue'],
            $document['data'],
            [
                'societe_id'  => $document['societe_id'],
                'nature'      => $document['nature'],
                'nom_fichier' => $document['nom_fichier'],
            ]
        );

        return $this->response->download($result['chemin'], null)->setFileName(
            basename($result['chemin'])
        );
    }
}