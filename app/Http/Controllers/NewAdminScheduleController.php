<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NewAdminScheduleController extends Controller
{
    /**
     * Display the admin schedules page.
     */
    public function index()
    {
        return view('admin-schedules');
    }

    /**
     * Store multiple schedules (bulk create).
     */
    public function storeBulk(Request $request)
    {
        try {
            $validatedData = $request->validate([
                '*.route_from' => 'required|string|max:255',
                '*.route_to' => 'required|string|max:255',
                '*.departure_time' => 'required|date',
                '*.arrival_time' => 'nullable|date',
                '*.bus_number' => 'nullable|string|max:50',
                '*.seats' => 'required|integer|min:0',
                '*.available_seats' => 'required|integer|min:0',
                '*.fare' => 'required|numeric|min:0',
                '*.status' => ['required', Rule::in(['active', 'inactive', 'cancelled'])],
                '*.bus_type' => ['required', Rule::in(['regular', 'deluxe'])],
                '*.trip_type' => ['required', Rule::in(['single', 'round'])],
                '*.capacity' => 'required|integer|min:1',
            ]);

            $schedules = [];
            foreach ($validatedData as $scheduleData) {
                $scheduleData['created_by'] = 'admin';
                $schedules[] = Schedule::create($scheduleData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Schedules created successfully',
                'created' => count($schedules),
                'schedules' => $schedules
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schedules for a specific route and date range.
     */
    public function getSchedules(Request $request)
    {
        try {
            $query = Schedule::query();

            if ($request->filled('route_from')) {
                $query->where('route_from', 'like', '%' . $request->route_from . '%');
            }

            if ($request->filled('route_to')) {
                $query->where('route_to', 'like', '%' . $request->route_to . '%');
            }

            if ($request->filled('start_date')) {
                $query->whereDate('departure_time', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('departure_time', '<=', $request->end_date);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $schedules = $query->orderBy('departure_time')->get();

            return response()->json([
                'success' => true,
                'schedules' => $schedules
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error fetching schedules: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a schedule.
     */
    public function update(Request $request, $id)
    {
        try {
            $schedule = Schedule::findOrFail($id);

            $validatedData = $request->validate([
                'route_from' => 'sometimes|required|string|max:255',
                'route_to' => 'sometimes|required|string|max:255',
                'departure_time' => 'sometimes|required|date',
                'arrival_time' => 'nullable|date',
                'bus_number' => 'nullable|string|max:50',
                'seats' => 'sometimes|required|integer|min:0',
                'available_seats' => 'sometimes|required|integer|min:0',
                'fare' => 'sometimes|required|numeric|min:0',
                'status' => ['sometimes', 'required', Rule::in(['active', 'inactive', 'cancelled'])],
                'bus_type' => ['sometimes', 'required', Rule::in(['regular', 'deluxe'])],
                'trip_type' => ['sometimes', 'required', Rule::in(['single', 'round'])],
                'capacity' => 'sometimes|required|integer|min:1',
            ]);

            $schedule->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Schedule updated successfully',
                'schedule' => $schedule
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Schedule not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a schedule.
     */
    public function destroy($id)
    {
        try {
            $schedule = Schedule::findOrFail($id);
            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Schedule deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Schedule not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schedule statistics.
     */
    public function getStats()
    {
        try {
            $stats = [
                'total_schedules' => Schedule::count(),
                'active_schedules' => Schedule::where('status', 'active')->count(),
                'inactive_schedules' => Schedule::where('status', 'inactive')->count(),
                'regular_buses' => Schedule::where('bus_type', 'regular')->count(),
                'deluxe_buses' => Schedule::where('bus_type', 'deluxe')->count(),
                'today_schedules' => Schedule::whereDate('departure_time', today())->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error fetching statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
