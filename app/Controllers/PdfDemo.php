<?php

namespace App\Controllers;

use App\Libraries\PdfService;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class PdfDemo extends BaseController
{
    public function convocation(): ResponseInterface
    {
        $examplePath = ROOTPATH . 'writable/exemples/convocation.json';
        $json = file_get_contents($examplePath);

        if ($json === false) {
            throw new RuntimeException("JSON d'exemple introuvable : {$examplePath}");
        }

        $data = json_decode($json, true);
        if (! is_array($data)) {
            throw new RuntimeException("JSON d'exemple invalide : {$examplePath}");
        }

        $result = (new PdfService())->generateFromView(
            'pdf/demo_convocation',
            $data,
            [
                'societe_id'  => (int) ($data['societe_id'] ?? 0),
                'reunion_id'  => isset($data['reunion_id']) ? (int) $data['reunion_id'] : null,
                'nature'      => 'convocation',
                'nom_fichier' => 'convocation-exemple-' . date('YmdHis') . '.pdf',
            ]
        );

        return $this->response->download($result['chemin'], null)->setFileName(
            basename($result['chemin'])
        );
    }
}
