<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\Recommendation;
use App\Models\User;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Dashboard.
     */
    public function index()
    {
        $monthDiagnoses = Diagnosis::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month);
        $classified = Diagnosis::whereNotNull('inference_source')->count();

        return view('home', [
            'stats' => [
                'patients' => Patient::count(),
                'patients_month' => Patient::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
                'diagnoses_month' => (clone $monthDiagnoses)->count(),
                'ml_share' => $classified ? round(Diagnosis::where('inference_source', 'ml')->count() * 100 / $classified) : null,
                'doctors' => User::whereIn('rol_id', [Role::Doctor->value, Role::ChiefDoctor->value])->count(),
            ],
            'recomm' => Recommendation::latest()->paginate(5, ['*'], 'recomendaciones'),
            'users' => User::whereIn('rol_id', [Role::Doctor->value, Role::ChiefDoctor->value])
                ->orderBy('name')->paginate(5, ['*'], 'equipo'),
        ]);
    }
}
