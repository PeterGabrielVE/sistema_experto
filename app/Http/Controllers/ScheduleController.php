<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleRequest;
use App\Models\Schedule;
use Illuminate\Support\Facades\Gate;

class ScheduleController extends Controller
{
    public function index()
    {
        return view('schedule.index', ['schedules' => Schedule::latest()->paginate(15)]);
    }

    public function create()
    {
        Gate::authorize('create', Schedule::class);

        return view('schedule.create');
    }

    public function store(ScheduleRequest $request)
    {
        Schedule::create($request->validated());

        return redirect()->route('schedule.index')->withStatus(__('Horario creado correctamente.'));
    }

    public function edit(Schedule $schedule)
    {
        Gate::authorize('update', $schedule);

        return view('schedule.edit', ['sc' => $schedule]);
    }

    public function update(ScheduleRequest $request, Schedule $schedule)
    {
        $schedule->update($request->validated());

        return redirect()->route('schedule.index')->withStatus(__('Horario actualizado exitosamente.'));
    }

    public function destroy(Schedule $schedule)
    {
        Gate::authorize('delete', $schedule);

        $schedule->delete();

        return redirect()->route('schedule.index')->withStatus(__('Horario eliminado exitosamente.'));
    }
}
