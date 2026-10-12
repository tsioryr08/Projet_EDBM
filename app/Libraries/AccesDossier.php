<?php

namespace App\Libraries;

use CodeIgniter\HTTP\RedirectResponse;

/**
 * Contrôle d'accès partagé : un dossier n'est ouvert que si le mot de passe
 * de la société a été validé dans la session (clé societe_access_{id}).
 */
class AccesDossier
{
    public static function ouvert(int $societeId): bool
    {
        return (bool) session()->get('societe_access_' . $societeId);
    }

    public static function redirection(int $societeId): RedirectResponse
    {
        return redirect()->to(site_url('dossiers/' . $societeId . '/acces'));
    }

    /** Société d'une réunion, ou null si la réunion n'existe pas. */
    public static function societeDeReunion(int $reunionId): ?int
    {
        $row = db_connect()->table('reunion')->select('societe_id')->where('id', $reunionId)->get()->getRowArray();

        return $row ? (int) $row['societe_id'] : null;
    }

    /** Société d'une assemblée, ou null si l'assemblée n'existe pas. */
    public static function societeDeAssemblee(int $assembleeId): ?int
    {
        $row = db_connect()->table('assemblee a')
            ->select('r.societe_id')
            ->join('reunion r', 'r.id = a.reunion_id')
            ->where('a.id', $assembleeId)
            ->get()->getRowArray();

        return $row ? (int) $row['societe_id'] : null;
    }
}