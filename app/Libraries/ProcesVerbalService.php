<?php

namespace App\Libraries;

use RuntimeException;

class ProcesVerbalService
{
    public function preparer(int $assembleeId): array
    {
        $data = (new AssembleeService())->versJson($assembleeId);
        $nature = (string) $data['nature'];
        $vue = $nature === 'CA' ? 'pdf/pv/ca' : ($nature === 'AGC' ? 'pdf/pv/constitutif' : 'pdf/pv/assemblee');
        $pdfNature = $nature === 'CA' ? 'pv_conseil_administration'
            : ($nature === 'AGC' ? 'pv_constitutif' : 'pv_ordinaire');

        return [
            'vue' => $vue,
            'data' => $data + ['titre' => $this->titre($nature)],
            'societe_id' => $this->societeId($data),
            'reunion_id' => $this->reunionId($assembleeId),
            'nature' => $pdfNature,
            'nom_fichier' => 'pv-' . strtolower($nature) . '-' . $assembleeId . '.pdf',
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

    private function societeId(array $data): int
    {
        $row = db_connect()->table('societe')->where('denomination', $data['societe'])->get()->getRowArray();
        if (! $row) {
            throw new RuntimeException("Société « {$data['societe']} » introuvable.");
        }
        return (int) $row['id'];
    }

    private function reunionId(int $assembleeId): int
    {
        $row = db_connect()->table('assemblee')->where('id', $assembleeId)->get()->getRowArray();
        if (! $row) {
            throw new RuntimeException("Assemblée #{$assembleeId} introuvable.");
        }
        return (int) $row['reunion_id'];
    }
}
