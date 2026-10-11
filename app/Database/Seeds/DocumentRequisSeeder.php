<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Documents requis (listes Orinasa : SA/SAU, SARL/SARLU, SCS, SNC, SC) + pivot par type de société.
 */
class DocumentRequisSeeder extends Seeder
{
    public function run()
    {
        // code, catégorie, libellé, condition, validité (mois), modèle Orinasa, note
        $docs = [
            // Établissement
            ['STATUTS_SARL', 'etablissement', 'Statuts de la société (modèle SARL ou SARLU)', null, null, 1, null],
            ['STATUTS_SA_AG', 'etablissement', 'Statuts SA avec administrateur général', 'sa_ag', null, 1, 'Obligatoire si 3 actionnaires ou moins'],
            ['STATUTS_SA_CA', 'etablissement', "Statuts SA avec conseil d'administration", 'sa_ca', null, 1, null],
            ['STATUTS_SCS_SNC', 'etablissement', 'Statuts de la société', null, null, 0, 'Pas de modèle listé par Orinasa'],
            ['STATUTS_SC', 'etablissement', 'Statuts de la société civile', null, null, 1, null],
            ['PV_AGC', 'etablissement', "Procès-verbal de l'assemblée générale constitutive", null, null, 1, null],
            ['PV_CA', 'etablissement', "Procès-verbal de conseil d'administration", 'sa_ca', null, 0, null],
            ['PV_NOMINATION_GERANTS', 'etablissement', 'Procès-verbal de nomination des gérants', 'gerant_non_statutaire', null, 1, null],
            // Constitution du capital
            ['BULLETIN_SOUSCRIPTION', 'capital', 'Bulletin de souscription de versement (un par actionnaire ou par commanditaire)', null, null, 1, null],
            ['DECL_SOUSCRIPTION', 'capital', 'Déclaration de souscription de versement', null, null, 1, null],
            ['ETAT_SOUSCRIPTION', 'capital', 'État de souscription de versement', null, null, 0, null],
            ['ATTESTATION_BLOCAGE', 'capital', 'Attestation de blocage du capital libéré', null, null, 0, 'SA'],
            // Associés
            ['ASSOC_ID', 'associe', 'Copie CIN, passeport ou carte de résident', null, null, 0, null],
            ['ASSOC_FISC', 'associe', 'Justificatif de régularité fiscale', null, null, 0, null],
            ['ASSOC_MORALE_ETR', 'associe', 'Statuts et Kbis (ou extrait RCS) en version française assermentée', 'associe_morale_etrangere', null, 0, null],
            ['ASSOC_MORALE_MG', 'associe', 'Extrait RCS, carte fiscale et PV de prise de participation enregistré', 'associe_morale_malagasy', null, 1, 'Modèle de PV de prise de participation disponible'],
            ['ASSOC_MINEUR', 'associe', "CIN du père ou ordonnance du tribunal désignant un tuteur légal", 'associe_mineur_non_emancipe', null, 0, 'Moins de 21 ans et non émancipé ; non listé pour la SNC (art. 5)'],
            // Dirigeants
            ['DIR_ID', 'dirigeant', 'Copie CIN, passeport ou carte de résident', null, null, 0, null],
            ['DIR_RESID', 'dirigeant', 'Certificat de résidence', null, 3, 0, 'Moins de 3 mois'],
            ['DIR_DECL', 'dirigeant', 'Déclaration relative aux dirigeants sociaux (filiation et non-condamnation)', null, null, 1, null],
            // Commissaire aux comptes (SA)
            ['CAC_LISTE', 'commissaire_aux_comptes', "Justification de l'inscription sur la liste des commissaires aux comptes", null, null, 0, null],
            ['CAC_PHYSIQUE', 'commissaire_aux_comptes', 'CIN, certificat de résidence et déclaration relative aux dirigeants sociaux', 'cac_physique', 3, 0, 'Certificat de résidence de moins de 3 mois'],
            ['CAC_MORALE', 'commissaire_aux_comptes', 'Extrait RCS et NIF', 'cac_morale', null, 0, null],
            // Siège social
            ['SIEGE_BAIL', 'siege', 'Contrat de bail', null, null, 1, 'Modèle disponible : lien possible avec le générateur de bail'],
            ['SIEGE_PLAN', 'siege', 'Plan de repérage visé par le fokontany', null, null, 0, null],
            ['SIEGE_TITRE', 'siege', "Copie du titre de propriété (ou l'un des deux documents suivants)", null, null, 0, null],
            ['SIEGE_CSJ', 'siege', 'Certificat de situation juridique', null, 3, 0, 'Moins de 3 mois'],
            ['SIEGE_ACTE_VENTE', 'siege', 'Acte de vente', null, 12, 0, "Moins d'un an"],
            ['SIEGE_SOUSLOC', 'siege', "Contrat de bail enregistré et autorisation du propriétaire", 'sous_location', null, 0, null],
            ['SIEGE_BAILLEUR', 'siege', 'CIN du bailleur, ou extrait RCS et NIF si personne morale', null, null, 0, null],
            // Activité et mandataire
            ['ACT_REGLEMENTEE', 'activite', "Documents complémentaires selon l'activité réglementée", 'activite_reglementee', null, 0, null],
            ['MANDAT_PROC', 'mandataire', 'Procuration signée par le dirigeant', 'mandataire', null, 0, null],
            ['MANDAT_CIN', 'mandataire', 'Copie CIN du mandataire', 'mandataire', null, 0, null],
        ];

        $existantsDoc = array_column($this->db->table('document_requis')->select('code')->get()->getResultArray(), 'code');
        $data = [];
        foreach ($docs as [$code, $cat, $libelle, $cond, $validite, $modele, $note]) {
            if (in_array($code, $existantsDoc, true)) {
                continue;
            }
            $data[] = [
                'code' => $code, 'categorie' => $cat, 'libelle' => $libelle, 'condition_code' => $cond,
                'validite_mois' => $validite, 'modele_disponible' => $modele, 'note' => $note,
            ];
        }
        if ($data !== []) {
            $this->db->table('document_requis')->insertBatch($data);
        }

        // ---- Pivot : quel document pour quel type ----
        $docId  = array_column($this->db->table('document_requis')->get()->getResultArray(), 'id', 'code');
        $typeId = array_column($this->db->table('type_societe')->get()->getResultArray(), 'id', 'code');
        $existantsPivot = [];
        foreach ($this->db->table('document_type_societe')->get()->getResultArray() as $ligne) {
            $existantsPivot[$ligne['document_id'] . ':' . $ligne['type_societe_id']] = true;
        }

        $communs = ['ASSOC_ID', 'ASSOC_FISC', 'ASSOC_MORALE_ETR', 'ASSOC_MORALE_MG',
                    'DIR_ID', 'DIR_RESID', 'DIR_DECL',
                    'SIEGE_BAIL', 'SIEGE_PLAN', 'SIEGE_TITRE', 'SIEGE_CSJ', 'SIEGE_ACTE_VENTE', 'SIEGE_SOUSLOC', 'SIEGE_BAILLEUR',
                    'ACT_REGLEMENTEE', 'MANDAT_PROC', 'MANDAT_CIN'];

        // code document => types concernés
        $map = [];
        foreach ($communs as $c) {
            $map[$c] = ['SA', 'SARL', 'SARLU', 'SCS', 'SNC', 'SC']; // SCA : à chercher
        }
        $map['ASSOC_MINEUR']          = ['SA', 'SARL', 'SARLU', 'SCS', 'SC'];
        $map['STATUTS_SARL']          = ['SARL', 'SARLU'];
        $map['PV_NOMINATION_GERANTS'] = ['SARL'];
        foreach (['STATUTS_SA_AG', 'STATUTS_SA_CA', 'PV_CA', 'ATTESTATION_BLOCAGE', 'CAC_LISTE', 'CAC_PHYSIQUE', 'CAC_MORALE'] as $c) {
            $map[$c] = ['SA'];
        }
        $map['STATUTS_SCS_SNC']       = ['SCS', 'SNC'];
        $map['STATUTS_SC']            = ['SC'];
        $map['PV_AGC']                = ['SA', 'SCS', 'SNC'];
        foreach (['BULLETIN_SOUSCRIPTION', 'DECL_SOUSCRIPTION', 'ETAT_SOUSCRIPTION'] as $c) {
            $map[$c] = ['SA', 'SCS'];
        }

        $pivot = [];
        foreach ($map as $code => $types) {
            foreach ($types as $tc) {
                $docKey = $docId[$code];
                $typeKey = $typeId[$tc];
                $clef = $docKey . ':' . $typeKey;
                if (isset($existantsPivot[$clef])) {
                    continue;
                }
                $pivot[] = ['document_id' => $docKey, 'type_societe_id' => $typeKey];
                $existantsPivot[$clef] = true;
            }
        }

        if ($pivot !== []) {
            $this->db->table('document_type_societe')->insertBatch($pivot);
        }
    }
}
