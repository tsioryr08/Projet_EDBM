<?php

namespace App\Libraries;

use App\Models\AssembleeModel;
use App\Models\ReunionModel;
use App\Models\SocieteModel;

/**
 * T8 - Écrans de la phase B.
 *
 * Regroupe les écrans métier de la phase B :
 * - planification de réunion,
 * - tenue de l'assemblée / journée de vote,
 * - registre des assemblées.
 */
class PhaseBService
{
    public function __construct(
        private ?ReunionService $reunionService = null,
        private ?AssembleeService $assembleeService = null,
        private ?SocieteModel $societes = null,
        private ?ReunionModel $reunions = null,
        private ?AssembleeModel $assemblees = null,
    ) {
        $this->reunionService ??= new ReunionService();
        $this->assembleeService ??= new AssembleeService();
        $this->societes ??= new SocieteModel();
        $this->reunions ??= new ReunionModel();
        $this->assemblees ??= new AssembleeModel();
    }

    /**
     * Écran 1 : planification de réunion.
     */
    public function ecranPlanification(
        int $societeId,
        string $nature,
        string $dateReunion,
        string $heure,
        string $lieu,
        array $ordreDuJour = [],
        int $numeroConvocation = 1,
        ?string $modeCalcul = null,
        ?int $delaiStatuts = null,
        bool $enregistrer = true,
        ?int $reunionPrecedenteId = null,
    ): array {
        $reunion = $this->reunionService->planifier(
            $societeId,
            $nature,
            $dateReunion,
            $heure,
            $lieu,
            $ordreDuJour,
            $numeroConvocation,
            $modeCalcul,
            $delaiStatuts,
            $enregistrer,
            $reunionPrecedenteId,
        );

        return [
            'type_ecran' => 'planification_reunion',
            'reunion' => $reunion,
            'resume' => [
                'societe' => $reunion['societe'],
                'nature' => $reunion['nature'],
                'date_reunion' => $reunion['date_reunion'],
                'date_convocation' => $reunion['date_convocation'],
                'numero_convocation' => $reunion['numero_convocation'],
                'delai_jours' => $reunion['delai_jours'],
                'en_retard' => $reunion['en_retard'],
            ],
        ];
    }

    /**
     * Écran 2 : tenue de la réunion / jour J.
     */
    public function ecranJourJ(int $reunionId, array $participants, ?string $president = null, ?string $secretaire = null, ?string $dateTenue = null): array
    {
        $assemblee = $this->assembleeService->tenir($reunionId, $participants, $president, $secretaire, $dateTenue);

        return [
            'type_ecran' => 'jour_j',
            'assemblee' => $assemblee,
            'resume' => [
                'assemblee_id' => $assemblee['assemblee_id'],
                'quorum_atteint' => $assemblee['quorum_atteint'],
                'voix_presentes_ou_representees' => $assemblee['voix_presentes_ou_representees'],
                'voix_totales' => $assemblee['voix_totales'],
                'justification' => $assemblee['justification'],
            ],
        ];
    }

    /**
     * Écran 3 : vote des résolutions dans l'assemblée.
     */
    public function ecranVotes(int $assembleeId, array $resolutions): array
    {
        $resultat = $this->assembleeService->voter($assembleeId, $resolutions);

        return [
            'type_ecran' => 'votes',
            'resultat' => $resultat,
            'resume' => [
                'assemblee_id' => $assembleeId,
                'resolutions' => count($resultat['resolutions'] ?? []),
                'adoptees' => count(array_filter($resultat['resolutions'] ?? [], static fn (array $r): bool => (bool) ($r['adoptee'] ?? false))),
            ],
        ];
    }

    /**
     * Registre des assemblées : liste des réunions et de leurs résultats.
     */
    public function registreAssemblies(int $societeId): array
    {
        $reunions = $this->reunions->where('societe_id', $societeId)->orderBy('date_reunion', 'ASC')->findAll();
        $registre = [];

        foreach ($reunions as $reunion) {
            $assemblee = $this->assemblees->where('reunion_id', $reunion['id'])->first();
            $registre[] = [
                'reunion_id' => (int) $reunion['id'],
                'assemblee_id' => $assemblee ? (int) $assemblee['id'] : null,
                'nature' => $reunion['nature_code'],
                'numero_convocation' => (int) $reunion['numero_convocation'],
                'date_reunion' => $reunion['date_reunion'],
                'date_convocation' => $reunion['date_convocation'],
                'statut' => $reunion['statut'],
                'quorum_atteint' => $assemblee['quorum_atteint'] ?? null,
                'president' => $assemblee['president_seance'] ?? null,
                'secretaire' => $assemblee['secretaire'] ?? null,
            ];
        }

        return [
            'type_ecran' => 'registre_assemblees',
            'societe_id' => $societeId,
            'registre' => $registre,
        ];
    }
}
