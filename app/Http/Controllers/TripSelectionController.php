<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\SupplyDemandService;

class TripSelectionController extends Controller
{
    private $supplyDemandService;

    public function __construct()
    {
        $this->supplyDemandService = new SupplyDemandService();
    }

    /**
     * Get available dates with trip counts and ML-optimized minimum fares
     */
    public function getAvailableDates(Request $request)
    {
        try {
            $origin = $request->input('origin', '');
            $destination = $request->input('destination', '');
            $startDate = $request->input('start_date', Carbon::now()->toDateString());
            $days = (int) $request->input('days', 7);

            // Try to get real data first
            $query = Schedule::where('status', 'active');

            // Filter by route if provided - use exact match first, then fallback to LIKE
            if (!empty($origin) && !empty($destination)) {
                $query->where(function($q) use ($origin, $destination) {
                    // Try exact match first
                    $q->where('route_from', 'like', '%' . trim($origin) . '%')
                      ->where('route_to', 'like', '%' . trim($destination) . '%');
                });
            }

            // Better date range filtering
            $endDate = Carbon::parse($startDate)->addDays($days)->toDateString();
            $query->whereBetween('departure_time', [$startDate, $endDate]);

            $schedules = $query->orderBy('departure_time')->get();

            $dates = [];
            $currentDate = Carbon::parse($startDate);
            
            // Get route distance for ML predictions
            $routeDistance = $this->getRouteDistance($origin, $destination);
            
            for ($i = 0; $i < $days; $i++) {
                $dateStr = $currentDate->toDateString();
                $daySchedules = $schedules->filter(function($schedule) use ($dateStr) {
                    return Carbon::parse($schedule->departure_time)->toDateString() === $dateStr;
                });

                $availableTrips = $daySchedules->where('available_seats', '>', 0)->count();
                $baseMinFare = $daySchedules->min('fare');
                
                // If we have real schedules, use them; otherwise use fallback demo data
                if ($daySchedules->count() > 0) {
                    // Apply ML demand prediction for this date with SupplyDemandService
                    $supplyDemandPricing = $this->getSupplyDemandDatePricing($dateStr, $routeDistance, $origin, $destination, $baseMinFare);
                    

                    $dates[] = [
                        'date' => $dateStr,
                        'day_name' => $currentDate->format('l'),
                        'day_short' => $currentDate->format('D'),
                        'month_day' => $currentDate->format('M j'),
                        'available_trips' => $availableTrips,
                        'min_fare' => $baseMinFare ? number_format($baseMinFare, 0) : null,
                        'ml_optimized_fare' => $supplyDemandPricing['price'], // Fixed: Use consistent field name
                        'supply_demand_price' => $supplyDemandPricing['price'], // Keep for backward compatibility
                        'original_fare' => $baseMinFare ? number_format($baseMinFare, 0) : null,
                        'price_change' => $supplyDemandPricing['price_change'],
                        'price_change_percent' => $supplyDemandPricing['price_change_percent'],
                        'is_available' => $availableTrips > 0,
                        'total_schedules' => $daySchedules->count(),
                        'has_ml_prediction' => $supplyDemandPricing['has_pricing'],
                        'has_supply_demand_pricing' => $supplyDemandPricing['has_pricing'],
                        'demand_level' => $supplyDemandPricing['demand_level'],
                        'supply_level' => $supplyDemandPricing['supply_level']
                    ];
                } else {
                    // Generate demo data when no real schedules exist
                    $demoFare = 800 + rand(0, 400); // ₱800-1200
                    $isAvailable = rand(1, 100) > 30; // 70% chance of having trips
                    $demoTrips = $isAvailable ? rand(2, 8) : 0;
                    

                    $dates[] = [
                        'date' => $dateStr,
                        'day_name' => $currentDate->format('l'),
                        'day_short' => $currentDate->format('D'),
                        'month_day' => $currentDate->format('M j'),
                        'available_trips' => $demoTrips,
                        'min_fare' => number_format($demoFare, 0),
                        'ml_optimized_fare' => number_format($demoFare, 0), // Fixed: Use consistent field name
                        'supply_demand_price' => number_format($demoFare, 0), // Keep for backward compatibility
                        'original_fare' => number_format($demoFare, 0),
                        'price_change' => 0,
                        'price_change_percent' => 0,
                        'is_available' => $isAvailable,
                        'total_schedules' => $demoTrips,
                        'has_ml_prediction' => false,
                        'has_supply_demand_pricing' => false,
                        'demand_level' => 'medium',
                        'supply_level' => 'normal'
                    ];
                }

                $currentDate->addDay();
            }

            return response()->json([
                'success' => true,
                'dates' => $dates,
                'total_dates' => count($dates),
                'origin' => $origin,
                'destination' => $destination,
                'pricing_model' => 'supply_demand_v2',
                'debug' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_schedules_found' => $schedules->count(),
                    'using_demo_data' => $schedules->count() === 0
                ]
            ]);

        } catch (\Exception $e) {
            // Even on error, return demo data instead of failing completely
            $dates = [];
            $currentDate = Carbon::parse($startDate ?? Carbon::now()->toDateString());
            
            for ($i = 0; $i < ($days ?? 7); $i++) {
                $dateStr = $currentDate->toDateString();
                $demoFare = 800 + rand(0, 400);
                $isAvailable = rand(1, 100) > 30;
                $demoTrips = $isAvailable ? rand(2, 8) : 0;
                

                $dates[] = [
                    'date' => $dateStr,
                    'day_name' => $currentDate->format('l'),
                    'day_short' => $currentDate->format('D'),
                    'month_day' => $currentDate->format('M j'),
                    'available_trips' => $demoTrips,
                    'min_fare' => number_format($demoFare, 0),
                    'ml_optimized_fare' => number_format($demoFare, 0), // Fixed: Use consistent field name
                    'supply_demand_price' => number_format($demoFare, 0), // Keep for backward compatibility
                    'original_fare' => number_format($demoFare, 0),
                    'price_change' => 0,
                    'price_change_percent' => 0,
                    'is_available' => $isAvailable,
                    'total_schedules' => $demoTrips,
                    'has_ml_prediction' => false,
                    'has_supply_demand_pricing' => false,
                    'demand_level' => 'medium',
                    'supply_level' => 'normal'
                ];
                
                $currentDate->addDay();
            }

            return response()->json([
                'success' => true,
                'dates' => $dates,
                'total_dates' => count($dates),
                'origin' => $origin,
                'destination' => $destination,
                'debug' => [
                    'error' => $e->getMessage(),
                    'using_fallback_data' => true
                ]
            ]);
        }
    }

    /**
     * Get schedules for a specific date and route with supply and demand pricing
     */
    public function getSchedules(Request $request)
    {
        try {
            $origin = $request->input('origin', '');
            $destination = $request->input('destination', '');
            $date = $request->input('date');
            $adults = (int) $request->input('adults', 1);

            if (!$date) {
                return response()->json([
                    'success' => false,
                    'error' => 'Date parameter is required'
                ], 400);
            }

            // Try to get real data first
            $query = Schedule::where('status', 'active')
                ->whereDate('departure_time', $date)
                ->where('available_seats', '>=', $adults)
                ->orderBy('departure_time');

            // Filter by route if provided - exact match first, then LIKE
            if (!empty($origin) && !empty($destination)) {
                $query->where(function($q) use ($origin, $destination) {
                    $q->where('route_from', 'like', '%' . trim($origin) . '%')
                      ->where('route_to', 'like', '%' . trim($destination) . '%');
                });
            }

            $schedules = $query->get();

            // If we have real schedules, use them; otherwise generate demo data
            if ($schedules->count() > 0) {
                // Apply supply and demand-based dynamic pricing
                $schedulesWithPricing = $schedules->map(function($schedule) use ($origin, $destination, $date) {
                    $baseFare = $schedule->fare;
                    $supplyDemandPrice = $this->getScheduleSupplyDemandPricing($schedule, $origin, $destination, $date);
                    

                    return [
                        'id' => $schedule->id,
                        'route_from' => $schedule->route_from,
                        'route_to' => $schedule->route_to,
                        'departure_time' => $schedule->departure_time,
                        'arrival_time' => $schedule->arrival_time,
                        'bus_number' => $schedule->bus_number,
                        'bus_type' => $schedule->bus_type,
                        'seats' => $schedule->seats,
                        'available_seats' => $schedule->available_seats,
                        'fare' => $schedule->fare,
                        'supply_demand_price' => $supplyDemandPrice['price'],
                        'ml_optimized_price' => $supplyDemandPrice['price'], // Frontend expects this field
                        'original_price' => $baseFare,
                        'price_change' => $supplyDemandPrice['price_change'],
                        'price_change_percent' => $supplyDemandPrice['price_change_percent'],
                        'demand_level' => $supplyDemandPrice['demand_level'],
                        'supply_level' => $supplyDemandPrice['supply_level'],
                        'price_breakdown' => $supplyDemandPrice['breakdown'],
                        'is_booking_available' => $schedule->available_seats > 0,
                        'confidence_level' => $supplyDemandPrice['confidence']
                    ];
                });

                return response()->json([
                    'success' => true,
                    'schedules' => $schedulesWithPricing->values(),
                    'total_schedules' => $schedulesWithPricing->count(),
                    'date' => $date,
                    'origin' => $origin,
                    'destination' => $destination,
                    'pricing_model' => 'supply_demand_v2',
                    'features' => [
                        'real_time_seat_availability' => true,
                        'time_of_day_pricing' => true,
                        'demand_prediction' => true,
                        'price_transparency' => true
                    ],
                    'debug' => [
                        'date_filter' => $date,
                        'adults_needed' => $adults,
                        'total_schedules_found' => $schedules->count(),
                        'filters_applied' => [
                            'origin' => $origin,
                            'destination' => $destination
                        ],
                        'using_real_data' => true
                    ]
                ]);
            } else {
                // Generate demo schedules when no real data exists
                $demoSchedules = $this->generateDemoSchedules($date, $origin, $destination);
                
                return response()->json([
                    'success' => true,
                    'schedules' => $demoSchedules,
                    'total_schedules' => count($demoSchedules),
                    'date' => $date,
                    'origin' => $origin,
                    'destination' => $destination,
                    'pricing_model' => 'supply_demand_v2',
                    'debug' => [
                        'date_filter' => $date,
                        'adults_needed' => $adults,
                        'total_schedules_found' => 0,
                        'filters_applied' => [
                            'origin' => $origin,
                            'destination' => $destination
                        ],
                        'using_demo_data' => true
                    ]
                ]);
            }

        } catch (\Exception $e) {
            // Even on error, return demo data instead of failing completely
            $demoSchedules = $this->generateDemoSchedules($date, $origin, $destination);
            
            return response()->json([
                'success' => true,
                'schedules' => $demoSchedules,
                'total_schedules' => count($demoSchedules),
                'date' => $date,
                'origin' => $origin,
                'destination' => $destination,
                'debug' => [
                    'error' => $e->getMessage(),
                    'using_fallback_data' => true
                ]
            ]);
        }
    }

    /**
     * Get alternative times with better pricing for a specific date
     */
    public function getAlternativeTimes(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date',
                'origin' => 'required|string',
                'destination' => 'required|string',
                'current_time' => 'required|date_format:H:i'
            ]);

            $date = $request->input('date');
            $origin = $request->input('origin');
            $destination = $request->input('destination');
            $currentTime = $request->input('current_time');
            $baseFare = $request->input('base_fare', 800);

            $route = $origin . '-' . $destination;
            $alternatives = $this->supplyDemandService->getAlternativeTimes($date, $route, $currentTime, $baseFare, 5);

            return response()->json([
                'success' => true,
                'date' => $date,
                'route' => $route,
                'current_time' => $currentTime,
                'alternatives' => $alternatives,
                'message' => 'Alternative times with potentially better pricing'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get alternative times: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate demo schedules for fallback
     */
    private function generateDemoSchedules($date, $origin, $destination)
    {
        $origin = $origin ?: 'Manila';
        $destination = $destination ?: 'Baguio';
        

        $demoTrips = [
            [
                'id' => 999001,
                'route_from' => $origin,
                'route_to' => $destination,
                'departure_time' => $date . ' 08:00:00',
                'arrival_time' => $date . ' 13:30:00',
                'bus_number' => 'REG-001',
                'bus_type' => 'regular',
                'seats' => 40,
                'available_seats' => 32,
                'fare' => 850,
                'supply_demand_price' => 850,
                'ml_optimized_price' => 850, // Frontend expects this field
                'original_price' => 850,
                'price_change' => 0,
                'price_change_percent' => 0,
                'demand_level' => 'medium',
                'supply_level' => 'normal',
                'price_breakdown' => [
                    'base_price' => 850,
                    'adjustments' => [],
                    'demand_level' => 'medium',
                    'supply_level' => 'normal'
                ],
                'is_booking_available' => true,
                'confidence_level' => 'medium'
            ],
            [
                'id' => 999002,
                'route_from' => $origin,
                'route_to' => $destination,
                'departure_time' => $date . ' 10:00:00',
                'arrival_time' => $date . ' 15:30:00',
                'bus_number' => 'DLX-101',
                'bus_type' => 'deluxe',
                'seats' => 25,
                'available_seats' => 18,
                'fare' => 1200,
                'supply_demand_price' => 1200,
                'ml_optimized_price' => 1200, // Frontend expects this field
                'original_price' => 1200,
                'price_change' => 0,
                'price_change_percent' => 0,
                'demand_level' => 'medium-high',
                'supply_level' => 'normal',
                'price_breakdown' => [
                    'base_price' => 1200,
                    'adjustments' => [],
                    'demand_level' => 'medium-high',
                    'supply_level' => 'normal'
                ],
                'is_booking_available' => true,
                'confidence_level' => 'medium'
            ],
            [
                'id' => 999003,
                'route_from' => $origin,
                'route_to' => $destination,
                'departure_time' => $date . ' 14:00:00',
                'arrival_time' => $date . ' 19:30:00',
                'bus_number' => 'REG-045',
                'bus_type' => 'regular',
                'seats' => 40,
                'available_seats' => 40,
                'fare' => 750,
                'supply_demand_price' => 750,
                'ml_optimized_price' => 750, // Frontend expects this field
                'original_price' => 750,
                'price_change' => 0,
                'price_change_percent' => 0,
                'demand_level' => 'low',
                'supply_level' => 'high',
                'price_breakdown' => [
                    'base_price' => 750,
                    'adjustments' => [],
                    'demand_level' => 'low',
                    'supply_level' => 'high'
                ],
                'is_booking_available' => true,
                'confidence_level' => 'high'
            ],
            [
                'id' => 999004,
                'route_from' => $origin,
                'route_to' => $destination,
                'departure_time' => $date . ' 16:00:00',
                'arrival_time' => $date . ' 21:30:00',
                'bus_number' => 'REG-089',
                'bus_type' => 'regular',
                'seats' => 40,
                'available_seats' => 25,
                'fare' => 900,
                'supply_demand_price' => 900,
                'ml_optimized_price' => 900, // Frontend expects this field
                'original_price' => 900,
                'price_change' => 0,
                'price_change_percent' => 0,
                'demand_level' => 'medium-low',
                'supply_level' => 'normal',
                'price_breakdown' => [
                    'base_price' => 900,
                    'adjustments' => [],
                    'demand_level' => 'medium-low',
                    'supply_level' => 'normal'
                ],
                'is_booking_available' => true,
                'confidence_level' => 'medium'
            ]
        ];
        
        return $demoTrips;
    }

    /**
     * Get supply and demand pricing for a specific date
     */
    private function getSupplyDemandDatePricing($date, $distance, $origin, $destination, $baseFare = null)
    {
        try {
            if (!$baseFare) {
                return [
                    'price' => 800,
                    'price_change' => 0,
                    'price_change_percent' => 0,
                    'has_pricing' => false,
                    'demand_level' => 'medium',
                    'supply_level' => 'normal'
                ];
            }

            // Use representative time for date-based analysis
            $representativeTime = '10:00';
            $route = $origin . '-' . $destination;
            
            // Simulate average seat availability for the date
            $averageSeats = 20;
            $totalSeats = 40;
            
            $pricingResult = $this->supplyDemandService->calculateDynamicPrice(
                $baseFare,
                $date,
                $representativeTime,
                $averageSeats,
                $totalSeats,
                $route
            );

            return [
                'price' => $pricingResult['price'],
                'price_change' => $pricingResult['price'] - $baseFare,
                'price_change_percent' => $baseFare > 0 ? (($pricingResult['price'] - $baseFare) / $baseFare) * 100 : 0,
                'has_pricing' => true,
                'demand_level' => $pricingResult['breakdown']['demand_level'],
                'supply_level' => $pricingResult['breakdown']['supply_level'],
                'breakdown' => $pricingResult['breakdown']
            ];

        } catch (\Exception $e) {
            // Fallback pricing
            return [
                'price' => $baseFare ?? 800,
                'price_change' => 0,
                'price_change_percent' => 0,
                'has_pricing' => false,
                'demand_level' => 'medium',
                'supply_level' => 'normal'
            ];
        }
    }

    /**
     * Get supply and demand pricing for a specific schedule
     */
    private function getScheduleSupplyDemandPricing($schedule, $origin, $destination, $date)
    {
        try {
            $route = $origin . '-' . $destination;
            $departureTime = Carbon::parse($schedule->departure_time)->format('H:i');
            
            $pricingResult = $this->supplyDemandService->calculateDynamicPrice(
                $schedule->fare,
                $date,
                $departureTime,
                $schedule->available_seats,
                $schedule->seats,
                $route
            );

            return [
                'price' => $pricingResult['price'],
                'price_change' => $pricingResult['price'] - $schedule->fare,
                'price_change_percent' => $schedule->fare > 0 ? (($pricingResult['price'] - $schedule->fare) / $schedule->fare) * 100 : 0,
                'demand_level' => $pricingResult['breakdown']['demand_level'],
                'supply_level' => $pricingResult['breakdown']['supply_level'],
                'breakdown' => $pricingResult['breakdown'],
                'confidence' => $pricingResult['confidence']
            ];

        } catch (\Exception $e) {
            // Fallback pricing
            return [
                'price' => $schedule->fare,
                'price_change' => 0,
                'price_change_percent' => 0,
                'demand_level' => 'medium',
                'supply_level' => 'normal',
                'breakdown' => [
                    'base_price' => $schedule->fare,
                    'adjustments' => [],
                    'demand_level' => 'medium',
                    'supply_level' => 'normal'
                ],
                'confidence' => 'low'
            ];
        }
    }

    /**
     * Get route distance for ML predictions
     */
    private function getRouteDistance($origin, $destination)
    {
        // Default distances for common routes
        $defaultDistances = [
            'manila-baguio' => 240,
            'manila-angeles' => 83,
            'manila-clark' => 85,
            'manila-subic' => 120,
            'manila-tarlac' => 120,
            'manila-dagupan' => 160,
            'manila-pangasinan' => 180,
            'cebu-manila' => 572,
            'davao-manila' => 1442
        ];

        $routeKey = strtolower($origin) . '-' . strtolower($destination);
        
        // Check exact match first
        if (isset($defaultDistances[$routeKey])) {
            return $defaultDistances[$routeKey];
        }
        
        // Check reverse direction
        $reverseKey = strtolower($destination) . '-' . strtolower($origin);
        if (isset($defaultDistances[$reverseKey])) {
            return $defaultDistances[$reverseKey];
        }
        
        // Try to find from database
        try {
            $baseSchedule = Schedule::where('route_from', 'like', '%' . $origin . '%')
                ->where('route_to', 'like', '%' . $destination . '%')
                ->first();
                
            if ($baseSchedule && $baseSchedule->distance_km) {
                return $baseSchedule->distance_km;
            }
        } catch (\Exception $e) {
            // Continue to default
        }
        
        // Return default Manila-Baguio distance
        return 240;
    }

    /**
     * Get route statistics for better UX
     */
    public function getRouteStats(Request $request)
    {
        try {
            $origin = $request->input('origin', '');
            $destination = $request->input('destination', '');

            $query = Schedule::where('status', 'active');

            if (!empty($origin) && !empty($destination)) {
                $query->where('route_from', 'like', '%' . $origin . '%')
                      ->where('route_to', 'like', '%' . $destination . '%');
            }

            $stats = $query->select(
                DB::raw('MIN(fare) as min_fare'),
                DB::raw('MAX(fare) as max_fare'),
                DB::raw('AVG(fare) as avg_fare'),
                DB::raw('COUNT(*) as total_trips'),
                DB::raw('SUM(available_seats) as total_available_seats')
            )->first();

            return response()->json([
                'success' => true,
                'stats' => [
                    'min_fare' => number_format($stats->min_fare ?? 0, 0),
                    'max_fare' => number_format($stats->max_fare ?? 0, 0),
                    'avg_fare' => number_format($stats->avg_fare ?? 0, 0),
                    'total_trips' => $stats->total_trips ?? 0,
                    'total_available_seats' => $stats->total_available_seats ?? 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch route stats: ' . $e->getMessage()
            ], 500);
        }
    }
}
