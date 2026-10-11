<?php

namespace App\Controllers;

use App\Models\SocieteModel;
use App\Models\TypeSocieteModel;

class Home extends BaseController
{
    public function index(): string
    {
        $societes = (new SocieteModel())->orderBy('id', 'DESC')->findAll();
        $types = (new TypeSocieteModel())->findAll();

        return view('home/index', compact('societes', 'types'));
    }
}
