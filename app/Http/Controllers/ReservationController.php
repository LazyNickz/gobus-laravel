<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\RouteStat;
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

        // Render the blade view and pass the user payload so the navbar shows profile
        return view('user-reservations', ['user' => $user]);
    }

    public function store(Request $r){
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
        $res = Reservation::create($data);
        // update route_stats aggregate (simple increment)
        $day = strtolower(Carbon::parse($data['date'])->format('D'));
        $slot = substr($data['time'],0,5);
        $stat = RouteStat::firstOrCreate(
            ['from_terminal_id'=>$data['from_terminal_id'],'to_terminal_id'=>$data['to_terminal_id'],'date'=>$data['date'],'time_slot'=>$slot],
            ['day_of_week'=>$day,'bookings'=>0,'demand_score'=>0]
        );
        $stat->increment('bookings',$data['qty']);
        // simple normalized demand sample (update later with ML)
        $stat->demand_score = min(1, $stat->bookings / 100.0);
        $stat->save();

        return redirect()->back()->with('success','Reservation created');
    }
}
