<?php

namespace App\Libraries;

use App\Models\AssocieModel;
use App\Models\DirigeantModel;
use App\Models\SocieteModel;
use App\Models\TypeSocieteModel;
use RuntimeException;

/**
 * Prépare les données persistées pour le générateur des statuts.
 *
 * Cette classe ne déduit aucune règle juridique : les champs absents restent
 * signalés comme tels dans le PDF.
 */
class StatutsService
{
    public function __construct(
        private ?SocieteModel $societes = null,
        private ?TypeSocieteModel $types = null,
        private ?AssocieModel $associes = null,
        private ?DirigeantModel $dirigeants = null,
    ) {
        $this->societes  ??= new SocieteModel();
        $this->types     ??= new TypeSocieteModel();
        $this->associes  ??= new AssocieModel();
        $this->dirigeants ??= new DirigeantModel();
    }

    /**
     * @return array{
     *     vue:string,
     *     societe_id:int,
     *     nature:string,
     *     nom_fichier:string,
     *     data:array<string,mixed>
     * }
     */
    public function preparer(int $societeId): array
    {
        $societe = $this->societes->find($societeId);
        if (! $societe) {
            throw new RuntimeException("Société #{$societeId} introuvable.");
        }

        $type = $this->types->find((int) $societe['type_societe_id']);
        if (! $type) {
            throw new RuntimeException("Type de société #{$societe['type_societe_id']} introuvable.");
        }

        $code = (string) $type['code'];
        if ($code === 'SCA') {
            throw new RuntimeException('Le gabarit SCA n’est pas encore disponible.');
        }

        $vue = match ($code) {
            'SARL', 'SARLU' => 'pdf/statuts/sarl',
            'SA' => $societe['mode_administration'] === 'AG'
                ? 'pdf/statuts/sa_administrateur_general'
                : ($societe['mode_administration'] === 'CA'
                    ? 'pdf/statuts/sa_conseil_administration'
                    : throw new RuntimeException(
                        'Le mode d’administration de cette SA est manquant ou non reconnu.'
                    )),
            'SCS', 'SNC' => 'pdf/statuts/scs_snc',
            'SC' => 'pdf/statuts/sc',
            default => throw new RuntimeException("Aucun gabarit de statuts disponible pour {$code}."),
        };

        $associes = $this->associes->where('societe_id', $societeId)->findAll();
        $dirigeants = $this->dirigeants->where('societe_id', $societeId)->findAll();

        $data = [
            'titre'       => 'Projet de statuts',
            'societe'     => $societe,
            'type'        => $type,
            'associes'    => $associes,
            'dirigeants'  => $dirigeants,
            'forme'       => $type['libelle'],
            'gouvernance' => $this->gouvernance($code, $societe, $type),
            'article_10'  => $this->mentionsArticle10($societe, $type),
        ];

        return [
            'vue'         => $vue,
            'societe_id'  => $societeId,
            'nature'      => 'statuts',
            'nom_fichier' => $this->nomFichier((string) $societe['denomination']),
            'data'        => $data,
        ];
    }

    private function gouvernance(string $code, array $societe, array $type): string
    {
        if ($code === 'SA') {
            return $societe['mode_administration'] === 'AG'
                ? 'Administrateur général'
                : 'Conseil d’administration';
        }

        return (string) ($type['organe_direction'] ?? 'Non renseigné');
    }

    /**
     * Ces champs sont ceux disponibles dans le dossier. La source est affichée
     * avec un statut à confirmer, car le texte officiel de l’article n’est pas
     * stocké dans une table de règles dédiée aux statuts.
     *
     * @return list<array{libelle:string,valeur:string,source:string,statut:string}>
     */
    private function mentionsArticle10(array $societe, array $type): array
    {
        $champs = [
            ['Dénomination', $societe['denomination'] ?? null],
            ['Forme sociale', $type['libelle'] ?? null],
            ['Siège social', $societe['siege'] ?? null],
            ['Objet social', $societe['objet'] ?? null],
            ['Capital social', $this->montant($societe['capital'] ?? null)],
            ['Durée', $this->duree($societe['duree_annees'] ?? null)],
            ['Code d’activité', $societe['code_activite'] ?? null],
        ];

        $mentions = [];
        foreach ($champs as [$libelle, $valeur]) {
            if ($valeur === null || $valeur === '') {
                continue;
            }
            $mentions[] = [
                'libelle' => $libelle,
                'valeur'  => (string) $valeur,
                'source'  => 'Loi 2003-036, article 10',
                'statut'  => 'à confirmer',
            ];
        }

        return $mentions;
    }

    private function montant(mixed $montant): ?string
    {
        return $montant === null || $montant === ''
            ? null
            : number_format((float) $montant, 2, ',', ' ') . ' ariary';
    }

    private function duree(mixed $annees): ?string
    {
        return $annees === null || $annees === '' ? null : $annees . ' ans';
    }

    private function nomFichier(string $denomination): string
    {
        $base = preg_replace('/[^A-Za-z0-9]+/', '-', $denomination) ?? 'societe';
        return 'statuts-' . trim($base, '-') . '-' . date('YmdHis') . '.pdf';
    }
}
