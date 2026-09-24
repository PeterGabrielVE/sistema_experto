<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\Diagnosis;
use App\Models\Food;
use App\Models\Recommendation;
use App\Services\InferenceEngine;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Auth;
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
    public function store(Request $request, InferenceEngine $engine)
    {
        $patient = Patient::findOrFail($request->id_patient);

        $inference = $engine->classify([
            'weight' => $request->weight,
            'size' => $request->size,
            'age' => $request->age,
            'gender' => $patient->gender,
            'physical_activity' => $request->physical_activity,
        ]);

        $diagnosis = Diagnosis::create(array_merge(
            $request->except(array_keys($inference)),
            ['created_by' => Auth::user()->id],
            $inference
        ));

        return redirect()->route('result',$diagnosis)->withStatus(__('Diagnóstico creado correctamente.'));
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

    public function getAllByPatient($id)
    {
        $diagnoses = Diagnosis::where('id_patient',$id)->get();
        $patient = Patient::find($id);
        return view('diagnoses.show', ['diagnoses' => $diagnoses,'patient' => $patient]); 
    }

    public function result($id)
    {
        $diagnosis = Diagnosis::find($id);
        $patient = Patient::find($diagnosis->id_patient);
        $foods = Food::all();
        $foods_cereal = Food::where('item','Cereales')->orWhere('item','Pan')->get();
        $foods_lacteos = Food::where('item','Lácteos')->get();
        $foods_cereal_leg = Food::where('item','Cereales')->orWhere('item','Pan')->orWhere('item','Legumbres')->get();
        $foods_verduras = Food::where('item','Verduras')->get();
        $proteins = Food::where('id_group',4)->get();
        $proteinas = Food::whereIn('id',[3,4,7])->get();
        $aceites = Food::whereIn('id',[72,73,74])->get();
        $lipids = Food::where('id_group',10)->whereNotIn('id',[72,73,74])->get();
        $lipidos = Food::where('id_group',10)->get();
    
        return view('diagnoses.result',['patient' => $patient,'diagnosis' => $diagnosis,
        'foods' => $foods,'cereales' => $foods_cereal,'lacteos' => $foods_lacteos,
        'cereal_leg' => $foods_cereal_leg,'verduras' => $foods_verduras,'proteinas' => $proteinas,
        'lipidos' => $lipidos,'proteins' => $proteins,'lipids' => $lipids,
        'aceites' => $aceites,'rule' => $this->ruleFor($diagnosis),
        'categories' => InferenceEngine::CATEGORIES]);
    }

    public function download($id)
    {
        $diagnosis = Diagnosis::find($id);
        $patient = Patient::find($diagnosis->id_patient);
        $foods = Food::all();
        $foods_cereal = Food::where('item','Cereales')->orWhere('item','Pan')->get();
        $foods_lacteos = Food::where('item','Lácteos')->get();
        $foods_cereal_leg = Food::where('item','Cereales')->orWhere('item','Pan')->orWhere('item','Legumbres')->get();
        $foods_verduras = Food::where('item','Verduras')->get();
        $proteins = Food::where('id_group',4)->get();
        $proteinas = Food::whereIn('id',[3,4,7])->get();
        $aceites = Food::whereIn('id',[72,73,74])->get();
        $lipids = Food::where('id_group',10)->whereNotIn('id',[72,73,74])->get();
        $lipidos = Food::where('id_group',10)->get();
        $recomendations = Recommendation::where('id_rule',$this->ruleFor($diagnosis))->get();

        return PDF::loadView('result-pdf', ['patient' => $patient,'diagnosis' => $diagnosis,
        'foods' => $foods,'cereales' => $foods_cereal,'lacteos' => $foods_lacteos,
        'cereal_leg' => $foods_cereal_leg,'verduras' => $foods_verduras,'proteinas' => $proteinas,
        'lipidos' => $lipidos,'proteins' => $proteins,'lipids' => $lipids,
        'aceites' => $aceites,'recomendations' => $recomendations])
        ->stream('archivo.pdf');
    }

    /**
     * A doctor confirms or corrects the category. These labels feed model retraining.
     */
    public function updateRule(Request $request, Diagnosis $diagnosis)
    {
        abort_unless(in_array(Auth::user()->rol_id, [2, 3]), 403);

        $data = $request->validate([
            'id_rule' => ['required', 'integer', 'in:'.implode(',', array_keys(InferenceEngine::CATEGORIES))],
        ]);

        $diagnosis->update($data + ['inference_source' => 'manual']);

        return redirect()->route('result', $diagnosis)->withStatus(__('Categoría actualizada.'));
    }

    /**
     * Diagnoses created before the inference engine have no stored category.
     */
    private function ruleFor(Diagnosis $diagnosis): int
    {
        return $diagnosis->id_rule ?? InferenceEngine::ruleForImc((float) $diagnosis->imc);
    }
}
