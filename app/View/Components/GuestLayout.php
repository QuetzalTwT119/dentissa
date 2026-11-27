<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * @param  string  $titulo  Texto de la pestaña del navegador (26/11/2026: antes
     *                          todas las pantallas de acceso decían "Acceso").
     */
    public function __construct(public string $titulo = 'Acceso') {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
