<?php
namespace App\Http\Controllers;
use App\Models\Schedule;
use App\Models\RouteStat;
use Illuminate\Http\Request;

class ScheduleController extends Controller {
    public function store(Request $r){
        $data = $r->validate([
            'from_terminal_id'=>'required|exists:terminals,id',
            'to_terminal_id'=>'required|exists:terminals,id',
            'time'=>'required',
            'date'=>'nullable|date',
            'bus_type'=>'required|in:regular,deluxe',
            'capacity'=>'nullable|integer',
            'price'=>'nullable|numeric'
        ]);
        Schedule::create($data);
        return redirect()->back()->with('success','Schedule created');
    }

    // endpoint to fetch aggregated stats for ML (JSON)
    public function stats(Request $r){
        $from = $r->input('from_terminal_id');
        $to = $r->input('to_terminal_id');
        $qb = RouteStat::query();
        if($from) $qb->where('from_terminal_id',$from);
        if($to) $qb->where('to_terminal_id',$to);
        return $qb->orderBy('date','desc')->limit(200)->get();
    }
}
