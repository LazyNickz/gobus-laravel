<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;


    protected $fillable = [
        'route_from',
        'route_to',
        'departure_time',
        'arrival_time',
        'bus_number',
        'seats',
        'available_seats',
        'fare',
        'status',
        'created_by',
        'bus_type',
        'trip_type',
        'capacity',
    ];

    /**
     * Simple search scope: accepts ['route_from','route_to','date'].
     */
    public function scopeSearch($query, array $filters = [])
    {
        if (!empty($filters['route_from'])) {
            $query->where('route_from', 'like', '%' . $filters['route_from'] . '%');
        }
        if (!empty($filters['route_to'])) {
            $query->where('route_to', 'like', '%' . $filters['route_to'] . '%');
        }
        if (!empty($filters['date'])) {
            $query->whereDate('departure_time', $filters['date']);
        }
        return $query;
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}

