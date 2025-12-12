<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\RouteStat;
use App\Models\Schedule;
use Carbon\Carbon;

class ReservationController extends Controller {
    public function index(Request $r){
        // Build user info from server session (if logged)
        $user = null;
        if ($r->session()->get('gobus_user_logged')) {
            $user = [
                'email' => $r->session()->get('gobus_user_email'),
                'name'  => $r->session()->get('gobus_user_name'),
            ];
        }

        // If JSON requested (API), return reservations list
        if ($r->wantsJson()) {
            $rows = Reservation::latest()->paginate(30);
            return response()->json($rows);
        }

        // --- NEW: load upcoming DB schedules and compute availability ---
        $today = Carbon::today()->toDateString();
        $dbSchedules = Schedule::where('date', '>=', $today)
            ->orderBy('date')->orderBy('time')
            ->limit(500)->get();

        $dbSchedules = $dbSchedules->map(function($s){
            $reserved = $s->reservations()->where('status','!=','cancelled')->sum('qty');
            $available = max(0, ($s->capacity ?? 0) - $reserved);
            return [
                'id' => $s->id,
                'from_terminal_id' => $s->from_terminal_id,
                'to_terminal_id' => $s->to_terminal_id,
                'date' => $s->date,
                'time' => $s->time,
                'bus_type' => $s->bus_type,
                'capacity' => $s->capacity,
                'price' => $s->price,
                'reserved' => (int)$reserved,
                'available' => (int)$available,
            ];
        });

        // Render the blade view and pass the user payload and db_schedules so the view can show availability
        return view('user-reservations', [
            'user' => $user,
            'db_schedules' => $dbSchedules,
        ]);
    }

    public function store(Request $r){
        // ...existing validation and route_stats code...
        $data = $r->validate([
            'user_id'=>'nullable|exists:users,id',
            'schedule_id'=>'nullable|exists:schedules,id',
            'date'=>'required|date',
            'time'=>'required',
            'from_terminal_id'=>'required|exists:terminals,id',
            'to_terminal_id'=>'required|exists:terminals,id',
            'qty'=>'required|integer|min:1',
            'price'=>'nullable|numeric',
            'seats'=>'nullable|array'
        ]);

        // If schedule_id provided, store reservation in DB
        if (!empty($data['schedule_id'])) {
            $res = Reservation::create([
                'user_id' => $data['user_id'] ?? null,
                'schedule_id' => $data['schedule_id'],
                'qty' => $data['qty'],
                'seats' => $data['seats'] ?? null,
                'status' => 'pending',
            ]);
        } else {
            $res = Reservation::create($data); // fallback if schedule-less
        }

        // update route_stats aggregate (simple increment)
        $day = strtolower(Carbon::parse($data['date'])->format('D'));
        $slot = substr($data['time'],0,5);
        $stat = RouteStat::firstOrCreate(
            ['from_terminal_id'=>$data['from_terminal_id'],'to_terminal_id'=>$data['to_terminal_id'],'date'=>$data['date'],'time_slot'=>$slot],
            ['day_of_week'=>$day,'bookings'=>0,'demand_score'=>0]
        );
        $stat->increment('bookings',$data['qty']);
        $stat->demand_score = min(1, $stat->bookings / 100.0);
        $stat->save();

        return redirect()->back()->with('success','Reservation created');
    }
}
