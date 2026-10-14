<?php

namespace App\Libraries;

use RuntimeException;

class ProcesVerbalService
{
    public function preparer(int $assembleeId): array
    {
        $contexte = $this->contexte($assembleeId);

        $data = (new AssembleeService())->versJson($assembleeId);
        $data['interventions'] = (new InterventionService())->pourAssemblee($assembleeId);
        $nature = (string) $data['nature'];
        $vue = $nature === 'CA' ? 'pdf/pv/ca' : ($nature === 'AGC' ? 'pdf/pv/constitutif' : 'pdf/pv/assemblee');
        $pdfNature = $nature === 'CA' ? 'pv_conseil_administration'
            : ($nature === 'AGC' ? 'pv_constitutif' : 'pv_ordinaire');

        $dateIso = substr((string) (
            ($data['date_tenue'] ?? null)
            ?: ($contexte['date_tenue'] ?? null)
            ?: ($data['date_reunion'] ?? null)
            ?: $contexte['date_reunion']
        ), 0, 10);

        // Référence du PV : nature, date de tenue, numéro de l'assemblée.
        $reference = 'PV-' . strtoupper($nature) . '-' . $dateIso . '-A' . $assembleeId;

        $ordreDuJour = json_decode((string) ($contexte['ordre_du_jour'] ?? '[]'), true);

        $data += [
            'titre'              => $this->titre($nature),
            'siege'              => $contexte['siege'] ?? '',
            'lieu'               => $contexte['lieu'] ?? '',
            'type_code'          => $contexte['type_code'] ?? '',
            'numero_convocation' => (int) $contexte['numero_convocation'],
            'heure_fr'           => str_replace(':', 'h', substr((string) $contexte['heure'], 0, 5)),
            'ordre_du_jour'      => is_array($ordreDuJour) ? array_values($ordreDuJour) : [],
            'date_fr'            => $this->dateFr($dateIso),
        ];
        $data['reference'] = $reference;
        $data['is_pv'] = true;

        return [
            'vue'         => $vue,
            'data'        => $data,
            'societe_id'  => (int) $contexte['societe_id'],
            'reunion_id'  => (int) $contexte['reunion_id'],
            'nature'      => $pdfNature,
            'nom_fichier' => strtolower($reference) . '.pdf',
        ];
    }

    private function titre(string $nature): string
    {
        return match ($nature) {
            'CA' => 'Procès-verbal du conseil d’administration',
            'AGC' => 'Procès-verbal de l’assemblée générale constitutive',
            'AGE' => 'Procès-verbal de l’assemblée générale extraordinaire',
            default => 'Procès-verbal de l’assemblée générale',
        };
    }

    /** Assemblée, réunion, société et forme sociale en une seule requête. */
    private function contexte(int $assembleeId): array
    {
        $row = db_connect()->table('assemblee a')
            ->select('a.id, a.reunion_id, a.date_tenue, r.societe_id, r.date_reunion, r.heure, r.lieu, r.ordre_du_jour, r.numero_convocation, s.siege, t.code AS type_code')
            ->join('reunion r', 'r.id = a.reunion_id')
            ->join('societe s', 's.id = r.societe_id')
            ->join('type_societe t', 't.id = s.type_societe_id', 'left')
            ->where('a.id', $assembleeId)
            ->get()->getRowArray();

        if (! $row) {
            throw new RuntimeException("Assemblée #{$assembleeId} introuvable.");
        }

        return $row;
    }

    /** « 22 octobre 2026 » ; ligne vide si la date est invalide. */
    private function dateFr(string $date): string
    {
        $timestamp = $date !== '' ? strtotime($date) : false;
        if ($timestamp === false) {
            return '_________';
        }

        $mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $jour = (int) date('j', $timestamp);

        return ($jour === 1 ? '1er' : (string) $jour) . ' ' . $mois[(int) date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
    }
}