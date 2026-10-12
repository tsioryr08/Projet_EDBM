<?php

namespace App\Libraries;

/**
 * Checklist documentaire.
 *
 * Les documents, conditions et durées de validité viennent exclusivement de
 * document_requis et document_type_societe. Ce service ne contient aucune
 * liste juridique de remplacement.
 */
class DocumentChecklistService
{
    protected CalculRegles $regles;

    public function __construct(?CalculRegles $regles = null)
    {
        $this->regles = $regles ?? new CalculRegles();
    }

    public function genererChecklist(array $contexte, ?array $documents = null): array
    {
        $contexte = $this->normaliserContexte($contexte);
        $pieces = $documents ?? $this->buildChecklistForType($contexte['type_societe'] ?? '', $contexte);
        $resultats = array_map(
            fn (array $piece): array => $this->verifierPiece($piece, $contexte),
            $pieces
        );

        $obligatoires = array_values(array_filter(
            $resultats,
            static fn (array $item): bool => (int) ($item['obligatoire'] ?? 0) === 1
        ));
        $manquantes = array_filter($obligatoires, static fn (array $item): bool => $item['statut'] === 'manquante');
        $expirees = array_filter($obligatoires, static fn (array $item): bool => $item['statut'] === 'expiree');
        $valides = array_filter($obligatoires, static fn (array $item): bool => $item['statut'] === 'conforme');
        $aConfirmer = array_filter($obligatoires, static fn (array $item): bool => $item['statut'] === 'a_confirmer');

        return [
            'dossier_statut' => ($manquantes || $expirees || $aConfirmer) ? 'incomplet' : 'complet',
            'pieces_obligatoires' => count($obligatoires),
            'pieces_valides' => count($valides),
            'pieces_manquantes' => count($manquantes),
            'pieces_expirees' => count($expirees),
            'pieces_a_confirmer' => count($aConfirmer),
            'pieces' => $resultats,
        ];
    }

    public function buildChecklistForType(string $typeSociete, array $contexte = []): array
    {
        $contexte = $this->normaliserContexte($contexte + ['type_societe' => strtoupper(trim($typeSociete))]);

        return array_values(array_filter(
            $this->chargerDepuisBase($contexte),
            fn (array $piece): bool => $this->estApplicable($piece, $contexte)
        ));
    }

    public function chargerDepuisBase(array $contexte): array
    {
        $builder = db_connect()->table('document_requis dr')->select('dr.*');
        if (! empty($contexte['type_societe'])) {
            $builder->join('document_type_societe dts', 'dts.document_id = dr.id')
                ->join('type_societe ts', 'ts.id = dts.type_societe_id')
                ->where('ts.code', strtoupper((string) $contexte['type_societe']));
        }

        return $builder->orderBy('dr.categorie')->orderBy('dr.id')->get()->getResultArray();
    }

    public function verifierPiece(array $piece, array $contexte): array
    {
        $code = (string) ($piece['code'] ?? '');
        $obligatoire = (int) ($piece['obligatoire'] ?? $piece['required'] ?? 0);
        $dateDepot = (string) ($contexte['date_depot_prevue'] ?? date('Y-m-d'));
        $document = $contexte['documents'][$code] ?? null;
        $base = [
            'code' => $code,
            'libelle' => $piece['libelle'] ?? $code,
            'obligatoire' => $obligatoire,
            'validite_mois' => (int) ($piece['validite_mois'] ?? 0),
            'date_depot_prevue' => $dateDepot,
            'note' => $piece['note'] ?? null,
        ];

        if (! $this->estApplicable($piece, $contexte)) {
            return $base + ['statut' => 'non_applicable', 'commentaire' => 'Pièce non applicable au contexte.'];
        }
        if (! is_array($document) || empty($document['date_emission'])) {
            return $base + [
                'statut' => $obligatoire ? 'manquante' : 'a_completer',
                'commentaire' => $obligatoire ? 'Pièce obligatoire absente du dossier.' : 'Pièce facultative non renseignée.',
            ];
        }

        $dateEmission = (string) $document['date_emission'];
        $valide = $base['validite_mois'] === 0
            || $this->regles->pieceEstValide($dateEmission, $base['validite_mois'], $dateDepot);

        return $base + [
            'statut' => $valide ? 'conforme' : 'expiree',
            'date_emission' => $dateEmission,
            'commentaire' => $valide ? 'Pièce conforme à la date de dépôt.' : 'Pièce expirée à la date de dépôt prévue.',
        ];
    }

    protected function estApplicable(array $piece, array $contexte): bool
    {
        $condition = strtolower((string) ($piece['condition_code'] ?? ''));
        if ($condition === '') {
            return true;
        }

        $type = strtolower((string) ($contexte['type_societe'] ?? ''));
        $mode = strtolower((string) ($contexte['mode_administration'] ?? ''));
        $personne = strtolower((string) ($contexte['type_personne'] ?? 'personne_physique'));

        return match ($condition) {
            'sa_ag' => $type === 'sa' && in_array($mode, ['ag', 'assemblee_generale'], true),
            'sa_ca', 'societe_ca' => $type === 'sa' && in_array($mode, ['ca', 'conseil_administration'], true),
            'societe_gerance', 'gerant_non_statutaire' => in_array($mode, ['gerance', 'gerant_unique'], true),
            'associe_mineur_non_emancipe' => ! empty($contexte['associe_mineur']),
            'associe_morale_etrangere' => $personne === 'personne_morale_etrangere',
            'associe_morale_malagasy' => $personne === 'personne_morale_malagasy',
            'personne_morale' => in_array($personne, ['personne_morale_etrangere', 'personne_morale_malagasy'], true),
            'sous_location' => ! empty($contexte['sous_location']),
            'activite_reglementee' => ! empty($contexte['activite_reglementee']),
            'mandataire' => ! empty($contexte['mandataire']),
            'cac_physique' => ! empty($contexte['cac_physique']),
            'cac_morale' => ! empty($contexte['cac_morale']),
            default => true,
        };
    }

    private function normaliserContexte(array $contexte): array
    {
        $contexte['documents'] = $contexte['documents'] ?? [];
        return $contexte;
    }
}
