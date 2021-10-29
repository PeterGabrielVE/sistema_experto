<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Recommendation;

class RecommendationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Recommendation $model)
    {
        return view('recommendations.index', ['recommendations' => $model->paginate(15)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('recommendations.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $recommendation = Recommendation::create($request->all());
        return redirect()->route('recommendation.index')->withStatus(__('Recomendación creada correctamente.'));
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
        $re = Recommendation::find($id);
        return view('recommendations.edit', compact('re'));
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
        $re = Recommendation::find($id);
        $re->update(
            $request->all()
            );

        return redirect()->route('recommendation.index')->withStatus(__('Recomendación actualizado exitosamente.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $re = Recommendation::find($id);
        $re->delete();

        return redirect()->route('recommendation.index')->withStatus(__('Recomendación eliminado exitosamente.'));
    }
}
