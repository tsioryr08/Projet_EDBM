<?php

namespace App\Libraries;

/**
 * Construit l'explication juridique d'une décision calculée par le logiciel.
 * Chaque méthode renvoie :
 *   motif          : phrase qui explique le résultat
 *   source         : article / texte (colonne "source" de la règle)
 *   statut         : verifie | a_confirmer | deduction
 *   avertissement  : précision éventuelle (choix du projet, point à confirmer) ou null
 *   texte_officiel : passage de la loi recopié en base (ou null s'il n'est pas encore saisi)
 *
 * PHP pur : aucune dépendance CI4. Les chiffres viennent des règles en base, rien n'est écrit en dur.
 */
class Justification
{
    public const STATUTS = [
        'verifie'     => 'vérifié dans le texte',
        'a_confirmer' => 'à confirmer',
        'deduction'   => 'déduction (conséquence du texte, non écrite telle quelle)',
    ];

    /** Délai de convocation. */
    public function delai(array $regle, string $dateReunion, string $dateConvocation, string $mode, int $delai): array
    {
        $reunion = $this->date($dateReunion);
        $envoi   = $this->date($dateConvocation);

        $calcul = match ($mode) {
            'franc'      => "Calcul en jours francs : ni le jour d'envoi ni le jour de la réunion ne comptent.",
            'ouvrable'   => "Calcul en jours ouvrables : si la date tombe un week-end ou un jour férié, on recule au jour ouvrable précédent.",
            default      => "Calcul en jours calendaires : tous les jours comptent.",
        };

        $forme = ! empty($regle['forme_envoi']) ? " Forme de l'envoi : {$regle['forme_envoi']}." : '';

        return [
            'motif'  => "Délai minimum de {$delai} jours avant la réunion du {$reunion}. {$calcul} La convocation doit donc partir au plus tard le {$envoi}.{$forme}",
            'source' => $regle['source'] ?? null,
            'statut' => ! empty($regle['verifie']) ? 'verifie' : 'a_confirmer',
            'avertissement' => $mode === 'franc'
                ? "Le mode de calcul en jours francs est un choix du projet : les articles lus disent seulement « quinze jours au moins »."
                : null,
            'texte_officiel' => $regle['texte_officiel'] ?? null,
        ];
    }

    /** Quorum. */
    public function quorum(array $regle, int $presentes, int $totaux, bool $atteint): array
    {
        $statut = ! empty($regle['verifie']) ? 'verifie' : 'a_confirmer';

        if ((int) $regle['quorum_num'] === 0) {
            $motif = "Aucun quorum n'est exigé pour cette réunion : elle peut délibérer quel que soit le nombre de présents.";
        } else {
            $base = match ($regle['quorum_base']) {
                'actions_droit_vote' => 'des actions ayant droit de vote',
                'membres_conseil'    => 'des membres du conseil',
                default              => 'des parts ou des associés',
            };
            $pct   = $totaux > 0 ? round($presentes * 100 / $totaux, 1) : 0;
            $motif = 'Quorum requis : ' . $this->exigence((int) $regle['quorum_num'], (int) $regle['quorum_den'], false) . " {$base}. "
                   . "Présents ou représentés : {$presentes} sur {$totaux} ({$pct} %). "
                   . ($atteint ? 'Le quorum est atteint : l\'assemblée peut délibérer.' : "Le quorum n'est pas atteint : l'assemblée ne peut pas délibérer.");
        }

        return [
            'motif'          => $motif,
            'source'         => $regle['source'] ?? null,
            'statut'         => $statut,
            'avertissement'  => $this->avertissementsQuorum($regle, $totaux),
            'texte_officiel' => $regle['texte_officiel'] ?? null,
        ];
    }

    private function avertissementsQuorum(array $regle, int $totaux): ?string
    {
        $av = [];
        if (! empty($regle['note'])) {
            $av[] = $regle['note'] . '.';
        }
        if ($regle['quorum_base'] === 'membres_conseil') {
            $av[] = "À confirmer : le texte parle des membres « présents » ; on compte ici aussi les membres représentés.";
            if ($totaux > 0 && $totaux % 2 === 0) {
                $av[] = "Nombre pair de membres : une égalité de voix est possible (rien n'impose un nombre impair).";
            }
        }
        return $av ? implode(' ', $av) : null;
    }

    /**
     * Adoption d'une résolution.
     *
     * @param ?bool   $adoptee      null = décision en attente (voix du président)
     * @param ?string $sensPresident 'pour' | 'contre' | 'abstention' (abstenu ou absent) | null (pas encore saisi)
     */
    public function adoption(array $regle, array $voix, int $base, ?bool $adoptee, ?string $sensPresident = null): array
    {
        $statut = ! empty($regle['verifie']) ? 'verifie' : 'a_confirmer';
        $avert  = null;

        $voixLabel = $regle['majorite_base'] === 'voix_exprimees'
            ? 'des voix exprimées'
            : 'des voix des membres présents ou représentés';
        $exig = $this->exigence((int) $regle['majorite_num'], (int) $regle['majorite_den'], (int) $regle['majorite_stricte'] === 1);

        $motif = "{$voix['pour']} voix pour et {$voix['contre']} contre, sur une base de {$base} voix. "
               . "Majorité exigée : {$exig} {$voixLabel}. ";

        $egalite = $voix['pour'] === $voix['contre'] && $voix['pour'] > 0;

        if ($egalite && (int) $regle['voix_preponderante_president'] === 1) {
            $motif .= "Partage des voix : la voix du président de séance est prépondérante, sauf disposition contraire des statuts. ";
            if ($sensPresident === null) {
                $motif .= "Décision en attente : indiquez le sens du vote du président de séance.";
            } elseif ($sensPresident === 'abstention') {
                $statut = 'deduction';
                $motif .= "Le président de séance s'est abstenu ou était absent : sa voix prépondérante n'est pas exercée et la résolution n'est pas adoptée (déduction : le texte ne le dit pas expressément).";
            } else {
                $motif .= "Le président de séance a voté « {$sensPresident} » : sa voix l'emporte, la résolution est " . ($adoptee ? 'adoptée.' : 'non adoptée.');
            }
        } else {
            $motif .= 'Résolution ' . ($adoptee ? 'adoptée.' : 'non adoptée.');
            if ($egalite) {
                // Le texte ne parle pas de l'égalité : on le dit honnêtement.
                $statut = 'deduction';
                $motif .= " L'égalité des voix ne donne pas la majorité exigée : c'est une conséquence de la règle, le texte ne prévoit pas de départage dans ce cas.";
            }
        }

        if ($regle['majorite_base'] === 'voix_exprimees' && ($voix['abstention'] ?? 0) > 0) {
            $avert = "Les abstentions ne sont pas comptées dans les voix exprimées (point à confirmer).";
        }

        return [
            'motif'          => $motif,
            'source'         => $regle['source'] ?? null,
            'statut'         => $statut,
            'avertissement'  => $avert,
            'texte_officiel' => $regle['texte_officiel'] ?? null,
        ];
    }

    // ---------------------------------------------------------------

    private function exigence(int $n, int $d, bool $stricte): string
    {
        $cle = "{$n}/{$d}";
        if ($stricte) {
            return match ($cle) {
                '1/2' => 'plus de la moitié', '1/3' => 'plus du tiers', '2/3' => 'plus des deux tiers',
                '1/4' => 'plus du quart', '3/4' => 'plus des trois quarts', default => "plus de {$cle}",
            };
        }
        return match ($cle) {
            '1/2' => 'au moins la moitié', '1/3' => 'au moins le tiers', '2/3' => 'au moins les deux tiers',
            '1/4' => 'au moins le quart', '3/4' => 'au moins les trois quarts', default => "au moins {$cle}",
        };
    }

    private function date(string $iso): string
    {
        return (new \DateTimeImmutable($iso))->format('d/m/Y');
    }
}
