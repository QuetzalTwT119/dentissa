<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function paginationPageSize(Request $request): int
    {
        return in_array((string) $request->query('per_page', '20'), ['20', '50'], true)
            ? (int) $request->query('per_page', 20)
            : 20;
    }
}
