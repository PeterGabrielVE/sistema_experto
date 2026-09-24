<?php

namespace App\Http\Controllers;

use App\Http\Requests\PatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientController extends Controller
{
    public function __construct(private PatientService $patients)
    {
    }

    /**
     * Server-side search (name, RUT or email) and pagination.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $patients = Patient::with('user')
            ->when($search !== '', function ($query) use ($search) {
                $rut = preg_replace('/[^0-9kK]/', '', $search);
                $query->where(function ($query) use ($search, $rut) {
                    // Every word must match the first name, last name or email ("carla soto").
                    $query->where(function ($query) use ($search) {
                        foreach (preg_split('/\s+/', $search) as $word) {
                            $query->where(fn ($q) => $q->where('first_name', 'like', "%{$word}%")
                                ->orWhere('last_name', 'like', "%{$word}%")
                                ->orWhere('email', 'like', "%{$word}%"));
                        }
                    });
                    // RUT typed with or without dots/dash.
                    if (strlen($rut) >= 3 && ctype_digit(rtrim(strtoupper($rut), 'K'))) {
                        $query->orWhereRaw("replace(replace(rut, '.', ''), '-', '') like ?", ["%{$rut}%"]);
                    }
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('patients.index', compact('patients', 'search'));
    }

    public function create()
    {
        Gate::authorize('create', Patient::class);

        return view('patients.create', ['genders' => Patient::GENDERS]);
    }

    /**
     * The Vue form sends JSON and receives the PatientResource plus where to go next;
     * a classic form post receives a redirect.
     */
    public function store(PatientRequest $request)
    {
        $patient = $this->patients->register(
            $request->safe()->except('image'),
            $request->user(),
            $request->file('image'),
        );

        return $this->saved(
            $request,
            $patient,
            route('patient.clinical-record.edit', $patient),
            __('Paciente creado correctamente. Complete ahora su ficha clínica.'),
            201,
        );
    }

    public function edit(Patient $patient)
    {
        Gate::authorize('update', $patient);

        return view('patients.edit', [
            'patient' => $patient,
            'genders' => Patient::GENDERS,
        ]);
    }

    public function update(PatientRequest $request, Patient $patient)
    {
        $this->patients->update($patient, $request->safe()->except('image'));

        return $this->saved($request, $patient, route('patient.index'), __('Paciente actualizado exitosamente.'));
    }

    public function destroy(Request $request, Patient $patient)
    {
        Gate::authorize('delete', $patient);

        $this->patients->delete($patient, $request->user());

        return redirect()->route('patient.index')->withStatus(__('Paciente eliminado exitosamente.'));
    }

    private function saved(Request $request, Patient $patient, string $redirect, string $message, int $status = 200)
    {
        if (! $request->expectsJson()) {
            return redirect($redirect)->withStatus($message);
        }

        // Shown by the page the Vue form navigates to.
        $request->session()->flash('status', $message);

        return (new PatientResource($patient->fresh()))
            ->additional(['meta' => ['message' => $message, 'redirect' => $redirect]])
            ->response()
            ->setStatusCode($status);
    }
}
