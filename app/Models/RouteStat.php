<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RouteStat extends Model {
    protected $fillable = ['from_terminal_id','to_terminal_id','date','day_of_week','time_slot','bookings','demand_score'];
}
