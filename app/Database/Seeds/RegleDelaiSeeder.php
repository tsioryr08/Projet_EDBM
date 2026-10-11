<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Délais de convocation. verifie = 1 : règle vérifiée dans un texte malgache.
 * Pour ajouter une règle trouvée plus tard (SNC, SCS, SCA, SC...) : ajouter une ligne ici
 * (ou un nouveau seeder), sans toucher au code métier.
 *
 * mode_calcul = 'franc' partout : choix du projet (jours francs). Les articles lus ne précisent pas le mode.
 */
class RegleDelaiSeeder extends Seeder
{
    public function run()
    {
        $t = array_column($this->db->table('type_societe')->get()->getResultArray(), 'id', 'code');

        $annonce = "Avis dans un journal d'annonces légales ; lettre recommandée si toutes les actions sont nominatives";
        $loi536  = 'Loi 2003-036, art. 536 (texte de la loi 2014-010)';

        $rows = [
            // type, nature, n°, délai, forme, source, verifie, note
            [$t['SA'], 'AGC', 1, 6, null, 'Loi 2003-036, art. 428', 1, 'Souscripteurs convoqués 6 jours au moins avant'],
            [$t['SA'], 'AGO', 1, 15, $annonce, $loi536, 1, "Les statuts fixent les règles sous réserve de l'art. 536 ; un mandataire de justice peut obtenir un autre délai"],
            [$t['SA'], 'AGO', 2, 6, 'Idem', 'Loi 2003-036, art. 536', 1, 'Convocations suivantes'],
            [$t['SA'], 'AGE', 1, 15, $annonce, $loi536, 1, null],
            [$t['SA'], 'AGE', 2, 6, 'Idem', 'Loi 2003-036, art. 536', 1, 'Convocations suivantes'],
            [$t['SA'], 'CA', 1, null, null, 'Loi 2003-036, art. 482', 1, 'Délai fixé par les statuts ; convocation par le président'],
            [$t['SARL'], 'AGO', 1, 15, 'Lettre recommandée', 'Décret 2004-453, art. 21', 0, 'À reconfirmer après les modifications du décret 2005-151'],
            [$t['SARL'], 'AGE', 1, 15, 'Lettre recommandée', 'Décret 2004-453, art. 21', 0, 'À reconfirmer après les modifications du décret 2005-151'],
            [$t['SNC'], 'AGO', 1, null, null, 'Loi 2003-036, à chercher', 0, 'Délai de convocation de la SNC à chercher'],
            [$t['SNC'], 'AGE', 1, null, null, 'Loi 2003-036, à chercher', 0, 'Délai de convocation de la SNC à chercher'],
            [$t['SCS'], 'AGO', 1, null, null, 'Loi 2003-036, à chercher', 0, 'Délai de convocation de la SCS à chercher'],
            [$t['SCS'], 'AGE', 1, null, null, 'Loi 2003-036, à chercher', 0, 'Délai de convocation de la SCS à chercher'],
            [$t['SCA'], 'AGO', 1, null, null, 'Loi 2003-036, à chercher', 0, 'Délai de convocation de la SCA à chercher'],
            [$t['SCA'], 'AGE', 1, null, null, 'Loi 2003-036, à chercher', 0, 'Délai de convocation de la SCA à chercher'],
            [$t['SC'], 'AGO', 1, null, null, 'Loi 2001-026, à chercher', 0, 'Délai de convocation de la SC à chercher'],
            [$t['SC'], 'AGE', 1, null, null, 'Loi 2001-026, à chercher', 0, 'Délai de convocation de la SC à chercher'],
            [$t['SARLU'], 'AGO', 1, null, null, 'Loi 2003-036, SARLU – à chercher', 0, 'Délai de convocation de la SARLU à chercher'],
            [$t['SARLU'], 'AGE', 1, null, null, 'Loi 2003-036, SARLU – à chercher', 0, 'Délai de convocation de la SARLU à chercher'],
        ];

        $existants = $this->db->table('regle_delai')->get()->getResultArray();
        $clefs = [];
        foreach ($existants as $ligne) {
            $clefs[$ligne['type_societe_id'] . ':' . $ligne['nature_code'] . ':' . $ligne['numero_convocation']] = true;
        }

        $data = [];
        foreach ($rows as [$type, $nature, $num, $delai, $forme, $source, $verifie, $note]) {
            $clef = $type . ':' . $nature . ':' . $num;
            if (isset($clefs[$clef])) {
                continue;
            }

            $data[] = [
                'type_societe_id'    => $type,
                'nature_code'        => $nature,
                'numero_convocation' => $num,
                'delai_jours'        => $delai,
                'mode_calcul'        => 'franc',
                'forme_envoi'        => $forme,
                'source'             => $source,
                'verifie'            => $verifie,
                'note'               => $note,
            ];
        }

        if ($data !== []) {
            $this->db->table('regle_delai')->insertBatch($data);
        }
    }
}
