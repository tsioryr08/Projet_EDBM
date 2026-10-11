<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Quorum et majorité (SA seulement : le reste est à chercher).
 * Fractions : quorum_num/quorum_den ; majorité : majorite_num/majorite_den.
 */
class RegleQuorumSeeder extends Seeder
{
    public function run()
    {
        $types = array_column($this->db->table('type_societe')->get()->getResultArray(), 'id', 'code');

        $rows = [
            // type, nature, n°, base quorum, q_num, q_den, base majorité, m_num, m_den, stricte, prépondérante, source, note
            ['SA', 'AGC', 1, 'actions_droit_vote', 1, 2, null, null, null, null, 0, 'Loi 2003-036, art. 428', "Majorité de l'AGC à chercher"],
            ['SA', 'AGC', 2, 'actions_droit_vote', 1, 4, null, null, null, null, 0, 'Loi 2003-036, art. 428', "Majorité de l'AGC à chercher"],
            ['SA', 'AGO', 1, 'actions_droit_vote', 1, 4, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036, art. 570 et 571', 'Bulletins blancs non comptés'],
            ['SA', 'AGO', 2, 'actions_droit_vote', 0, 1, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036, art. 570 et 571', 'Aucun quorum sur deuxième convocation'],
            ['SA', 'AGE', 1, 'actions_droit_vote', 1, 2, 'voix_exprimees', 2, 3, 0, 0, 'Loi 2003-036, art. 574 et 575', 'Majorité des deux tiers des voix exprimées'],
            ['SA', 'AGE', 2, 'actions_droit_vote', 1, 4, 'voix_exprimees', 2, 3, 0, 0, 'Loi 2003-036, art. 574 et 575', 'Majorité des deux tiers des voix exprimées'],
            ['SA', 'CA',  1, 'membres_conseil', 1, 2, 'presents_ou_representes', 1, 2, 1, 1, 'Loi 2003-036, art. 483', 'Tous les membres doivent avoir été convoqués ; les statuts peuvent prévoir plus'],

            // T10 – règles à chercher / à confirmer pour les autres formes sociales
            ['SARL', 'AGO', 1, 'parts_ou_associes', 1, 2, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036 / texte à confirmer', 'Quorum et majorité de la SARL à vérifier'],
            ['SARL', 'AGE', 1, 'parts_ou_associes', 1, 2, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036 / texte à confirmer', 'Quorum et majorité de la SARL à vérifier'],
            ['SNC', 'AGO', 1, 'parts_ou_associes', 1, 2, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036 / texte à confirmer', 'Quorum et majorité de la SNC à vérifier'],
            ['SNC', 'AGE', 1, 'parts_ou_associes', 1, 2, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036 / texte à confirmer', 'Quorum et majorité de la SNC à vérifier'],
            ['SCS', 'AGO', 1, 'parts_ou_associes', 1, 2, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036 / texte à confirmer', 'Quorum et majorité de la SCS à vérifier'],
            ['SCS', 'AGE', 1, 'parts_ou_associes', 1, 2, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036 / texte à confirmer', 'Quorum et majorité de la SCS à vérifier'],
            ['SCA', 'AGO', 1, 'actions_droit_vote', 1, 2, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036 / texte à confirmer', 'Quorum et majorité de la SCA à vérifier'],
            ['SCA', 'AGE', 1, 'actions_droit_vote', 1, 2, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036 / texte à confirmer', 'Quorum et majorité de la SCA à vérifier'],
            ['SC', 'AGO', 1, 'parts_ou_associes', 1, 2, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2001-026 / texte à confirmer', 'Quorum et majorité de la SC à vérifier'],
            ['SC', 'AGE', 1, 'parts_ou_associes', 1, 2, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2001-026 / texte à confirmer', 'Quorum et majorité de la SC à vérifier'],
            ['SARLU', 'AGO', 1, 'parts_ou_associes', 1, 2, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036 / SARLU à chercher', 'Quorum et majorité de la SARLU à vérifier'],
            ['SARLU', 'AGE', 1, 'parts_ou_associes', 1, 2, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036 / SARLU à chercher', 'Quorum et majorité de la SARLU à vérifier'],
        ];

        $existants = $this->db->table('regle_quorum')->get()->getResultArray();
        $clefs = [];
        foreach ($existants as $ligne) {
            $clefs[$ligne['type_societe_id'] . ':' . $ligne['nature_code'] . ':' . $ligne['numero_convocation']] = true;
        }

        $data = [];
        foreach ($rows as [$codeType, $nature, $num, $qb, $qn, $qd, $mb, $mn, $md, $ms, $vp, $source, $note]) {
            $clef = $types[$codeType] . ':' . $nature . ':' . $num;
            if (isset($clefs[$clef])) {
                continue;
            }

            $data[] = [
                'type_societe_id'              => $types[$codeType],
                'nature_code'                  => $nature,
                'numero_convocation'           => $num,
                'quorum_base'                  => $qb,
                'quorum_num'                   => $qn,
                'quorum_den'                   => $qd,
                'majorite_base'                => $mb,
                'majorite_num'                 => $mn,
                'majorite_den'                 => $md,
                'majorite_stricte'             => $ms,
                'voix_preponderante_president' => $vp,
                'source'                       => $source,
                'verifie'                      => 0,
                'note'                         => $note,
            ];
        }

        if ($data !== []) {
            $this->db->table('regle_quorum')->insertBatch($data);
        }
    }
}
