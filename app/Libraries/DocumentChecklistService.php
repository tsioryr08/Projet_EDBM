<?php

namespace App\Libraries;

use DateTimeImmutable;

/**
 * T5 - Checklist documentaire.
 *
 * Les pièces sont lues dans la base (table document_requis + document_type_societe)
 * puis filtrées selon le contexte de la société, du dossier et des cas particuliers.
 * Chaque pièce est ensuite vérifiée selon sa date d'émission et sa validité en mois.
 */
class DocumentChecklistService
{
    protected CalculRegles $regles;

    public function __construct(?CalculRegles $regles = null)
    {
        $this->regles = $regles ?? new CalculRegles();
    }

    /**
     * Génère la checklist complète pour un dossier.
     *
     * @param array $contexte
     *   Exemple :
     *   [
     *      'type_societe' => 'SARL',
     *      'mode_administration' => 'gerance',
     *      'date_depot_prevue' => '2026-10-11',
     *      'documents' => ['STATUTS_SARL' => ['date_emission' => '2026-01-10']],
     *      'associe_mineur' => false,
     *      'sous_location' => false,
     *      'activite_reglementee' => false,
     *      'type_personne' => 'personne_physique',
     *   ]
     * @param array|null $documents
     *   Si fourni, remplace le chargement depuis la base.
     */
    public function genererChecklist(array $contexte, ?array $documents = null): array
    {
        $contexte = $this->normaliserContexte($contexte);
        $pieces = $documents ?? $this->buildChecklistForType($contexte['type_societe'] ?? '', $contexte);
        $resultats = [];

        foreach ($pieces as $piece) {
            $resultats[] = $this->verifierPiece($piece, $contexte);
        }

        $obligatoires = array_values(array_filter($resultats, static fn (array $item): bool => (int) ($item['obligatoire'] ?? 0) === 1));
        $manquantes = array_values(array_filter($obligatoires, static fn (array $item): bool => $item['statut'] === 'manquante'));
        $expirees = array_values(array_filter($obligatoires, static fn (array $item): bool => $item['statut'] === 'expiree'));
        $valides = array_values(array_filter($obligatoires, static fn (array $item): bool => $item['statut'] === 'conforme'));
        $aConfirmer = array_values(array_filter($obligatoires, static fn (array $item): bool => $item['statut'] === 'a_confirmer'));

        $dossierStatut = 'complet';
        if (count($manquantes) > 0 || count($expirees) > 0 || count($aConfirmer) > 0) {
            $dossierStatut = 'incomplet';
        }

        return [
            'dossier_statut' => $dossierStatut,
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
        $typeSociete = strtoupper(trim($typeSociete));
        $templates = $this->getChecklistTemplates();
        $template = $templates[$typeSociete] ?? $templates['SARL'];

        $contexte = $this->normaliserContexte($contexte + ['type_societe' => $typeSociete]);
        $pieces = [];

        foreach ($template as $code => $def) {
            $applicable = true;
            if (($def['condition'] ?? null) !== null) {
                $applicable = $this->appliquerCondition($def['condition'], $contexte);
            }

            if ($applicable) {
                $pieces[] = [
                    'code' => $code,
                    'categorie' => $def['categorie'],
                    'libelle' => $def['libelle'],
                    'condition_code' => $def['condition'] ?? null,
                    'validite_mois' => $def['validite_mois'],
                    'obligatoire' => $def['obligatoire'] ?? 1,
                    'note' => $def['note'] ?? null,
                ];
            }
        }

        return $pieces;
    }

    public function getChecklistTemplates(): array
    {
        return [
            'SARL' => [
                'STATUTS_SARL' => ['categorie' => 'etablissement', 'libelle' => 'Statuts de la SARL', 'condition' => null, 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Pièce de base'],
                'DIR_ID' => ['categorie' => 'dirigeant', 'libelle' => 'Pièce d’identité du gérant', 'condition' => null, 'validite_mois' => 3, 'obligatoire' => 1, 'note' => 'Pièce d’identité'],
                'ASSOC_ID' => ['categorie' => 'associe', 'libelle' => 'Pièce d’identité de l’associé', 'condition' => null, 'validite_mois' => 3, 'obligatoire' => 1, 'note' => 'Pièce d’identité'],
                'DIR_RESID' => ['categorie' => 'dirigeant', 'libelle' => 'Certificat de résidence', 'condition' => null, 'validite_mois' => 3, 'obligatoire' => 1, 'note' => 'Vérification résidence'],
                'SIEGE_BAIL' => ['categorie' => 'siege', 'libelle' => 'Contrat de bail du siège', 'condition' => null, 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Siège social'],
                'SIEGE_SOUSLOC' => ['categorie' => 'siege', 'libelle' => 'Contrat de sous-location', 'condition' => 'sous_location', 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Applicable en cas de sous-location'],
                'ASSOC_MINEUR' => ['categorie' => 'associe', 'libelle' => 'Autorisation du représentant légal', 'condition' => 'associe_mineur_non_emancipe', 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Mineur ou non émancipé'],
                'ACT_REGLEMENTEE' => ['categorie' => 'activite', 'libelle' => 'Autorisation réglementée', 'condition' => 'activite_reglementee', 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Applicable si activité réglementée'],
            ],
            'SA' => [
                'STATUTS_SA_CA' => ['categorie' => 'etablissement', 'libelle' => 'Statuts de la SA', 'condition' => 'societe_ca', 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'SA avec CA'],
                'STATUTS_SA_AG' => ['categorie' => 'etablissement', 'libelle' => 'Statuts de la SA', 'condition' => 'sa_ag', 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'SA avec AG'],
                'BULLETIN_SOUSCRIPTION' => ['categorie' => 'capital', 'libelle' => 'Bulletin de souscription', 'condition' => null, 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Capital social'],
                'DECL_SOUSCRIPTION' => ['categorie' => 'capital', 'libelle' => 'Déclaration de souscription', 'condition' => null, 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Capital social'],
                'ASSOC_ID' => ['categorie' => 'associe', 'libelle' => 'Pièce d’identité des actionnaires', 'condition' => null, 'validite_mois' => 3, 'obligatoire' => 1, 'note' => 'Tous actionnaires'],
                'DIR_ID' => ['categorie' => 'dirigeant', 'libelle' => 'Pièce d’identité des dirigeants', 'condition' => null, 'validite_mois' => 3, 'obligatoire' => 1, 'note' => 'Dirigeants'],
                'DIR_RESID' => ['categorie' => 'dirigeant', 'libelle' => 'Certificat de résidence des dirigeants', 'condition' => null, 'validite_mois' => 3, 'obligatoire' => 1, 'note' => 'Dirigeants'],
                'SIEGE_BAIL' => ['categorie' => 'siege', 'libelle' => 'Contrat de bail du siège', 'condition' => null, 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Siège social'],
                'PV_AGC' => ['categorie' => 'etablissement', 'libelle' => 'Procès-verbal de l’assemblée constitutive', 'condition' => null, 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Constitution'],
                'ACT_REGLEMENTEE' => ['categorie' => 'activite', 'libelle' => 'Autorisation réglementée', 'condition' => 'activite_reglementee', 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Activité réglementée'],
            ],
            'SNC' => [
                'STATUTS_SCS_SNC' => ['categorie' => 'etablissement', 'libelle' => 'Statuts de la SNC', 'condition' => null, 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Statuts'],
                'ASSOC_ID' => ['categorie' => 'associe', 'libelle' => 'Pièce d’identité des associés', 'condition' => null, 'validite_mois' => 3, 'obligatoire' => 1, 'note' => 'Associés'],
                'SIEGE_BAIL' => ['categorie' => 'siege', 'libelle' => 'Contrat de bail du siège', 'condition' => null, 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Siège'],
                'ASSOC_MINEUR' => ['categorie' => 'associe', 'libelle' => 'Autorisation du représentant légal', 'condition' => 'associe_mineur_non_emancipe', 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Mineur'],
                'ACT_REGLEMENTEE' => ['categorie' => 'activite', 'libelle' => 'Autorisation réglementée', 'condition' => 'activite_reglementee', 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Activité réglementée'],
            ],
            'SCS' => [
                'STATUTS_SCS_SNC' => ['categorie' => 'etablissement', 'libelle' => 'Statuts de la SCS', 'condition' => null, 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Statuts'],
                'ASSOC_ID' => ['categorie' => 'associe', 'libelle' => 'Pièce d’identité des associés commanditaires', 'condition' => null, 'validite_mois' => 3, 'obligatoire' => 1, 'note' => 'Commanditaires'],
                'DIR_ID' => ['categorie' => 'dirigeant', 'libelle' => 'Pièce d’identité du gérant', 'condition' => null, 'validite_mois' => 3, 'obligatoire' => 1, 'note' => 'Gérant'],
                'SIEGE_BAIL' => ['categorie' => 'siege', 'libelle' => 'Contrat de bail du siège', 'condition' => null, 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Siège'],
                'ACT_REGLEMENTEE' => ['categorie' => 'activite', 'libelle' => 'Autorisation réglementée', 'condition' => 'activite_reglementee', 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Activité réglementée'],
            ],
            'SC' => [
                'STATUTS_SC' => ['categorie' => 'etablissement', 'libelle' => 'Statuts de la société civile', 'condition' => null, 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Statuts'],
                'ASSOC_ID' => ['categorie' => 'associe', 'libelle' => 'Pièce d’identité des associés', 'condition' => null, 'validite_mois' => 3, 'obligatoire' => 1, 'note' => 'Associés'],
                'SIEGE_BAIL' => ['categorie' => 'siege', 'libelle' => 'Contrat de bail du siège', 'condition' => null, 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Siège'],
                'ASSOC_MINEUR' => ['categorie' => 'associe', 'libelle' => 'Autorisation du représentant légal', 'condition' => 'associe_mineur_non_emancipe', 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Mineur'],
                'ACT_REGLEMENTEE' => ['categorie' => 'activite', 'libelle' => 'Autorisation réglementée', 'condition' => 'activite_reglementee', 'validite_mois' => 12, 'obligatoire' => 1, 'note' => 'Activité réglementée'],
            ],
        ];
    }

    protected function appliquerCondition(string $condition, array $contexte): bool
    {
        switch ($condition) {
            case 'sous_location':
                return ! empty($contexte['sous_location']);
            case 'associe_mineur_non_emancipe':
                return ! empty($contexte['associe_mineur']);
            case 'activite_reglementee':
                return ! empty($contexte['activite_reglementee']);
            case 'sa_ag':
                return strtolower((string) ($contexte['type_societe'] ?? '')) === 'sa' && in_array(strtolower((string) ($contexte['mode_administration'] ?? '')), ['ag', 'assemblee_generale'], true);
            case 'societe_ca':
                return strtolower((string) ($contexte['type_societe'] ?? '')) === 'sa' && in_array(strtolower((string) ($contexte['mode_administration'] ?? '')), ['ca', 'conseil_administration'], true);
            default:
                return true;
        }
    }

    protected function normaliserContexte(array $contexte): array
    {
        $contexte['type_societe'] = strtoupper((string) ($contexte['type_societe'] ?? ''));
        $contexte['mode_administration'] = strtolower((string) ($contexte['mode_administration'] ?? ''));
        $contexte['type_personne'] = strtolower((string) ($contexte['type_personne'] ?? 'personne_physique'));
        $contexte['associe_mineur'] = ! empty($contexte['associe_mineur']);
        $contexte['sous_location'] = ! empty($contexte['sous_location']);
        $contexte['activite_reglementee'] = ! empty($contexte['activite_reglementee']);
        $contexte['mandataire'] = ! empty($contexte['mandataire']);
        $contexte['documents'] = $contexte['documents'] ?? [];

        return $contexte;
    }

    /**
     * Charge les documents requis depuis la base selon le contexte.
     */
    public function chargerDepuisBase(array $contexte): array
    {
        $db = db_connect();
        $builder = $db->table('document_requis dr');
        $builder->select('dr.*');

        if (! empty($contexte['type_societe'])) {
            $builder->join('document_type_societe dts', 'dts.document_id = dr.id', 'inner');
            $builder->join('type_societe ts', 'ts.id = dts.type_societe_id', 'inner');
            $builder->where('ts.code', strtoupper((string) $contexte['type_societe']));
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Vérifie la validité d'une pièce selon le contexte juridique du dossier.
     */
    public function verifierPiece(array $piece, array $contexte): array
    {
        $code = (string) ($piece['code'] ?? '');
        $libelle = $piece['libelle'] ?? $code;
        $obligatoire = (int) ($piece['obligatoire'] ?? $piece['required'] ?? 0);
        $dateDepot = (string) ($contexte['date_depot_prevue'] ?? date('Y-m-d'));
        $documents = $contexte['documents'] ?? [];

        if (! $this->estApplicable($piece, $contexte)) {
            return [
                'code' => $code,
                'libelle' => $libelle,
                'obligatoire' => $obligatoire,
                'statut' => 'non_applicable',
                'commentaire' => 'Pièce non applicable au contexte juridique du dossier.',
            ];
        }

        $document = $documents[$code] ?? null;
        if (! is_array($document) || empty($document['date_emission'])) {
            $statut = $obligatoire === 1 ? 'manquante' : 'a_completer';
            return [
                'code' => $code,
                'libelle' => $libelle,
                'obligatoire' => $obligatoire,
                'statut' => $statut,
                'commentaire' => $obligatoire === 1
                    ? 'Pièce obligatoire absente du dossier.'
                    : 'Pièce facultative non renseignée au moment du dépôt.',
            ];
        }

        $validiteMois = (int) ($piece['validite_mois'] ?? 0);
        $dateEmission = (string) $document['date_emission'];
        $valide = true;

        if ($validiteMois > 0) {
            $valide = $this->regles->pieceEstValide($dateEmission, $validiteMois, $dateDepot);
        }

        return [
            'code' => $code,
            'libelle' => $libelle,
            'obligatoire' => $obligatoire,
            'statut' => $valide ? 'conforme' : 'expiree',
            'date_emission' => $dateEmission,
            'validite_mois' => $validiteMois,
            'date_depot_prevue' => $dateDepot,
            'commentaire' => $valide
                ? 'Pièce conforme et valable à la date de dépôt.'
                : 'Pièce expirée au regard de la date de dépôt prévue.',
        ];
    }

    /**
     * Détermine si la pièce est applicable au contexte.
     */
    protected function estApplicable(array $piece, array $contexte): bool
    {
        $condition = (string) ($piece['condition_code'] ?? '');
        if ($condition === '') {
            return true;
        }

        $typeSociete = strtolower((string) ($contexte['type_societe'] ?? ''));
        $modeAdministration = strtolower((string) ($contexte['mode_administration'] ?? ''));
        $associeMineur = ! empty($contexte['associe_mineur']);
        $sousLocation = ! empty($contexte['sous_location']);
        $activiteReglementee = ! empty($contexte['activite_reglementee']);
        $mandataire = ! empty($contexte['mandataire']);
        $typePersonne = strtolower((string) ($contexte['type_personne'] ?? 'personne_physique'));

        $condition = strtolower($condition);

        switch ($condition) {
            case 'sa_ag':
                return $typeSociete === 'sa' && in_array($modeAdministration, ['ag', 'assemblee_generale'], true);
            case 'sa_ca':
                return $typeSociete === 'sa' && in_array($modeAdministration, ['ca', 'conseil_administration'], true);
            case 'gerant_non_statutaire':
                return in_array($modeAdministration, ['gerance', 'gerant_unique', 'directoire'], true);
            case 'associe_mineur_non_emancipe':
                return $associeMineur === true;
            case 'associe_morale_etrangere':
                return $typePersonne === 'personne_morale_etrangere';
            case 'associe_morale_malagasy':
                return $typePersonne === 'personne_morale_malagasy';
            case 'sous_location':
                return $sousLocation === true;
            case 'activite_reglementee':
                return $activiteReglementee === true;
            case 'mandataire':
                return $mandataire === true;
            case 'cac_physique':
                return ($contexte['cac_physique'] ?? false) === true;
            case 'cac_morale':
                return ($contexte['cac_morale'] ?? false) === true;
            case 'personne_morale':
                return in_array($typePersonne, ['personne_morale_etrangere', 'personne_morale_malagasy'], true);
            case 'societe_ca':
                return $typeSociete === 'sa' && in_array($modeAdministration, ['ca', 'conseil_administration'], true);
            case 'societe_gerance':
                return in_array($modeAdministration, ['gerance', 'gerant_unique'], true);
            default:
                return true;
        }
    }
}
