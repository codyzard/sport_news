<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scheduler;
use Illuminate\Http\Request;

class SchedulerController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['get_schedule']]);
    }

    public function set_schedule(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 1) {
            return response()->json('Unauthorized', 401);
        }
        $schedule = Scheduler::all()->first();
        if($schedule === null){
            $schedule = Scheduler::create();
        }
        if($schedule->time === $request->cronValue){
            return response()->json([], 400);
        }
        $schedule->time = $request->cronValue;
        $schedule->save();
        return response()->json([
            'schedule' => $schedule,
        ], 200);
    }

    public function get_schedule()
    {
        $schedule = Scheduler::all()->first();
        if($schedule === null){
            return response()->json([
                'schedule' => null,
            ], 200);
        }
        return response()->json([
            'schedule' => $schedule,
        ], 200);
    }
}
