<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Schedule;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Schedule $model)
    {
        return view('schedule.index', ['schedules' => $model->paginate(15)]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        return view('schedule.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $recommendation = Schedule::create($request->all());
        return redirect()->route('schedule.index')->withStatus(__('Horario creado correctamente.'));
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
        $sc = Schedule::find($id);
        return view('schedule.edit', compact('sc'));
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
        $sc = Schedule::find($id);
        $sc->update(
            $request->all()
            );

        return redirect()->route('schedule.index')->withStatus(__('Horario actualizado exitosamente.'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $sc = Schedule::find($id);
        $sc->delete();

        return redirect()->route('schedule.index')->withStatus(__('Horario elimindo exitosamente.'));
    }
}
