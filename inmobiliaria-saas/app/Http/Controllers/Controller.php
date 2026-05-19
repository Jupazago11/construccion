<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Controlador base que habilita autorizaciones por policies en todos los módulos HTTP.
    use AuthorizesRequests;
}
