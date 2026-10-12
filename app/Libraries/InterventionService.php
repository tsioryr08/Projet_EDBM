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

    public function ajouter(int $assembleeId, int $participantId, string $texte): int
    {
        $texte = trim($texte);
        if ($texte === '') {
            throw new RuntimeException('Le contenu de l’intervention est obligatoire.');
        }

        if (! $this->assemblees->find($assembleeId)) {
            throw new RuntimeException('Assemblée introuvable.');
        }

        $participant = $this->participants->find($participantId);
        if (! $participant || (int) $participant['assemblee_id'] !== $assembleeId || $participant['statut'] !== 'present') {
            throw new RuntimeException('Choisissez une personne présente à cette assemblée.');
        }

        return (int) $this->interventions->insert([
            'assemblee_id' => $assembleeId,
            'participant_id' => $participantId,
            'texte' => $texte,
        ]);
    }

    public function pourAssemblee(int $assembleeId): array
    {
        return db_connect()->table('intervention i')
            ->select('i.id, i.assemblee_id, i.participant_id, i.texte, i.cree_le, p.nom as auteur')
            ->join('participant p', 'p.id = i.participant_id')
            ->where('i.assemblee_id', $assembleeId)
            ->orderBy('i.id', 'ASC')
            ->get()->getResultArray();
    }
}
