<?php

namespace App\Controllers;

use App\Models\SocieteModel;
use App\Models\TypeSocieteModel;

class Home extends BaseController
{
    public function index(): string
    {
        // Retour à l'accueil : on verrouille tous les dossiers ouverts.
        // Le mot de passe sera redemandé à la réouverture d'une société.
        foreach (array_keys(session()->get() ?? []) as $cle) {
            if (str_starts_with((string) $cle, 'societe_access_')) {
                session()->remove($cle);
            }
        }

        $societes = (new SocieteModel())->orderBy('id', 'DESC')->findAll();
        $types = (new TypeSocieteModel())->findAll();

        return view('home/index', compact('societes', 'types'));
    }
}