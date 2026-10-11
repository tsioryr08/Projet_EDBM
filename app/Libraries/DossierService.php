<?php

namespace App\Libraries;

use App\Models\AssocieModel;
use App\Models\DirigeantModel;
use App\Models\SocieteModel;
use App\Models\TypeSocieteModel;

/**
 * T6 - État du dossier + contrôles de validité à la création.
 *
 * Cette couche relie :
 * - la société,
 * - les dirigeants et associés,
 * - la checklist documentaire,
 * - les contrôles juridiques de création.
 */
class DossierService
{
    public function __construct(
        private ?SocieteService $societeService = null,
        private ?DocumentChecklistService $checklistService = null,
    ) {
        $this->societeService = $societeService ?? new SocieteService();
        $this->checklistService = $checklistService ?? new DocumentChecklistService();
    }

    /**
     * Évalue l’état du dossier d’une société donnée.
     */
    public function evaluerDossier(int $societeId, array $contexte = []): array
    {
        $societe = (new SocieteModel())->find($societeId);
        $societeData = is_array($societe) ? $societe : [
            'denomination' => $contexte['denomination'] ?? null,
            'type_societe_id' => null,
            'duree_annees' => $contexte['duree_annees'] ?? null,
            'mode_administration' => $contexte['mode_administration'] ?? null,
            'capital' => $contexte['capital'] ?? null,
        ];

        $typeSociete = $this->recupererTypeSociete($societeData, $contexte);
        $associes = (new AssocieModel())->where('societe_id', $societeId)->orderBy('id')->findAll();
        $dirigeants = (new DirigeantModel())->where('societe_id', $societeId)->orderBy('id')->findAll();

        if ($societe === null && ! empty($contexte['denomination']) && empty($associes)) {
            $associes = $contexte['associes'] ?? [];
            $dirigeants = $contexte['dirigeants'] ?? [];
        }

        $checkContext = [
            'type_societe' => $typeSociete,
            'mode_administration' => strtolower((string) ($contexte['mode_administration'] ?? $societeData['mode_administration'] ?? 'gerance')),
            'type_personne' => strtolower((string) ($contexte['type_personne'] ?? 'personne_physique')),
            'associe_mineur' => ! empty($contexte['associe_mineur']) || $this->aUnAssocieMineur($associes),
            'sous_location' => ! empty($contexte['sous_location']),
            'activite_reglementee' => ! empty($contexte['activite_reglementee']),
            'date_depot_prevue' => (string) ($contexte['date_depot_prevue'] ?? date('Y-m-d')),
            'documents' => $contexte['documents'] ?? [],
        ];

        $pieces = $this->checklistService->buildChecklistForType($typeSociete, $checkContext);
        $evaluation = $this->checklistService->genererChecklist($checkContext, $pieces);
        $validite = $this->verifierValiditeCreation($societeData, $associes, $dirigeants, $typeSociete);

        return [
            'societe_id' => $societeId,
            'societe_nom' => $societeData['denomination'] ?? null,
            'type_societe' => $typeSociete,
            'mode_administration' => $checkContext['mode_administration'],
            'date_depot_prevue' => $checkContext['date_depot_prevue'],
            'dossier_statut' => $evaluation['dossier_statut'],
            'pieces_obligatoires' => $evaluation['pieces_obligatoires'],
            'pieces_valides' => $evaluation['pieces_valides'],
            'pieces_manquantes' => $evaluation['pieces_manquantes'],
            'pieces_expirees' => $evaluation['pieces_expirees'],
            'pieces_a_confirmer' => $evaluation['pieces_a_confirmer'],
            'validite_creation' => $validite,
            'resume' => $this->resume($evaluation, $validite),
            'pieces' => $evaluation['pieces'],
        ];
    }

    protected function recupererTypeSociete(array $societeData, array $contexte): string
    {
        $typeSociete = strtoupper((string) ($contexte['type_societe'] ?? ''));
        if ($typeSociete !== '') {
            return $typeSociete;
        }

        if (! empty($societeData['type_societe_id'])) {
            $type = (new TypeSocieteModel())->find($societeData['type_societe_id']);
            if (is_array($type) && ! empty($type['code'])) {
                return strtoupper((string) $type['code']);
            }
        }

        return 'SARL';
    }

    protected function verifierValiditeCreation(array $societe, array $associes, array $dirigeants, string $typeSociete): array
    {
        $bloquants = [];
        $warnings = [];

        $nbAdministrateurs = count($dirigeants);
        if ($nbAdministrateurs < 3 || $nbAdministrateurs > 12) {
            $bloquants[] = 'Le nombre d’administrateurs doit être compris entre 3 et 12 (art. 439).';
        }

        if ($nbAdministrateurs % 2 === 0) {
            $warnings[] = 'Nombre d’administrateurs pair : avertissement uniquement, pas un blocage.';
        }

        $nonActionnaires = array_values(array_filter($dirigeants, static function (array $dirigeant): bool {
            return isset($dirigeant['actionnaire']) && ((bool) $dirigeant['actionnaire']) === false;
        }));

        if ($nbAdministrateurs > 0 && count($nonActionnaires) / $nbAdministrateurs > 1 / 3) {
            $bloquants[] = 'Le nombre de non-actionnaires ne peut pas dépasser un tiers des administrateurs (art. 440).';
        }

        $agObligatoire = count($associes) <= 3;
        if ($agObligatoire && ! $this->contientFonction($dirigeants, ['administrateur_general', 'administrateur général', 'administrateur general', 'ag'])) {
            $bloquants[] = 'Administrateur général obligatoire si la société compte 3 actionnaires ou moins (art. 519).';
        }

        if (strtoupper($typeSociete) === 'SNC') {
            if ($this->aUnAssocieMineur($associes)) {
                $bloquants[] = 'SNC : un associé mineur non émancipé est interdit (art. 5 et 6).';
            }

            if ($this->aUnConjoint($associes)) {
                $bloquants[] = 'SNC : les époux associés ne sont pas admis dans la même société (art. 5 et 6).';
            }
        }

        $duree = $societe['duree_annees'] ?? null;
        if ($duree !== null && $duree !== '' && (int) $duree > 99) {
            $bloquants[] = 'La durée de la société ne peut pas dépasser 99 ans.';
        }

        $nom = trim((string) ($societe['denomination'] ?? ''));
        if ($nom === '' || ! preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ0-9&\'\s\-\.]+$/u', $nom)) {
            $bloquants[] = 'Le format du nom de la société est invalide.';
        }

        $message = empty($bloquants)
            ? 'Cotation de création : conforme aux contrôles de validité.'
            : 'Cotation de création : blocage sur les règles de création.';

        return [
            'valide' => empty($bloquants),
            'bloquants' => $bloquants,
            'warnings' => $warnings,
            'message' => $message,
        ];
    }

    protected function contientFonction(array $dirigeants, array $noms): bool
    {
        foreach ($dirigeants as $dirigeant) {
            $fonction = strtolower((string) ($dirigeant['fonction'] ?? ''));
            $fonction = str_replace(['_', ' - ', '  '], [' ', ' ', ' '], $fonction);
            $fonction = trim($fonction);
            foreach ($noms as $nom) {
                if ($fonction === strtolower((string) $nom)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function aUnAssocieMineur(array $associes): bool
    {
        foreach ($associes as $associe) {
            if (! empty($associe['mineur_non_emancipe']) || (isset($associe['mineur_non_emancipe']) && (int) $associe['mineur_non_emancipe'] === 1)) {
                return true;
            }
        }

        return false;
    }

    protected function aUnConjoint(array $associes): bool
    {
        foreach ($associes as $associe) {
            if (! empty($associe['conjoint_de_associe_id'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Résumé lisible pour l’interface.
     */
    public function resume(array $evaluation, array $validite = []): string
    {
        $message = 'Dossier conforme : toutes les pièces obligatoires sont présentes et valides.';

        if (! empty($validite['bloquants'])) {
            $message = 'Dossier non conforme à la création : vérifiez les règles juridiques de structure et d’administration.';
        } elseif (! empty($validite['warnings'])) {
            $message = 'Dossier conforme aux règles de création, avec avertissement à vérifier.';
        } elseif (($evaluation['pieces_manquantes'] ?? 0) > 0) {
            $message = 'Dossier incomplet : pièces manquantes à compléter avant dépôt.';
        } elseif (($evaluation['pieces_expirees'] ?? 0) > 0) {
            $message = 'Dossier invalide : des pièces ont expiré avant la date de dépôt prévue.';
        } elseif (($evaluation['pieces_a_confirmer'] ?? 0) > 0) {
            $message = 'Dossier sous réserve : certaines pièces exigent une confirmation juridique.';
        }

        return $message;
    }
}
