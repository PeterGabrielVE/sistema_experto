<?php

namespace App\Http\Controllers;

use App\Http\Requests\PatientRequest;
use App\Models\Patient;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('patients.index', ['patients' => Patient::orderBy('last_name')->paginate(15)]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('patients.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PatientRequest $request)
    {
        $data = $request->safe()->except('image');
        $data['created_by'] = $request->user()->id;
        $data['image'] = '0.jpg';

        $patient = Patient::create($data);

        if ($request->hasFile('image')) {
            // The file name is derived from the id, never from user input.
            $fileName = $patient->id.'.'.$request->file('image')->extension();
            $request->file('image')->move(public_path('patient/images'), $fileName);
            $patient->update(['image' => $fileName]);
        }

        return redirect()->route('patient.index')->withStatus(__('Paciente creado correctamente.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Patient $patient)
    {
        return view('patients.edit', compact('patient'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PatientRequest $request, Patient $patient)
    {
        $patient->update($request->safe()->except('image'));

        return redirect()->route('patient.index')->withStatus(__('Paciente actualizado exitosamente.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        $patient->delete();

        return redirect()->route('patient.index')->withStatus(__('Paciente eliminado exitosamente.'));
    }

    public function chart()
    {
        $months = [];
        for($i = 1;$i <= 12; $i++){
            $result = Patient::orderBy('created_at', 'ASC')
                ->whereMonth('created_at','=',$i)
                ->count();

            $month = [ $i => $result ];
            $monts = array_push($months,$month);
        }

        return response()->json($months);
    }
}
