<?php

namespace App\Libraries;

use App\Models\AssembleeModel;
use App\Models\InterventionModel;
use App\Models\ParticipantModel;
use RuntimeException;

class InterventionService
{
    public function __construct(
        private ?InterventionModel $interventions = null,
        private ?AssembleeModel $assemblees = null,
        private ?ParticipantModel $participants = null,
    ) {
        $this->interventions ??= new InterventionModel();
        $this->assemblees ??= new AssembleeModel();
        $this->participants ??= new ParticipantModel();
    }

    public function ajouter(int $assembleeId, string $auteurSelection, string $texte): int
    {
        $texte = trim($texte);
        if ($texte === '') {
            throw new RuntimeException('Le contenu de l’intervention est obligatoire.');
        }

        $assemblee = $this->assemblees->find($assembleeId);
        if (! $assemblee) {
            throw new RuntimeException('Assemblée introuvable.');
        }
        if ((int) $assemblee['quorum_atteint'] !== 1) {
            throw new RuntimeException('Le quorum n’est pas atteint : aucune intervention ne peut être enregistrée.');
        }

        $participantId = null;
        $auteurExterne = null;
        if (preg_match('/^participant:(\d+)$/', $auteurSelection, $match)) {
            $participantId = (int) $match[1];
            $participant = $this->participants->find($participantId);
            if (! $participant || (int) $participant['assemblee_id'] !== $assembleeId || $participant['statut'] !== 'present') {
                throw new RuntimeException('Choisissez une personne présente à cette assemblée.');
            }
        } elseif (preg_match('/^mandataire:(\d+)$/', $auteurSelection, $match)) {
            if (! db_connect()->fieldExists('mandataire_nom_externe', 'pouvoir') || ! db_connect()->fieldExists('auteur_externe', 'intervention')) {
                throw new RuntimeException('Appliquez les migrations en attente pour enregistrer une intervention faite par un mandataire externe.');
            }
            $pouvoir = db_connect()->table('pouvoir')
                ->where('assemblee_id', $assembleeId)
                ->where('mandant_id', (int) $match[1])
                ->where('mandataire_id', null)
                ->get()->getRowArray();
            if (! $pouvoir || empty($pouvoir['mandataire_nom_externe'])) {
                throw new RuntimeException('Ce mandataire externe n’est pas enregistré pour cette assemblée.');
            }
            $auteurExterne = $pouvoir['mandataire_nom_externe'];
        } else {
            throw new RuntimeException('Choisissez un intervenant enregistré pour cette assemblée.');
        }

        $ligne = [
            'assemblee_id' => $assembleeId,
            'participant_id' => $participantId,
            'texte' => $texte,
        ];
        if (db_connect()->fieldExists('auteur_externe', 'intervention')) {
            $ligne['auteur_externe'] = $auteurExterne;
        }

        return (int) $this->interventions->insert($ligne);
    }

    public function pourAssemblee(int $assembleeId): array
    {
        $builder = db_connect()->table('intervention i')
            ->select(db_connect()->fieldExists('auteur_externe', 'intervention')
                ? 'i.id, i.assemblee_id, i.participant_id, i.texte, i.cree_le, COALESCE(p.nom, i.auteur_externe) as auteur'
                : 'i.id, i.assemblee_id, i.participant_id, i.texte, i.cree_le, p.nom as auteur')
            ->join('participant p', 'p.id = i.participant_id', 'left')
            ->where('i.assemblee_id', $assembleeId)
            ->orderBy('i.id', 'ASC');
        return $builder->get()->getResultArray();
    }
}
