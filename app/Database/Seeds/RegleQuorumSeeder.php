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
        $sa = $this->db->table('type_societe')->where('code', 'SA')->get()->getRow()->id;

        $rows = [
            // nature, n°, base quorum, q_num, q_den, base majorité, m_num, m_den, stricte, prépondérante, source, note
            ['AGC', 1, 'actions_droit_vote', 1, 2, null, null, null, null, 0, 'Loi 2003-036, art. 428', "Majorité de l'AGC à chercher"],
            ['AGC', 2, 'actions_droit_vote', 1, 4, null, null, null, null, 0, 'Loi 2003-036, art. 428', "Majorité de l'AGC à chercher"],
            ['AGO', 1, 'actions_droit_vote', 1, 4, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036, art. 570 et 571', 'Bulletins blancs non comptés'],
            ['AGO', 2, 'actions_droit_vote', 0, 1, 'voix_exprimees', 1, 2, 1, 0, 'Loi 2003-036, art. 570 et 571', 'Aucun quorum sur deuxième convocation'],
            ['AGE', 1, 'actions_droit_vote', 1, 2, 'voix_exprimees', 2, 3, 0, 0, 'Loi 2003-036, art. 574 et 575', 'Majorité des deux tiers des voix exprimées'],
            ['AGE', 2, 'actions_droit_vote', 1, 4, 'voix_exprimees', 2, 3, 0, 0, 'Loi 2003-036, art. 574 et 575', 'Majorité des deux tiers des voix exprimées'],
            ['CA',  1, 'membres_conseil', 1, 2, 'presents_ou_representes', 1, 2, 1, 1, 'Loi 2003-036, art. 483', 'Tous les membres doivent avoir été convoqués ; les statuts peuvent prévoir plus'],
            // À CHERCHER : SARL, SNC, SCS, SCA, SC (quorum et majorités).
        ];

        $data = [];
        foreach ($rows as [$nature, $num, $qb, $qn, $qd, $mb, $mn, $md, $ms, $vp, $source, $note]) {
            $data[] = [
                'type_societe_id'              => $sa,
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
                'verifie'                      => 1,
                'note'                         => $note,
            ];
        }
        $this->db->table('regle_quorum')->insertBatch($data);
    }
}
