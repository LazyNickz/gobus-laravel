<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Carbon\Carbon;
use App\Services\SupplyDemandService;

class DynamicPricingController extends Controller
{
    private $mlApiClient;
    private $supplyDemandService;
    private $mlApiBaseUrl = 'http://127.0.0.1:8001';

    public function __construct()
    {
        $this->mlApiClient = new Client([
            'timeout' => 60,
            'connect_timeout' => 10
        ]);
        $this->supplyDemandService = new SupplyDemandService();
    }

    /**
     * Get dynamic pricing for a specific route and date range
     * Returns pricing for 7 days starting from the selected date
     */
    public function getDynamicPricing(Request $request)
    {
        $request->validate([
            'from' => 'required|string|max:255',
            'to' => 'required|string|max:255', 
            'start_date' => 'required|date|after_or_equal:today',
            'bus_type' => 'nullable|string|in:regular,deluxe',
            'distance_km' => 'nullable|numeric|min:0'
        ]);

        $from = $request->input('from');
        $to = $request->input('to');
        $startDate = $request->input('start_date');
        $busType = $request->input('bus_type', 'regular');
        $distanceKm = $request->input('distance_km', 100); // Default distance

        try {
            // Generate 7-day predictions with enhanced supply and demand
            $pricingData = $this->generateSevenDaySupplyDemandPricing($from, $to, $startDate, $busType, $distanceKm);
            
            return response()->json([
                'success' => true,
                'route' => ['from' => $from, 'to' => $to],
                'start_date' => $startDate,
                'bus_type' => $busType,
                'pricing' => $pricingData,
                'pricing_model' => 'supply_demand_v2',
                'enhancements' => [
                    'time_of_day_pricing' => true,
                    'seat_availability_factors' => true,
                    'ml_integration' => true,
                    'demand_transparency' => true
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate dynamic pricing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time pricing for a specific schedule
     */
    public function getRealTimePricing(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|integer',
            'from' => 'required|string|max:255',
            'to' => 'required|string|max:255'
        ]);

        try {
            $scheduleId = $request->input('schedule_id');
            $from = $request->input('from');
            $to = $request->input('to');

            // Get schedule details
            $schedule = \App\Models\Schedule::find($scheduleId);
            if (!$schedule) {
                return response()->json([
                    'success' => false,
                    'error' => 'Schedule not found'
                ], 404);
            }

            // Calculate real-time pricing using SupplyDemandService
            $route = $from . '-' . $to;
            $pricingResult = $this->supplyDemandService->calculateDynamicPrice(
                $schedule->fare,
                Carbon::parse($schedule->departure_time)->format('Y-m-d'),
                Carbon::parse($schedule->departure_time)->format('H:i'),
                $schedule->available_seats,
                $schedule->seats,
                $route
            );

            return response()->json([
                'success' => true,
                'schedule_id' => $scheduleId,
                'base_price' => $schedule->fare,
                'dynamic_price' => $pricingResult['price'],
                'breakdown' => $pricingResult['breakdown'],
                'confidence' => $pricingResult['confidence'],
                'alternatives' => $this->supplyDemandService->getAlternativeTimes(
                    Carbon::parse($schedule->departure_time)->format('Y-m-d'),
                    $route,
                    Carbon::parse($schedule->departure_time)->format('H:i'),
                    $schedule->fare,
                    3
                )
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get real-time pricing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get time-based demand analysis for a specific date
     */
    public function getTimeBasedDemand(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'from' => 'required|string|max:255',
            'to' => 'required|string|max:255'
        ]);

        try {
            $date = $request->input('date');
            $from = $request->input('from');
            $to = $request->input('to');
            $route = $from . '-' . $to;

            // Generate time slots for analysis
            $timeSlots = [
                'early_morning' => '06:00',
                'morning' => '08:00', 
                'mid_morning' => '10:00',
                'midday' => '12:00',
                'afternoon' => '14:00',
                'evening' => '16:00',
                'peak_evening' => '18:00',
                'night' => '20:00'
            ];

            $demandAnalysis = [];
            foreach ($timeSlots as $slotName => $time) {
                // Simulate different seat availability scenarios
                $scenarios = [
                    ['available' => 35, 'total' => 40], // High availability
                    ['available' => 15, 'total' => 40], // Medium availability
                    ['available' => 5, 'total' => 40],  // Low availability
                ];

                $scenarioResults = [];
                foreach ($scenarios as $scenario) {
                    $pricing = $this->supplyDemandService->calculateDynamicPrice(
                        800, // Base fare
                        $date,
                        $time,
                        $scenario['available'],
                        $scenario['total'],
                        $route
                    );
                    
                    $scenarioResults[] = [
                        'available_seats' => $scenario['available'],
                        'price' => $pricing['price'],
                        'demand_level' => $pricing['breakdown']['demand_level'],
                        'supply_level' => $pricing['breakdown']['supply_level']
                    ];
                }

                $demandAnalysis[] = [
                    'time_slot' => $slotName,
                    'time' => $time,
                    'scenarios' => $scenarioResults,
                    'recommended_price' => $scenarioResults[1]['price'], // Medium availability
                    'peak_pricing' => end($scenarioResults)['price'], // Low availability
                    'discount_opportunity' => $scenarioResults[0]['price'] // High availability
                ];
            }

            return response()->json([
                'success' => true,
                'date' => $date,
                'route' => $route,
                'demand_analysis' => $demandAnalysis,
                'insights' => [
                    'peak_times' => ['peak_evening', 'evening'],
                    'discount_times' => ['early_morning', 'midday'],
                    'recommended_booking_window' => 'Book 2-7 days in advance for best prices'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to analyze time-based demand: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate enhanced 7-day pricing with supply and demand factors
     */
    private function generateSevenDaySupplyDemandPricing($from, $to, $startDate, $busType, $distanceKm)
    {
        $pricingData = [];
        $baseDate = Carbon::parse($startDate);
        $basePrice = $this->calculateBasePrice($distanceKm, $busType);
        $route = $from . '-' . $to;

        for ($i = 0; $i < 7; $i++) {
            $currentDate = $baseDate->copy()->addDays($i);
            $dateStr = $currentDate->format('Y-m-d');
            
            // Analyze multiple time slots for this date
            $timeAnalysis = $this->analyzeDateTimeSlots($dateStr, $route, $basePrice);
            
            $pricingData[] = [
                'date' => $dateStr,
                'day_of_week' => $currentDate->format('l'),
                'is_weekend' => $currentDate->isWeekend(),
                'is_holiday' => $this->isHoliday($currentDate),
                'base_price' => $basePrice,
                'time_analysis' => $timeAnalysis,
                'recommended_min_price' => min(array_column($timeAnalysis, 'price')),
                'recommended_max_price' => max(array_column($timeAnalysis, 'price')),
                'price_range' => [
                    'min' => min(array_column($timeAnalysis, 'price')),
                    'max' => max(array_column($timeAnalysis, 'price'))
                ],
                'demand_forecast' => $this->getDemandForecast($dateStr, $route),
                'supply_forecast' => $this->getSupplyForecast($dateStr),
                'pricing_insights' => $this->getPricingInsights($timeAnalysis, $currentDate)
            ];
        }

        return $pricingData;
    }

    /**
     * Analyze time slots for a specific date
     */
    private function analyzeDateTimeSlots($date, $route, $basePrice)
    {
        $timeSlots = [
            '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00'
        ];

        $analysis = [];
        foreach ($timeSlots as $time) {
            // Simulate realistic seat availability patterns
            $hour = (int) Carbon::parse($time)->format('H');
            $availableSeats = $this->simulateSeatAvailability($date, $hour);
            
            $pricing = $this->supplyDemandService->calculateDynamicPrice(
                $basePrice,
                $date,
                $time,
                $availableSeats,
                40, // Standard bus capacity
                $route
            );

            $analysis[] = [
                'time' => $time,
                'price' => $pricing['price'],
                'available_seats' => $availableSeats,
                'demand_level' => $pricing['breakdown']['demand_level'],
                'supply_level' => $pricing['breakdown']['supply_level'],
                'adjustments' => $pricing['breakdown']['adjustments']
            ];
        }

        return $analysis;
    }

    /**
     * Simulate realistic seat availability based on date and time
     */
    private function simulateSeatAvailability($date, $hour)
    {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeek;
        
        // Base availability
        $baseAvailability = 25;
        
        // Weekend effect
        if ($carbonDate->isWeekend()) {
            $baseAvailability -= 8; // Higher demand on weekends
        }
        
        // Time-based patterns
        if ($hour >= 6 && $hour <= 9) {
            $baseAvailability -= 10; // Peak morning - higher demand
        } elseif ($hour >= 17 && $hour <= 20) {
            $baseAvailability -= 12; // Peak evening - highest demand
        } elseif ($hour >= 23 || $hour <= 5) {
            $baseAvailability += 5; // Late night - lower demand
        }
        
        // Holiday effect
        if ($this->isHoliday($carbonDate)) {
            $baseAvailability -= 15; // Much higher demand on holidays
        }
        
        // Add some randomness
        $baseAvailability += rand(-5, 5);
        
        return max(1, min(40, $baseAvailability)); // Keep between 1 and 40
    }

    /**
     * Get demand forecast for a date
     */
    private function getDemandForecast($date, $route)
    {
        try {
            $carbonDate = Carbon::parse($date);
            $features = [
                'distance_km' => 240,
                'avg_speed' => 60,
                'is_weekend' => $carbonDate->isWeekend() ? 1 : 0,
                'is_holiday' => $this->isHoliday($carbonDate) ? 1 : 0,
                'date' => $date,
                'time' => '12:00', // Representative time
                'route' => $route,
                'origin' => explode('-', $route)[0],
                'destination' => explode('-', $route)[1] ?? '',
                'days_to_christmas' => $this->daysToChristmas($carbonDate),
                'days_to_new_year' => $this->daysToNewYear($carbonDate)
            ];

            $response = $this->mlApiClient->post($this->mlApiBaseUrl . '/predict', [
                'json' => $features,
                'timeout' => 5
            ]);

            $result = json_decode($response->getBody(), true);
            $prediction = $result['prediction'] ?? 35;

            if ($prediction > 60) {
                $level = 'high';
                $description = 'High demand expected';
            } elseif ($prediction > 40) {
                $level = 'medium-high';
                $description = 'Moderate to high demand';
            } elseif ($prediction > 20) {
                $level = 'medium';
                $description = 'Normal demand';
            } else {
                $level = 'low';
                $description = 'Lower demand expected';
            }

            return [
                'level' => $level,
                'score' => $prediction,
                'description' => $description
            ];

        } catch (\Exception $e) {
            return [
                'level' => 'medium',
                'score' => 35,
                'description' => 'Demand analysis unavailable'
            ];
        }
    }

    /**
     * Get supply forecast for a date
     */
    private function getSupplyForecast($date)
    {
        $carbonDate = Carbon::parse($date);
        
        // Simulate supply based on typical patterns
        $baseSupply = 20; // Average number of trips
        
        if ($carbonDate->isWeekend()) {
            $baseSupply += 5; // More trips on weekends
        }
        
        if ($this->isHoliday($carbonDate)) {
            $baseSupply += 8; // Extra trips for holidays
        }
        
        return [
            'estimated_trips' => $baseSupply,
            'total_seats' => $baseSupply * 40,
            'average_occupancy' => $this->simulateSeatAvailability($date, 12) / 40
        ];
    }

    /**
     * Get pricing insights for better user experience
     */
    private function getPricingInsights($timeAnalysis, $date)
    {
        $prices = array_column($timeAnalysis, 'price');
        $minPrice = min($prices);
        $maxPrice = max($prices);
        $avgPrice = array_sum($prices) / count($prices);
        
        $insights = [];
        
        // Find best deals
        $bestDeal = array_filter($timeAnalysis, function($slot) use ($minPrice) {
            return $slot['price'] == $minPrice;
        });
        
        if (!empty($bestDeal)) {
            $bestSlot = reset($bestDeal);
            $insights[] = "Best time to travel: {$bestSlot['time']} (₱{$bestSlot['price']})";
        }
        
        // Peak pricing warning
        $peakPrice = max($prices);
        if ($peakPrice > $avgPrice * 1.2) {
            $insights[] = "Peak pricing detected. Consider traveling at off-peak hours for savings.";
        }
        
        // Weekend/holiday insights
        if ($date->isWeekend()) {
            $insights[] = "Weekend travel typically has higher demand and prices.";
        }
        
        if ($this->isHoliday($date)) {
            $insights[] = "Holiday travel has premium pricing due to high demand.";
        }
        
        return $insights;
    }

    /**
     * Calculate base price based on distance and bus type
     */
    private function calculateBasePrice($distanceKm, $busType)
    {
        $baseRatePerKm = $busType === 'deluxe' ? 8.5 : 6.0; // PHP per km
        $basePrice = $distanceKm * $baseRatePerKm;
        
        // Minimum fare
        return max($basePrice, $busType === 'deluxe' ? 800 : 500);
    }

    /**
     * Check if date is a holiday (Philippine holidays)
     */
    private function isHoliday($date)
    {
        $year = $date->year;
        $holidays = [
            // Fixed holidays
            $year . '-01-01', // New Year's Day
            $year . '-04-09', // Araw ng Kagitingan
            $year . '-05-01', // Labor Day
            $year . '-06-12', // Independence Day
            $year . '-08-30', // National Heroes Day (last Monday of August)
            $year . '-11-30', // Bonifacio Day
            $year . '-12-25', // Christmas Day
            $year . '-12-30', // Rizal Day
        ];
        
        $dateString = $date->format('Y-m-d');
        
        // Check fixed holidays
        if (in_array($dateString, $holidays)) {
            return true;
        }
        
        // Special holiday calculations
        if ($dateString === $this->getLastMondayOfAugust($year)) {
            return true;
        }
        
        return false;
    }

    /**
     * Get last Monday of August for National Heroes Day
     */
    private function getLastMondayOfAugust($year)
    {
        $augustLastDay = Carbon::createFromDate($year, 8, 31);
        $daysToSubtract = ($augustLastDay->dayOfWeek + 6) % 7; // Days back to Monday
        $lastMonday = $augustLastDay->subDays($daysToSubtract);
        return $lastMonday->format('Y-m-d');
    }

    /**
     * Calculate days to Christmas
     */
    private function daysToChristmas($date)
    {
        $christmas = Carbon::createFromDate($date->year, 12, 25);
        if ($date->gt($christmas)) {
            $christmas->addYear();
        }
        return $christmas->diffInDays($date);
    }

    /**
     * Calculate days to New Year
     */
    private function daysToNewYear($date)
    {
        $newYear = Carbon::createFromDate($date->year + 1, 1, 1);
        return $newYear->diffInDays($date);
    }
}
