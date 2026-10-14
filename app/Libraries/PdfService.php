<?php

namespace App\Libraries;

use App\Models\DocumentGenereModel;
use Config\Paths;
use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

/**
 * Génère et conserve les PDF produits par l'application.
 *
 * Le service reçoit des données déjà calculées par la couche métier. Il ne
 * calcule aucune règle juridique et n'interprète pas les données métier.
 */
class PdfService
{
    private DocumentGenereModel $documents;
    private string $directory;

    public function __construct(?DocumentGenereModel $documents = null, ?string $directory = null)
    {
        $this->documents = $documents ?? new DocumentGenereModel();
        $this->directory = rtrim($directory ?? (new Paths())->writableDirectory, '/') . '/documents';
    }

    /**
     * @param array{societe_id:int, reunion_id?:int|null, nature:string, nom_fichier?:string} $document
     * @return array{id:int, fichier_pdf:string, chemin:string, contenu:string}
     */
    public function generateFromView(string $view, array $data, array $document): array
    {
        foreach (['societe_id', 'nature'] as $required) {
            if (! array_key_exists($required, $document)) {
                throw new RuntimeException("Métadonnée PDF manquante : {$required}.");
            }
        }

        $html = view('pdf/layout', [
            'title'   => $data['titre'] ?? 'Document juridique',
            'content' => view($view, $data),
            'is_pv'   => ! empty($data['is_pv']),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $content = $dompdf->output();

        if (! is_dir($this->directory) && ! mkdir($this->directory, 0750, true) && ! is_dir($this->directory)) {
            throw new RuntimeException("Impossible de créer le répertoire de documents PDF : {$this->directory}");
        }

        $filename = $this->filename($document['nom_fichier'] ?? ($document['nature'] . '-' . date('YmdHis') . '.pdf'));
        $relativePath = 'documents/' . $filename;
        $absolutePath = $this->directory . '/' . $filename;

        if (file_put_contents($absolutePath, $content) === false) {
            throw new RuntimeException("Impossible d'enregistrer le PDF : {$absolutePath}");
        }

        $id = $this->documents->insert([
            'societe_id' => (int) $document['societe_id'],
            'reunion_id' => isset($document['reunion_id']) ? (int) $document['reunion_id'] : null,
            'nature'     => $document['nature'],
            'fichier_pdf'=> $relativePath,
        ]);

        if (! $id) {
            @unlink($absolutePath);
            $error = db_connect()->error();
            throw new RuntimeException(
                "Impossible d'enregistrer le document généré : " . ($error['message'] ?: 'erreur inconnue.')
            );
        }

        return [
            'id'          => (int) $id,
            'fichier_pdf' => $relativePath,
            'chemin'      => $absolutePath,
            'contenu'     => $content,
        ];
    }

    /**
     * Nettoie le nom du fichier et garantit qu'il contient une date :
     * si le nom n'en a pas (AAAA-MM-JJ ou AAAAMMJJ), la date du jour est ajoutée.
     */
    private function filename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?? '';

        if ($filename === '' || ! str_ends_with(strtolower($filename), '.pdf')) {
            throw new RuntimeException('Le nom du fichier PDF est invalide.');
        }

        $base = substr($filename, 0, -4);
        if (! preg_match('/\d{4}-\d{2}-\d{2}|\d{8}/', $base)) {
            $base .= '-' . date('Y-m-d');
        }

        return $base . '.pdf';
    }
}