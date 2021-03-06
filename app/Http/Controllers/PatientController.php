<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Patient;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Auth;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Patient $model)
    {
        return view('patients.index', ['patients' => $model->paginate(15)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $regions = [ 1=>'Arica y Parinacota',2 =>'Tarapacá'];
        $comunas = [ 1=>'Arica y Parinacota',2 =>'Tarapacá'];
        return view('patients.create',['regions'=>$regions, 'comunas'=>$comunas]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) 
    {
        $validator = Validator::make($request->all(), [ 'image'=>'image' ]);

        if ( $validator->fails() )
            return back()->withInput()->withStatus(__('Solo se permiten imágenes.'));

        if ($request->first_name == null OR $request->first_name == "")
            return back()->withInput()->with('status',__('Es necesario ingresar el nombre del paciente.'));
    
        if ($request->last_name == null OR $request->last_name == "")
            return back()->withInput()->withStatus(__('Es necesario ingresar el apellido del paciente.'));
    
        if($request->address == null OR $request->address == "")
            return back()->withInput()->withStatus(__('Es necesario ingresar la dirección del paciente.'));

        /*if($request->id_region== null OR $request->id_region == "")
            return back()->withInput()->withStatus(__('Es necesario ingresar la región del paciente.'));

        if($request->id_comuna == null OR $request->id_comuna == "")
            return back()->withInput()->withStatus(__('Es necesario ingresar la comuna del paciente.'));
        */
        if($request->gender == null OR $request->gender == "")
            return back()->withInput()->withStatus(__('Es necesario ingresar el sexo del paciente.'));

        if ( strlen($request->first_name)<3 )
            return back()->withInput()->withStatus(__('El nombre del paciente debe tener como mínimo 3 caracteres.'));

        if ( strlen($request->last_name)<4 )
            return back()->withInput()->withInput()->withStatus(__('El apellido del paciente debe tener como mínimo 3 caracteres.'));

        if ( strlen($request->address)<4 )
            return back()->withInput()->withStatus(__('La dirección del paciente debe tener como mínimo 3 caracteres.'));

        $search = Patient::where('rut',$request->rut)->get();
        //dd($search);
        if(count($search) > 0){
           // return back()->withInput()->with('status',__('Es necesario ingresar el nombre del paciente.'));
            return back()->withInput()->withErrors('Ya existe un paciente con este RUT.'); 
        }
        $patient = Patient::create($request->all());
        if( $request->file('image') )
        {
            $path = public_path().'/patient/images';
            if($request->get('oldImage') !='0.png' )
                File::delete($path.'/'.$request->get('oldImage'));
            $extension = $request->file('image')->getClientOriginalExtension();
            $fileName = $patient->id . '.' . $extension;
            $request->file('image')->move($path, $fileName);
            $patient->image = $fileName;
            $patient->created_by = Auth::user()->id;
        }
        else
            $patient->image = '0.jpg';

        $patient->save();

        return redirect()->route('patient.index')->withStatus(__('Paciente creado correctamente.'));
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
        $patient = Patient::find($id);
        $regions = [ 1=>'Arica y Parinacota',2 =>'Tarapacá'];
        $comunas = [ 1=>'Arica y Parinacota',2 =>'Tarapacá'];
        return view('patients.edit', compact('patient','regions','comunas'));
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
        $pat = Patient::find($id);
        $pat->update(
            $request->all()
            );

        return redirect()->route('patient.index')->withStatus(__('Paciente actualizado exitosamente.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $pat = Patient::find($id);
        $pat->delete();

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
