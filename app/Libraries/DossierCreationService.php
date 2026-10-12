<?php

namespace App\Libraries;

use App\Models\AssocieModel;
use App\Models\DirigeantModel;
use App\Models\SocieteModel;
use App\Models\RegleValidationModel;
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
class DossierCreationService
{
    public function __construct(
        private ?SocieteService $societeService = null,
        private ?DocumentChecklistService $checklistService = null,
        private ?RegleValidationModel $reglesValidation = null,
    ) {
        $this->societeService = $societeService ?? new SocieteService();
        $this->checklistService = $checklistService ?? new DocumentChecklistService();
        $this->reglesValidation = $reglesValidation ?? new RegleValidationModel();
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
        $type = (new TypeSocieteModel())->where('code', strtoupper($typeSociete))->first();
        $typeId = is_array($type) ? (int) $type['id'] : null;

        $ajouterRegleAbsente = function (string $code) use (&$bloquants): void {
            $bloquants[] = "Règle de validation {$code} absente de la base : contrôle impossible, à confirmer.";
        };
        $appliquerPlage = function (string $code, float $valeur) use (&$bloquants, &$warnings, $typeId, $ajouterRegleAbsente): ?array {
            $regle = $this->reglesValidation->trouver($code, $typeId);
            if (! $regle) {
                $ajouterRegleAbsente($code);
                return null;
            }
            $horsMin = $regle['valeur_min'] !== null && $valeur < (float) $regle['valeur_min'];
            $horsMax = $regle['valeur_max'] !== null && $valeur > (float) $regle['valeur_max'];
            if ($horsMin || $horsMax) {
                ($regle['severite'] ?? 'bloquant') === 'avertissement'
                    ? $warnings[] = $regle['message']
                    : $bloquants[] = $regle['message'];
            }
            return $regle;
        };

        $nbAdministrateurs = count($dirigeants);
        if (strtoupper($typeSociete) === 'SA') {
            $appliquerPlage('SA_ADMINISTRATEURS', $nbAdministrateurs);
            $parite = $this->reglesValidation->trouver('SA_ADMIN_PARITE', $typeId);
            if (! $parite) {
                $ajouterRegleAbsente('SA_ADMIN_PARITE');
            } elseif ($nbAdministrateurs % 2 === 0) {
                ($parite['severite'] ?? 'avertissement') === 'avertissement'
                    ? $warnings[] = $parite['message']
                    : $bloquants[] = $parite['message'];
            }
            $nonActionnaires = count(array_filter($dirigeants, static fn (array $dirigeant): bool => isset($dirigeant['actionnaire']) && ! (bool) $dirigeant['actionnaire']));
            $appliquerPlage('SA_NON_ACTIONNAIRES', $nbAdministrateurs > 0 ? $nonActionnaires / $nbAdministrateurs : 0);
            $ag = $this->reglesValidation->trouver('SA_ADMIN_GENERAL', $typeId);
            if (! $ag) {
                $ajouterRegleAbsente('SA_ADMIN_GENERAL');
            } elseif (count($associes) <= (float) $ag['valeur_max'] && ! $this->contientFonction($dirigeants, ['administrateur_general', 'administrateur général', 'administrateur general', 'ag'])) {
                $bloquants[] = $ag['message'];
            }
        }

        if (strtoupper($typeSociete) === 'SNC') {
            $mineur = $this->reglesValidation->trouver('SNC_MINEUR', $typeId);
            $epoux = $this->reglesValidation->trouver('SNC_EPOUX', $typeId);
            if (! $mineur) $ajouterRegleAbsente('SNC_MINEUR');
            elseif ($this->aUnAssocieMineur($associes) && (float) $mineur['valeur_max'] === 0) $bloquants[] = $mineur['message'];
            if (! $epoux) $ajouterRegleAbsente('SNC_EPOUX');
            elseif ($this->aUnConjoint($associes) && (float) $epoux['valeur_max'] === 0) $bloquants[] = $epoux['message'];
        }

        $duree = $societe['duree_annees'] ?? null;
        $dureeRegle = $this->reglesValidation->trouver('duree_max', null);
        if (! $dureeRegle) {
            $ajouterRegleAbsente('duree_max');
        } elseif ($duree !== null && $duree !== '' && $dureeRegle['valeur_max'] !== null && (float) $duree > (float) $dureeRegle['valeur_max']) {
            $bloquants[] = $dureeRegle['message'];
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
