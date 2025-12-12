<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    // GET /schedules (JSON list for debugging)
    public function index(Request $request)
    {
        $query = Schedule::query();
        if ($request->filled('route_from')) {
            $query->where('route_from', 'like', '%'.$request->get('route_from').'%');
        }
        if ($request->filled('route_to')) {
            $query->where('route_to', 'like', '%'.$request->get('route_to').'%');
        }
        return response()->json($query->orderBy('departure_time')->paginate(25));
    }

    // POST /schedules
    public function store(Request $request)
    {
        $data = $request->validate([
            'route_from' => 'required|string|max:255',
            'route_to' => 'required|string|max:255',
            'departure_time' => 'required|date',
            'arrival_time' => 'nullable|date|after_or_equal:departure_time',
            'bus_number' => 'nullable|string|max:100',
            'seats' => 'required|integer|min:0',
            'available_seats' => 'nullable|integer|min:0',
            'fare' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:50',
        ]);

        $data['available_seats'] = $data['available_seats'] ?? $data['seats'];
        // Check for admin session first, then Laravel auth
        $adminEmail = $request->session()->get('gobus_admin_email');
        if ($adminEmail) {
            $data['created_by'] = $adminEmail;
        } else {
            $data['created_by'] = $request->user()->id ?? null;
        }

        $schedule = Schedule::create($data);

        return response()->json($schedule, 201);
    }

    // GET /schedules/{id}
    public function show($id)
    {
        $schedule = Schedule::findOrFail($id);
        return view('schedules.show', compact('schedule'));
    }

    // Optional small stats endpoint
    public function stats()
    {
        $total = Schedule::count();
        $upcoming = Schedule::where('departure_time', '>=', now())->count();
        return response()->json(['total' => $total, 'upcoming' => $upcoming]);
    }
}

