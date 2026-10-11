<?php

namespace App\Libraries;

use App\Models\AssocieModel;
use App\Models\DirigeantModel;
use App\Models\SocieteModel;
use App\Models\TypeSocieteModel;

/**
 * T2 : l'objet « societe » du contrat d'interface (entrée du générateur de statuts).
 * Ne recalcule aucune règle juridique : les paramètres du type viennent de la base,
 * et une valeur null veut dire « à chercher » (le gabarit doit l'afficher ainsi, pas l'inventer).
 */
class SocieteService
{
    private const MODES_ADMINISTRATION = [
        'AG' => 'Administrateur général',
        'CA' => "Conseil d'administration",
    ];

    public function versJson(int $societeId): array
    {
        $societe = (new SocieteModel())->find($societeId)
            ?? throw new \RuntimeException("Société #{$societeId} introuvable.");
        $type = (new TypeSocieteModel())->find($societe['type_societe_id']);

        // ---- associés ----
        $lignesAssocies = (new AssocieModel())->where('societe_id', $societeId)->orderBy('id')->findAll();
        $noms         = array_column($lignesAssocies, 'nom', 'id');
        $totalTitres  = array_sum(array_map(fn ($a) => (int) $a['nb_titres'], $lignesAssocies));
        $totalApports = 0.0;

        $associes = [];
        foreach ($lignesAssocies as $a) {
            $totalApports += (float) $a['apport'];
            $associes[] = [
                'nom'                 => $a['nom'],
                'categorie'           => $a['categorie'],            // physique | morale_malagasy | morale_etrangere
                'date_naissance'      => $a['date_naissance'],
                'mineur_non_emancipe' => (bool) $a['mineur_non_emancipe'],
                'apport'              => $this->montant($a['apport']),
                'nb_titres'           => (int) $a['nb_titres'],
                'pourcentage_titres'  => $totalTitres > 0 ? round((int) $a['nb_titres'] * 100 / $totalTitres, 2) : null,
                'est_commandite'      => $a['est_commandite'] === null ? null : (bool) $a['est_commandite'], // SCS / SCA
                'conjoint'            => $a['conjoint_de_associe_id'] ? ($noms[$a['conjoint_de_associe_id']] ?? null) : null,
            ];
        }

        // ---- dirigeants ----
        $dirigeants = [];
        foreach ((new DirigeantModel())->where('societe_id', $societeId)->orderBy('id')->findAll() as $d) {
            $dirigeants[] = [
                'nom'               => $d['nom'],
                'fonction'          => $d['fonction'],   // gerant | cogerant | administrateur_general | pdg | pca | dg | administrateur | commandite
                'est_associe'       => $d['associe_id'] !== null,
                'nomme_par_statuts' => (bool) $d['nomme_par_statuts'],
                'actionnaire'       => $d['actionnaire'] === null ? null : (bool) $d['actionnaire'],
                'date_nomination'   => $d['date_nomination'],
                'fin_mandat'        => $d['fin_mandat'],
            ];
        }

        $db = db_connect();
        $commissaires = array_map(
            fn ($c) => ['nom' => $c['nom'], 'categorie' => $c['categorie']],
            $db->table('commissaire_aux_comptes')->where('societe_id', $societeId)->orderBy('id')->get()->getResultArray()
        );
        $dossier = $db->table('dossier')->where('societe_id', $societeId)->get()->getRowArray();

        return [
            'type'                         => $type['code'],
            'type_libelle'                 => $type['libelle'],
            'mode_administration'          => $societe['mode_administration'],        // SA : AG | CA ; sinon null
            'mode_administration_libelle'  => self::MODES_ADMINISTRATION[$societe['mode_administration'] ?? ''] ?? null,
            'denomination'                 => $societe['denomination'],
            'siege'                        => $societe['siege'],
            'objet'                        => $societe['objet'],
            'capital'                      => $this->montant($societe['capital']),
            'duree_annees'                 => $societe['duree_annees'] !== null ? (int) $societe['duree_annees'] : null,
            'code_activite'                => $societe['code_activite'],
            'activite_reglementee'         => (bool) $societe['activite_reglementee'],
            'nombre_associes'              => count($associes),
            'total_titres'                 => $totalTitres,
            'total_apports'                => $this->montant($totalApports),
            'associes'                     => $associes,
            'dirigeants'                   => $dirigeants,
            'commissaires_aux_comptes'     => $commissaires,
            // paramètres du type lus en base : null = « à chercher » (ne pas inventer de valeur)
            'parametres_type' => [
                'est_commerciale'    => (bool) $type['est_commerciale'],
                'dans_liste_orinasa' => (bool) $type['dans_liste_orinasa'],
                'organe_direction'   => $type['organe_direction'],
                'associes_min'       => $type['associes_min'] !== null ? (int) $type['associes_min'] : null,
                'associes_max'       => $type['associes_max'] !== null ? (int) $type['associes_max'] : null,
                'capital_minimum'    => $this->montant($type['capital_minimum']),
                'note'               => $type['note'],
            ],
            'statut_dossier'               => $dossier['statut'] ?? null,            // brouillon | soumis | valide | immatricule
        ];
    }

    /** Montant en Ariary : entier si la valeur est ronde, null si absent. */
    private function montant(mixed $valeur): int|float|null
    {
        if ($valeur === null) {
            return null;
        }
        $f = (float) $valeur;
        return floor($f) === $f ? (int) $f : $f;
    }
}
