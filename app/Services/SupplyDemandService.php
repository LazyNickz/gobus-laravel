<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SupplyDemandService
{
    private $mlApiClient;
    private $mlApiBaseUrl = 'http://127.0.0.1:8001';

    // Time-of-day demand multipliers
    private $timeMultipliers = [
        'peak_morning' => 1.25,    // 6-9 AM
        'peak_evening' => 1.30,    // 5-8 PM
        'business_hours' => 1.10,  // 10 AM-4 PM
        'off_peak' => 0.80,        // 9 PM-6 AM
        'late_night' => 0.75       // 11 PM-5 AM
    ];

    // Day-of-week demand multipliers
    private $dayMultipliers = [
        'monday' => 0.90,
        'tuesday' => 0.95,
        'wednesday' => 0.95,
        'thursday' => 1.05,
        'friday' => 1.25,
        'saturday' => 1.20,
        'sunday' => 1.15
    ];

    // Philippine holidays for demand calculation
    private $holidays = [
        '2025-12-25', // Christmas
        '2025-12-30', // Rizal Day
        '2026-01-01', // New Year
        '2026-01-02', // Additional New Year Holiday
        '2026-04-09', // Araw ng Kagitingan
        '2026-05-01', // Labor Day
        '2026-06-12', // Independence Day
        '2026-08-30', // National Heroes Day
        '2026-11-30', // Bonifacio Day
        '2026-12-25'  // Christmas
    ];

    public function __construct()
    {
        $this->mlApiClient = new Client([
            'timeout' => 10,
            'connect_timeout' => 5
        ]);
    }

    /**
     * Calculate dynamic price based on supply and demand factors
     */
    public function calculateDynamicPrice($baseFare, $date, $time, $availableSeats, $totalSeats, $route)
    {
        $finalPrice = $baseFare;
        $priceBreakdown = [
            'base_price' => $baseFare,
            'adjustments' => [],
            'demand_level' => 'medium',
            'supply_level' => 'normal'
        ];

        // 1. Supply Factor (based on seat availability)
        $supplyMultiplier = $this->calculateSupplyMultiplier($availableSeats, $totalSeats);
        if ($supplyMultiplier != 1.0) {
            $supplyIncrease = $baseFare * ($supplyMultiplier - 1.0);
            $finalPrice += $supplyIncrease;
            
            $priceBreakdown['adjustments'][] = [
                'type' => 'supply_factor',
                'description' => $this->getSupplyDescription($availableSeats, $totalSeats),
                'multiplier' => $supplyMultiplier,
                'amount' => $supplyIncrease
            ];
            
            $priceBreakdown['supply_level'] = $this->getSupplyLevel($availableSeats, $totalSeats);
        }

        // 2. Time-of-Day Demand Factor
        $timeMultiplier = $this->calculateTimeMultiplier($time);
        if ($timeMultiplier != 1.0) {
            $timeIncrease = $baseFare * ($timeMultiplier - 1.0);
            $finalPrice += $timeIncrease;
            
            $priceBreakdown['adjustments'][] = [
                'type' => 'time_demand',
                'description' => $this->getTimeDescription($time),
                'multiplier' => $timeMultiplier,
                'amount' => $timeIncrease
            ];
        }

        // 3. Day-of-Week Demand Factor
        $dayMultiplier = $this->calculateDayMultiplier($date);
        if ($dayMultiplier != 1.0) {
            $dayIncrease = $baseFare * ($dayMultiplier - 1.0);
            $finalPrice += $dayIncrease;
            
            $priceBreakdown['adjustments'][] = [
                'type' => 'day_demand',
                'description' => $this->getDayDescription($date),
                'multiplier' => $dayMultiplier,
                'amount' => $dayIncrease
            ];
        }

        // 4. ML-Based Demand Prediction
        $mlPrediction = $this->getMLDemandPrediction($date, $time, $route, $baseFare);
        if ($mlPrediction['has_prediction']) {
            $mlMultiplier = $mlPrediction['multiplier'];
            if ($mlMultiplier != 1.0) {
                $mlIncrease = $baseFare * ($mlMultiplier - 1.0);
                $finalPrice += $mlIncrease;
                
                $priceBreakdown['adjustments'][] = [
                    'type' => 'ml_demand',
                    'description' => 'Market demand prediction',
                    'multiplier' => $mlMultiplier,
                    'amount' => $mlIncrease
                ];
            }
            
            $priceBreakdown['demand_level'] = $mlPrediction['demand_level'];
        }

        // 5. Holiday Adjustment
        if ($this->isHoliday($date)) {
            $holidayMultiplier = 1.15;
            $holidayIncrease = $baseFare * 0.15;
            $finalPrice += $holidayIncrease;
            
            $priceBreakdown['adjustments'][] = [
                'type' => 'holiday',
                'description' => 'Holiday travel premium',
                'multiplier' => $holidayMultiplier,
                'amount' => $holidayIncrease
            ];
        }

        // Round to nearest 10 PHP
        $finalPrice = round($finalPrice / 10) * 10;
        $priceBreakdown['final_price'] = $finalPrice;

        return [
            'price' => $finalPrice,
            'breakdown' => $priceBreakdown,
            'confidence' => $this->calculateConfidence($availableSeats, $totalSeats)
        ];
    }


    /**
     * Calculate supply multiplier based on seat availability
     */
    public function calculateSupplyMultiplier($availableSeats, $totalSeats)
    {
        $occupancyRate = ($totalSeats - $availableSeats) / $totalSeats;
        
        if ($availableSeats < 5) {
            return 1.40; // Very high demand, very low supply
        } elseif ($availableSeats < 10) {
            return 1.20; // High demand, low supply
        } elseif ($availableSeats < 15) {
            return 1.10; // Medium-high demand
        } elseif ($availableSeats > 25) {
            return 0.85; // Low demand, high supply
        } elseif ($availableSeats > 30) {
            return 0.75; // Very low demand
        }
        
        return 1.0; // Normal supply/demand balance
    }


    /**
     * Calculate time-of-day multiplier
     */
    public function calculateTimeMultiplier($time)
    {
        $hour = Carbon::parse($time)->format('H');
        $hour = (int) $hour;

        // Peak Morning (6-9 AM)
        if ($hour >= 6 && $hour <= 9) {
            return $this->timeMultipliers['peak_morning'];
        }
        
        // Peak Evening (5-8 PM)
        if ($hour >= 17 && $hour <= 20) {
            return $this->timeMultipliers['peak_evening'];
        }
        
        // Business Hours (10 AM-4 PM)
        if ($hour >= 10 && $hour <= 16) {
            return $this->timeMultipliers['business_hours'];
        }
        
        // Late Night (11 PM-5 AM)
        if ($hour >= 23 || $hour <= 5) {
            return $this->timeMultipliers['late_night'];
        }
        
        // Off Peak (9 PM-6 AM, excluding peak times)
        if ($hour >= 21 || $hour <= 6) {
            return $this->timeMultipliers['off_peak'];
        }
        
        return 1.0; // Default
    }


    /**
     * Calculate day-of-week multiplier
     */
    public function calculateDayMultiplier($date)
    {
        $carbonDate = Carbon::parse($date);
        $dayName = strtolower($carbonDate->format('l'));
        
        return $this->dayMultipliers[$dayName] ?? 1.0;
    }

    /**
     * Get ML-based demand prediction
     */
    private function getMLDemandPrediction($date, $time, $route, $baseFare)
    {
        try {
            // Parse route to get origin and destination
            $routeParts = explode('-', $route);
            $origin = $routeParts[0] ?? 'Manila';
            $destination = $routeParts[1] ?? 'Baguio';
            
            $features = $this->buildMLFeatures($date, $time, $origin, $destination, 240);
            
            $response = $this->mlApiClient->post($this->mlApiBaseUrl . '/predict', [
                'json' => $features,
                'timeout' => 5
            ]);

            $result = json_decode($response->getBody(), true);
            $predictedDemand = $result['prediction'] ?? 35;

            // Convert prediction to multiplier
            $multiplier = 1.0;
            $demandLevel = 'medium';

            if ($predictedDemand > 60) {
                $multiplier = 1.25;
                $demandLevel = 'high';
            } elseif ($predictedDemand > 40) {
                $multiplier = 1.10;
                $demandLevel = 'medium-high';
            } elseif ($predictedDemand > 20) {
                $multiplier = 1.0;
                $demandLevel = 'medium';
            } elseif ($predictedDemand > 10) {
                $multiplier = 0.95;
                $demandLevel = 'medium-low';
            } else {
                $multiplier = 0.85;
                $demandLevel = 'low';
            }

            return [
                'has_prediction' => true,
                'multiplier' => $multiplier,
                'demand_level' => $demandLevel,
                'predicted_demand' => $predictedDemand
            ];

        } catch (\Exception $e) {
            // Return neutral prediction if ML service is unavailable
            return [
                'has_prediction' => false,
                'multiplier' => 1.0,
                'demand_level' => 'medium',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build ML features for demand prediction
     */
    private function buildMLFeatures($date, $time, $origin, $destination, $distanceKm)
    {
        $carbonDate = Carbon::parse($date);
        $carbonTime = Carbon::parse($time);
        $hour = $carbonTime->format('H');
        
        return [
            'distance_km' => $distanceKm,
            'avg_speed' => 60,
            'is_weekend' => $carbonDate->isWeekend() ? 1 : 0,
            'is_holiday' => $this->isHoliday($date) ? 1 : 0,
            'date' => $date,
            'time' => $time,
            'hour_of_day' => (int) $hour,
            'route' => "{$origin}-{$destination}",
            'origin' => $origin,
            'destination' => $destination,
            'days_to_christmas' => $this->daysToChristmas($carbonDate),
            'days_to_new_year' => $this->daysToNewYear($carbonDate)
        ];
    }

    /**
     * Check if date is a holiday
     */
    private function isHoliday($date)
    {
        return in_array($date, $this->holidays);
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

    /**
     * Get supply level description
     */
    private function getSupplyLevel($availableSeats, $totalSeats)
    {
        if ($availableSeats < 5) return 'very_low';
        if ($availableSeats < 10) return 'low';
        if ($availableSeats > 25) return 'high';
        if ($availableSeats > 30) return 'very_high';
        return 'normal';
    }

    /**
     * Get supply description
     */
    private function getSupplyDescription($availableSeats, $totalSeats)
    {
        $level = $this->getSupplyLevel($availableSeats, $totalSeats);
        
        switch ($level) {
            case 'very_low':
                return 'Very limited seats remaining (High demand)';
            case 'low':
                return 'Limited seats available';
            case 'high':
                return 'Good seat availability';
            case 'very_high':
                return 'Plenty of seats available';
            default:
                return 'Normal availability';
        }
    }

    /**
     * Get time description
     */
    private function getTimeDescription($time)
    {
        $hour = Carbon::parse($time)->format('H');
        $hour = (int) $hour;

        if ($hour >= 6 && $hour <= 9) {
            return 'Peak morning hours (6-9 AM)';
        } elseif ($hour >= 17 && $hour <= 20) {
            return 'Peak evening hours (5-8 PM)';
        } elseif ($hour >= 10 && $hour <= 16) {
            return 'Business hours';
        } elseif ($hour >= 23 || $hour <= 5) {
            return 'Late night/early morning';
        } else {
            return 'Off-peak hours';
        }
    }

    /**
     * Get day description
     */
    private function getDayDescription($date)
    {
        $carbonDate = Carbon::parse($date);
        $dayName = $carbonDate->format('l');
        
        if ($carbonDate->isWeekend()) {
            return "Weekend travel ($dayName)";
        } elseif ($dayName === 'Friday') {
            return 'Friday travel (end of week)';
        } elseif (in_array($dayName, ['Monday', 'Tuesday'])) {
            return "$dayName travel (lower demand)";
        } else {
            return "$dayName travel";
        }
    }

    /**
     * Calculate confidence level for pricing
     */
    private function calculateConfidence($availableSeats, $totalSeats)
    {
        $occupancyRate = ($totalSeats - $availableSeats) / $totalSeats;
        
        if ($occupancyRate > 0.8 || $occupancyRate < 0.2) {
            return 'high'; // Clear supply/demand signals
        } elseif ($occupancyRate > 0.6 || $occupancyRate < 0.4) {
            return 'medium'; // Moderate signals
        }
        
        return 'low'; // Unclear signals
    }

    /**
     * Get alternative times with better pricing
     */
    public function getAlternativeTimes($date, $route, $originalTime, $baseFare, $limit = 3)
    {
        $alternatives = [];
        $originalHour = Carbon::parse($originalTime)->format('H');
        
        // Check different time slots
        $timeSlots = [
            'early_morning' => '06:00',
            'morning' => '08:00',
            'midday' => '12:00',
            'afternoon' => '15:00',
            'evening' => '18:00',
            'night' => '21:00'
        ];

        foreach ($timeSlots as $slotName => $time) {
            if ($time === $originalTime) continue;
            
            // Simulate pricing for alternative time
            $simulatedSeats = 20; // Assume average availability
            $simulatedTotalSeats = 40;
            
            $pricing = $this->calculateDynamicPrice(
                $baseFare, 
                $date, 
                $time, 
                $simulatedSeats, 
                $simulatedTotalSeats, 
                $route
            );
            
            $alternatives[] = [
                'time' => $time,
                'slot_name' => $slotName,
                'price' => $pricing['price'],
                'savings' => $baseFare - $pricing['price'],
                'demand_level' => $pricing['breakdown']['demand_level']
            ];
        }
        
        // Sort by price (lowest first) and return top alternatives
        usort($alternatives, function($a, $b) {
            return $a['price'] - $b['price'];
        });
        
        return array_slice($alternatives, 0, $limit);
    }
}
