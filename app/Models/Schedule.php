<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model {
    protected $fillable = ['from_terminal_id','to_terminal_id','time','date','bus_type','capacity','price','trip_type'];
    public function from(){ return $this->belongsTo(Terminal::class,'from_terminal_id'); }
    public function to(){ return $this->belongsTo(Terminal::class,'to_terminal_id'); }
}
