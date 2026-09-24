<?php

namespace App\Http\Controllers;

use App\Models\Rule;

/**
 * Nutritional categories. They are fixed (seeded) because the inference
 * engine predicts their ids; only their recommendations are editable.
 */
class RulesController extends Controller
{
    public function index()
    {
        return view('rules.index', [
            'rules' => Rule::paginate(15),
            'options' => Rule::pluck('name', 'id')->prepend('Seleccione...', ''),
        ]);
    }
}
