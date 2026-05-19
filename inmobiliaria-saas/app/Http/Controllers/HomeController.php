<?php

namespace App\Http\Controllers;

use App\Enums\SystemRole;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    // Resuelve la etiqueta legible del rol autenticado para la pantalla principal de bienvenida.
    public function __invoke(): View
    {
        $user = auth()->user();

        $roleLabel = match (true) {
            $user->hasRole(SystemRole::CompanyAdmin->value) => 'Administrador',
            $user->hasRole(SystemRole::Operator->value)     => 'Operador',
            $user->hasRole(SystemRole::Viewer->value)       => 'Visualizador',
            $user->hasRole(SystemRole::BuyerUser->value)    => 'Comprador',
            default                                         => 'Usuario',
        };

        return view('home.index', [
            'roleLabel' => $roleLabel,
        ]);
    }
}
