<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Patient;
use App\Diagnosis;
class DiagnosisController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Patient $model)
    {
        return view('diagnoses.index', ['patient' => $model]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $req, $id)
    {
        $patient = Patient::find($id);
        return view('diagnoses.index', ['patient' => $patient]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) 
    {
        $diagnosis = Diagnosis::create($request->all());
        
        $diagnosis->save();

        $patient = Patient::find($request->id_patient);

        return redirect()->route('diagnosis.index',$patient)->withStatus(__('Diagnóstico creado correctamente.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function chart()
    {
        $months = [];
        for($i = 1;$i <= 12; $i++){
            $result = Diagnosis::orderBy('created_at', 'ASC')
                ->whereMonth('created_at','=',$i)
                ->count();

            $month = [ $i => $result ];
            $monts = array_push($months,$month);
        }
        
        return response()->json($months);
    }

    public function getAllByPatientindex(Patient $model)
    {
        return view('diagnoses.index', ['patient' => $model]);
    }
}
