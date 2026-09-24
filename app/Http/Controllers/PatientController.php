<?php

namespace App\Http\Controllers;

use App\Http\Requests\PatientRequest;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientController extends Controller
{
    public function __construct(private PatientService $patients)
    {
    }

    public function index()
    {
        return view('patients.index', ['patients' => Patient::orderBy('last_name')->paginate(15)]);
    }

    public function create()
    {
        Gate::authorize('create', Patient::class);

        return view('patients.create');
    }

    public function store(PatientRequest $request)
    {
        $patient = $this->patients->register(
            $request->safe()->except('image'),
            $request->user(),
            $request->file('image'),
        );

        return redirect()->route('patient.clinical-record.edit', $patient)
            ->withStatus(__('Paciente creado correctamente. Complete ahora su ficha clínica.'));
    }

    public function edit(Patient $patient)
    {
        Gate::authorize('update', $patient);

        return view('patients.edit', compact('patient'));
    }

    public function update(PatientRequest $request, Patient $patient)
    {
        $this->patients->update($patient, $request->safe()->except('image'));

        return redirect()->route('patient.index')->withStatus(__('Paciente actualizado exitosamente.'));
    }

    public function destroy(Request $request, Patient $patient)
    {
        Gate::authorize('delete', $patient);

        $this->patients->delete($patient, $request->user());

        return redirect()->route('patient.index')->withStatus(__('Paciente eliminado exitosamente.'));
    }
}
