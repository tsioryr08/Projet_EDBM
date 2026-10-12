<?php

namespace App\Libraries;

/**
 * Construit l'explication juridique d'une décision calculée par le logiciel.
 * Chaque méthode renvoie :
 *   motif          : phrase qui explique le résultat
 *   source         : article / texte (colonne "source" de la règle)
 *   statut         : verifie | a_confirmer | deduction
 *   avertissement  : point d'attention (choix du projet, point à confirmer) ou null
 *   precision      : simple précision tirée de la règle (colonne "note"), sans alerte, ou null
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
            'precision'      => $regle['note'] ?? null,
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
                   . ($atteint
                       ? 'Le quorum est atteint : l\'assemblée peut délibérer.'
                       : "Le quorum n'est pas atteint : l'assemblée ne peut pas délibérer. Un procès-verbal de constat de non-quorum doit être dressé et la séance est réputée annulée pour défaut de quorum.");
        }

        return [
            'motif'          => $motif,
            'source'         => $regle['source'] ?? null,
            'statut'         => $statut,
            'avertissement'  => $this->avertissementsQuorum($regle, $totaux),
            'precision'      => $regle['note'] ?? null,
            'texte_officiel' => $regle['texte_officiel'] ?? null,
        ];
    }

    /** Base légale à afficher lorsqu'une séance ne peut pas délibérer faute de quorum. */
    public function baseLegaleNonQuorum(string $typeSociete, string $nature, int $numeroConvocation, ?string $source = null): string
    {
        if ($typeSociete !== 'SA') {
            return $source
                ? "Règle enregistrée dans le projet : {$source}. Son application à cette forme sociale doit être confirmée avec les statuts et les textes applicables."
                : 'La règle de quorum de cette forme sociale doit être vérifiée dans les statuts et les textes applicables.';
        }

        return match ($nature) {
            'CA' => 'Loi n° 2003-036, articles 482 et 483 : les règles de convocation et de délibération du conseil sont fixées par les statuts ; le conseil ne délibère valablement que si tous ses membres ont été régulièrement convoqués et si au moins la moitié de ses membres sont présents, sauf quorum statutaire supérieur. Une décision prise en violation de cette règle est nulle. Point à vérifier : le calcul actuel du projet compte aussi les administrateurs représentés, alors que l’article 483 emploie le terme « présents ».',
            'AGO' => $numeroConvocation === 1
                ? 'Loi n° 2003-036, articles 536 et 570 : sur première convocation, l’AGO d’une SA exige au moins le quart des actions ayant droit de vote. Si ce quorum manque, l’avis de convocation suivant doit respecter le délai prévu à l’article 536 ; sur deuxième convocation, aucun quorum n’est requis selon l’article 570.'
                : 'Loi n° 2003-036, articles 536 et 570 : l’avis de convocation suivant est porté à la connaissance des actionnaires au moins six jours avant l’assemblée ; sur deuxième convocation d’une AGO, aucun quorum n’est requis.',
            'AGE' => 'Loi n° 2003-036, articles 536 et 574 : l’AGE d’une SA exige au moins la moitié des actions sur première convocation et le quart sur deuxième convocation. Si le quorum manque encore sur deuxième convocation, une troisième peut être tenue dans les deux mois suivant la date fixée pour la deuxième, avec le quorum du quart ; les convocations suivantes respectent le délai de l’article 536.',
            'AGC' => 'Loi n° 2003-036, article 428 : l’assemblée constitutive exige la moitié des actions sur première convocation et le quart sur deuxième. Si le quorum manque encore, elle doit se tenir dans les deux mois de la date fixée pour la deuxième convocation ; la loi prévoit alors une troisième convocation avec le quorum du quart.',
            default => $source ? "Règle enregistrée dans le projet : {$source}." : 'La règle applicable doit être vérifiée.',
        };
    }

    private function avertissementsQuorum(array $regle, int $totaux): ?string
    {
        $av = [];
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
        $motif = $this->ajouterMotifAdoption($motif, $regle, $egalite, $adoptee, $sensPresident, $statut);
        $statut = $this->corrigerStatutAdoption($regle, $egalite, $statut, $sensPresident);

        if ($regle['majorite_base'] === 'voix_exprimees' && ($voix['abstention'] ?? 0) > 0) {
            $avert = "Les abstentions ne sont pas comptées dans les voix exprimées (point à confirmer).";
        }

        return [
            'motif'          => $motif,
            'source'         => $regle['source'] ?? null,
            'statut'         => $statut,
            'avertissement'  => $avert,
            'precision'      => null,
            'texte_officiel' => $regle['texte_officiel'] ?? null,
        ];
    }

    private function ajouterMotifAdoption(string $motif, array $regle, bool $egalite, ?bool $adoptee, ?string $sensPresident, string $statut): string
    {
        if ($egalite && (int) $regle['voix_preponderante_president'] === 1) {
            $motif .= "Partage des voix : la voix du président de séance est prépondérante, sauf disposition contraire des statuts. ";
            if ($sensPresident === null) {
                return $motif . 'Décision en attente : indiquez le sens du vote du président de séance.';
            }
            if ($sensPresident === 'abstention') {
                return $motif . "Le président de séance s'est abstenu ou était absent : sa voix prépondérante n'est pas exercée et la résolution n'est pas adoptée (déduction : le texte ne le dit pas expressément).";
            }
            return $motif . "Le président de séance a voté « {$sensPresident} » : sa voix l'emporte, la résolution est " . ($adoptee ? 'adoptée.' : 'non adoptée.');
        }

        $motif .= 'Résolution ' . ($adoptee ? 'adoptée.' : 'non adoptée.');
        if ($egalite) {
            return $motif . " L'égalité des voix ne donne pas la majorité exigée : c'est une conséquence de la règle, le texte ne prévoit pas de départage dans ce cas.";
        }

        return $motif;
    }

    private function corrigerStatutAdoption(array $regle, bool $egalite, string $statut, ?string $sensPresident): string
    {
        if ($egalite && (int) $regle['voix_preponderante_president'] === 1 && $sensPresident === 'abstention') {
            return 'deduction';
        }

        if ($egalite && ! (int) $regle['voix_preponderante_president']) {
            return 'deduction';
        }

        return $statut;
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
