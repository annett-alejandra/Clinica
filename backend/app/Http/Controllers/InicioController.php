<?php

namespace App\Http\Controllers;

use App\Services\PortalService;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __construct(protected PortalService $portal) {}

    /** Portada institucional con instalaciones, especialidades reales y acceso al portal. */
    public function __invoke(): View
    {
        return view('welcome', [
            'areas' => config('clinica.areas'),
            ...$this->portal->datosPortada(),
        ]);
    }
}

