<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model {
    protected $fillable = ['user_id','schedule_id','date','time','from_terminal_id','to_terminal_id','bus_type','qty','seats','price','status'];
    protected $casts = ['seats' => 'array'];
    public function user(){ return $this->belongsTo(User::class); }
    public function schedule(){ return $this->belongsTo(Schedule::class); }
}
