<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'schedule_id',
        'qty',
        'seats',
        'status',
    ];

    protected $casts = [
        'seats' => 'array',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
