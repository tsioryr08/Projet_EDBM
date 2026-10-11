<?php

namespace App\Libraries;

use DateTimeImmutable;

/**
 * Fonctions de calcul du Lot 2. PHP pur (aucune dépendance CI4) : testable seul.
 * Les délais, quorums et majorités viennent de la base (tables regle_*), jamais écrits en dur ici.
 */
class CalculRegles
{
    /**
     * Date limite d'envoi de la convocation.
     *  - calendaire : réunion - délai
     *  - franc      : réunion - délai - 1 (ni le jour d'envoi ni le jour de réunion ne comptent)
     *  - ouvrable   : date "franc", puis recul au jour ouvrable précédent (lundi-vendredi, hors fériés) [à confirmer]
     *
     * Exemple : réunion le 2026-12-15, délai 15 -> calendaire 2026-11-30 ; franc 2026-11-29.
     *
     * @param string[] $joursFeries dates 'Y-m-d' (table jour_ferie), utilisé seulement en mode ouvrable
     */
    public function calculerDateConvocation(string $dateReunion, int $delaiJours, string $mode = 'franc', array $joursFeries = []): DateTimeImmutable
    {
        $date = new DateTimeImmutable($dateReunion);

        switch ($mode) {
            case 'calendaire':
                return $date->modify("-{$delaiJours} days");

            case 'franc':
                return $date->modify('-' . ($delaiJours + 1) . ' days');

            case 'ouvrable':
                $date = $date->modify('-' . ($delaiJours + 1) . ' days');
                while (! $this->estOuvrable($date, $joursFeries)) {
                    $date = $date->modify('-1 day');
                }
                return $date;

            default:
                throw new \InvalidArgumentException("Mode de calcul inconnu : {$mode}");
        }
    }

    private function estOuvrable(DateTimeImmutable $d, array $joursFeries): bool
    {
        $weekend = (int) $d->format('N') >= 6; // 6 = samedi, 7 = dimanche
        return ! $weekend && ! in_array($d->format('Y-m-d'), $joursFeries, true);
    }

    /**
     * Pièce valide si elle n'est pas périmée à la date de dépôt.
     * La base stocke la validité en MOIS (validite_mois : 3 ou 12).
     */
    public function pieceEstValide(string $datePiece, int $validiteMois, string $dateDepot): bool
    {
        $piece = new DateTimeImmutable($datePiece);
        // addMonths avec plafonnement à la fin du mois (30 nov + 3 mois = 28/29 fév, pas 2 mars)
        $jour  = (int) $piece->format('j');
        $debut = $piece->modify('first day of this month')->modify("+{$validiteMois} months");
        $fin   = $debut->modify('+' . (min($jour, (int) $debut->format('t')) - 1) . ' days');

        return $fin >= new DateTimeImmutable($dateDepot);
    }

    /**
     * Quorum atteint ? Arithmétique entière (pas de flottants).
     *
     * @param array $participants lignes avec 'statut' (present|represente|absent) et 'voix'
     * @param array $regle        ligne de regle_quorum (quorum_num, quorum_den)
     */
    public function calculerQuorum(array $participants, array $regle, int $voixTotales): bool
    {
        if ((int) $regle['quorum_num'] === 0) {
            return true; // aucun quorum (ex. AGO de SA, 2e convocation)
        }
        $voixPresentes = 0;
        foreach ($participants as $p) {
            if ($p['statut'] !== 'absent') {
                $voixPresentes += (int) $p['voix'];
            }
        }
        // voixPresentes / voixTotales >= num / den
        return $voixPresentes * (int) $regle['quorum_den'] >= $voixTotales * (int) $regle['quorum_num'];
    }

    /**
     * Résolution adoptée ?
     *
     * @param array $voix   ['pour' => int, 'contre' => int, 'abstention' => int, 'blanc' => int]
     * @param array $regle  ligne de regle_quorum (majorite_base, majorite_num, majorite_den, majorite_stricte)
     * @param int   $voixPresentesOuRepresentees utilisé si majorite_base = presents_ou_representes
     */
    public function resolutionAdoptee(array $voix, array $regle, int $voixPresentesOuRepresentees = 0): bool
    {
        if ($regle['majorite_base'] === null) {
            throw new \RuntimeException('Majorité non renseignée pour cette règle (à chercher).');
        }
        // Bulletins blancs non comptés (art. 570 et 571). Abstentions : exclues aussi (à confirmer).
        $base = $regle['majorite_base'] === 'voix_exprimees'
            ? (int) $voix['pour'] + (int) $voix['contre']
            : $voixPresentesOuRepresentees;

        $gauche = (int) $voix['pour'] * (int) $regle['majorite_den'];
        $droite = $base * (int) $regle['majorite_num'];

        return (int) $regle['majorite_stricte'] === 1 ? $gauche > $droite : $gauche >= $droite;
    }

    /**
     * Procuration valide ?
     *  - on ne se représente pas soi-même ;
     *  - un mandant ne donne qu'un pouvoir par séance ;
     *  - au CA : un mandataire ne détient qu'une procuration par séance (art. 485), sauf statuts contraires.
     *
     * @param array $pouvoirsExistants liste de ['mandant_id' => int, 'mandataire_id' => int]
     */
    public function procurationValide(string $natureReunion, int $mandantId, int $mandataireId, array $pouvoirsExistants, int $maxParMandataire = 1): bool
    {
        if ($mandantId === $mandataireId) {
            return false;
        }
        $detenues = 0;
        foreach ($pouvoirsExistants as $p) {
            if ((int) $p['mandant_id'] === $mandantId) {
                return false;
            }
            if ((int) $p['mandataire_id'] === $mandataireId) {
                $detenues++;
            }
        }
        return $natureReunion !== 'CA' || $detenues < $maxParMandataire;
    }
}
