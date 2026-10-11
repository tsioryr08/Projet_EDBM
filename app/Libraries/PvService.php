<?php

namespace App\Libraries;

/**
 * Génère un texte de procès-verbal à partir du JSON produit par AssembleeService.
 */
final class PvService
{
    public function generer(array $donnees): string
    {
        $nature = strtoupper((string) ($donnees['nature'] ?? 'ASSEMBLEE'));
        $type = match ($nature) {
            'CA' => 'Conseil d’administration',
            'AGO' => 'Assemblée générale ordinaire',
            'AGE' => 'Assemblée générale extraordinaire',
            'AGC' => 'Assemblée générale constitutive',
            default => 'Assemblée',
        };

        $lignes = [];
        $lignes[] = 'PROCÈS-VERBAL';
        $lignes[] = '===============================';
        $lignes[] = 'Société : ' . ($donnees['societe'] ?? '—');
        $lignes[] = 'Type : ' . $type;
        $lignes[] = 'Date de réunion : ' . ($donnees['date_reunion'] ?? '—');
        $lignes[] = 'Lieu : ' . ($donnees['lieu'] ?? '—');
        $lignes[] = 'Président de séance : ' . ($donnees['president_seance'] ?? '—');
        $lignes[] = 'Secrétaire : ' . ($donnees['secretaire'] ?? '—');
        $lignes[] = 'Quorum requis : ' . ($donnees['quorum_requis'] ?? '—');
        $lignes[] = 'Quorum atteint : ' . (($donnees['quorum_atteint'] ?? false) ? 'Oui' : 'Non');
        $lignes[] = '';

        if (! ($donnees['quorum_atteint'] ?? false)) {
            $lignes[] = 'Il est constaté que le quorum n’est pas atteint.';
            $lignes[] = 'La séance est annulée pour défaut de quorum.';
            $lignes[] = 'Aucune délibération ne peut être prise et aucun vote ne peut être validé.';
            if (! empty($donnees['pv_constat'])) {
                $lignes[] = $donnees['pv_constat']['message'];
            }
            return implode(PHP_EOL, $lignes);
        }

        $lignes[] = 'Il est constaté que le quorum est atteint.';
        $lignes[] = 'La séance est régulièrement tenue.';
        $lignes[] = '';
        $lignes[] = 'Participants :';

        foreach ($donnees['participants'] ?? [] as $participant) {
            $ligne = '- ' . ($participant['nom'] ?? '—') . ' : ' . ($participant['statut'] ?? '—');
            if (($participant['statut'] ?? null) === 'represente' && ! empty($participant['par'])) {
                $ligne .= ' (représenté par ' . $participant['par'] . ')';
            }
            $ligne .= ' — voix : ' . ($participant['voix'] ?? 0);
            $lignes[] = $ligne;
        }

        $lignes[] = '';
        $lignes[] = 'Résolutions :';

        foreach ($donnees['resolutions'] ?? [] as $index => $resolution) {
            $numero = $index + 1;
            $etat = match (true) {
                ($resolution['adoptee'] ?? null) === true => 'ADOPTÉE',
                ($resolution['adoptee'] ?? null) === false => 'REJETÉE',
                default => 'EN ATTENTE',
            };

            $lignes[] = '- Résolution n°' . $numero . ' : ' . ($resolution['texte'] ?? '—') . ' — ' . $etat;
            $lignes[] = '  Vote : ' . ($resolution['pour'] ?? 0) . ' pour, ' . ($resolution['contre'] ?? 0) . ' contre';

            if (($resolution['sens_vote_president'] ?? null) !== null) {
                $lignes[] = '  Vote du président de séance : ' . $resolution['sens_vote_president'];
            }

            if (! empty($resolution['mention_pv'])) {
                $lignes[] = '  Mention PV : ' . $resolution['mention_pv'];
            }

            if (! empty($resolution['motif'])) {
                $lignes[] = '  Motif : ' . $resolution['motif'];
            }
        }

        if (empty($donnees['resolutions'] ?? [])) {
            $lignes[] = 'Aucune résolution n’a été soumise à la délibération.';
        }

        $lignes[] = '';
        $lignes[] = 'JUSTIFICATION JURIDIQUE';
        $lignes[] = '------------------------';
        $lignes[] = 'Quorum : ' . ($donnees['motif_quorum'] ?? 'Non renseigné');
        $source = $donnees['source_regle'] ?? 'Non renseignée';
        $statut = ! empty($donnees['regle_verifiee']) ? 'vérifié' : 'à confirmer';
        $lignes[] = 'Source : ' . $source . ' (' . $statut . ')';

        foreach ($donnees['resolutions'] ?? [] as $index => $resolution) {
            $numero = $index + 1;
            $sourceRes = $resolution['source'] ?? 'Non renseignée';
            $statutRes = $resolution['statut_regle'] ?? 'a_confirmer';
            $lignes[] = 'Résolution ' . $numero . ' : ' . ($resolution['motif'] ?? 'Sans motif') . ' | Source : ' . $sourceRes . ' | Statut : ' . $statutRes;
        }

        return implode(PHP_EOL, $lignes);
    }

    public function documentFinal(array $donnees): string
    {
        return $this->generer($donnees);
    }

    public function exportHtml(array $donnees): string
    {
        $texte = $this->generer($donnees);
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Procès-verbal</title>'
            . '<style>body{font-family:Arial,sans-serif;margin:32px;color:#111;}h1{font-size:26px;}h2{font-size:18px;}p,li{line-height:1.6;}pre{white-space:pre-wrap;}</style>'
            . '</head><body><h1>Procès-verbal</h1><pre>' . htmlspecialchars($texte, ENT_QUOTES, 'UTF-8') . '</pre></body></html>';

        return $html;
    }
}
