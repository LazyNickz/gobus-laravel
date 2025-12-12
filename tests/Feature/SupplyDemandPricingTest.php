<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Schedule;
use App\Services\SupplyDemandService;
use App\Http\Controllers\DynamicPricingController;
use App\Http\Controllers\TripSelectionController;

class SupplyDemandPricingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected SupplyDemandService $pricingService;
    protected DynamicPricingController $pricingController;
    protected TripSelectionController $tripController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = new SupplyDemandService();
        $this->pricingController = new DynamicPricingController();
        $this->tripController = new TripSelectionController();
        
        // Seed the database with test data
        $this->seed(\Database\Seeders\DemoSeeder::class);
    }

    /** @test */
    public function it_applies_time_based_demand_multipliers_correctly()
    {
        $testCases = [
            ['hour' => 7, 'expected_multiplier' => 1.2],   // Morning peak
            ['hour' => 8, 'expected_multiplier' => 1.15],  // Morning rush
            ['hour' => 12, 'expected_multiplier' => 1.0],  // Lunch time - normal
            ['hour' => 17, 'expected_multiplier' => 1.25], // Evening peak
            ['hour' => 18, 'expected_multiplier' => 1.2],  // Evening rush
            ['hour' => 22, 'expected_multiplier' => 0.8],  // Late night - low demand
            ['hour' => 2, 'expected_multiplier' => 0.7],   // Very early - low demand
        ];

        foreach ($testCases as $case) {
            $actualMultiplier = $this->pricingService->getTimeOfDayDemandMultiplier($case['hour']);
            
            $this->assertEquals(
                $case['expected_multiplier'], 
                $actualMultiplier, 
                "Time {$case['hour']} should have multiplier {$case['expected_multiplier']}"
            );
        }
    }

    /** @test */
    public function it_applies_day_of_week_multipliers_correctly()
    {
        $testCases = [
            ['day' => 5, 'expected_multiplier' => 1.15], // Friday
            ['day' => 6, 'expected_multiplier' => 1.20], // Saturday
            ['day' => 7, 'expected_multiplier' => 1.10], // Sunday
            ['day' => 1, 'expected_multiplier' => 0.90], // Monday
            ['day' => 2, 'expected_multiplier' => 0.95], // Tuesday
            ['day' => 3, 'expected_multiplier' => 1.0],  // Wednesday
            ['day' => 4, 'expected_multiplier' => 1.05], // Thursday
        ];

        foreach ($testCases as $case) {
            $actualMultiplier = $this->pricingService->getDayOfWeekMultiplier($case['day']);
            
            $this->assertEquals(
                $case['expected_multiplier'], 
                $actualMultiplier, 
                "Day {$case['day']} should have multiplier {$case['expected_multiplier']}"
            );
        }
    }

    /** @test */
    public function it_calculates_seat_availability_multipliers_correctly()
    {
        $testCases = [
            ['available' => 5, 'total' => 40, 'expected_multiplier' => 1.15],  // Very limited
            ['available' => 15, 'total' => 40, 'expected_multiplier' => 1.08], // Limited
            ['available' => 25, 'total' => 40, 'expected_multiplier' => 1.0],  // Moderate
            ['available' => 35, 'total' => 40, 'expected_multiplier' => 0.95], // Plenty
            ['available' => 40, 'total' => 40, 'expected_multiplier' => 0.90], // Full availability
        ];

        foreach ($testCases as $case) {
            $actualMultiplier = $this->pricingService->getSeatAvailabilityMultiplier(
                $case['available'], 
                $case['total']
            );
            
            $this->assertEquals(
                $case['expected_multiplier'], 
                $actualMultiplier, 
                "Availability {$case['available']}/{$case['total']} should have multiplier {$case['expected_multiplier']}"
            );
        }
    }

    /** @test */
    public function it_determines_demand_levels_correctly()
    {
        $testCases = [
            ['combined_score' => 0.3, 'expected_level' => 'low'],
            ['combined_score' => 0.5, 'expected_level' => 'medium-low'],
            ['combined_score' => 0.7, 'expected_level' => 'medium'],
            ['combined_score' => 1.2, 'expected_level' => 'medium-high'],
            ['combined_score' => 1.8, 'expected_level' => 'high'],
        ];

        foreach ($testCases as $case) {
            $actualLevel = $this->pricingService->classifyDemandLevel($case['combined_score']);
            
            $this->assertEquals(
                $case['expected_level'], 
                $actualLevel, 
                "Score {$case['combined_score']} should be classified as {$case['expected_level']}"
            );
        }
    }

    /** @test */
    public function it_calculates_ml_optimized_prices_correctly()
    {
        $schedule = Schedule::factory()->create([
            'base_fare' => 750,
            'departure_time' => '2025-12-20 08:00:00', // Morning peak
            'available_seats' => 15,
            'total_seats' => 40,
        ]);

        $request = new \Illuminate\Http\Request([
            'route_from' => $schedule->route_from,
            'route_to' => $schedule->route_to,
            'depart_date' => '2025-12-20',
            'departure_time' => '08:00',
            'available_seats' => 15,
            'total_seats' => 40,
        ]);

        $response = $this->pricingController->calculatePrice($request);
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertIsArray($data['pricing_breakdown']);
        $this->assertIsNumeric($data['final_price']);
        $this->assertGreaterThan(0, $data['final_price']);
        $this->assertGreaterThanOrEqual($schedule->base_fare, $data['final_price']);
    }

    /** @test */
    public function it_handles_real_time_pricing_updates()
    {
        $schedule = Schedule::factory()->create([
            'departure_time' => '2025-12-20 10:00:00',
            'available_seats' => 20,
            'total_seats' => 40,
        ]);

        // First request
        $response1 = $this->pricingController->getRealtimePricing([
            'schedule_id' => $schedule->id,
            'current_seats' => 20,
        ]);

        $data1 = json_decode($response1->getContent(), true);
        $price1 = $data1['price'];

        // Simulate seat booking
        $schedule->update(['available_seats' => 15]);
        $schedule->refresh();

        // Second request with fewer seats
        $response2 = $this->pricingController->getRealtimePricing([
            'schedule_id' => $schedule->id,
            'current_seats' => 15,
        ]);

        $data2 = json_decode($response2->getContent(), true);
        $price2 = $data2['price'];

        // Price should increase when seats are limited
        $this->assertGreaterThanOrEqual($price2, $price1);
    }

    /** @test */
    public function it_provides_pricing_transparency_correctly()
    {
        $schedule = Schedule::factory()->create([
            'base_fare' => 750,
            'departure_time' => '2025-12-20 17:00:00', // Evening peak
            'available_seats' => 10,
            'total_seats' => 40,
        ]);

        $breakdown = $this->pricingService->getPricingBreakdown($schedule);

        $this->assertArrayHasKey('base_fare', $breakdown);
        $this->assertArrayHasKey('time_demand_multiplier', $breakdown);
        $this->assertArrayHasKey('seat_availability_multiplier', $breakdown);
        $this->assertArrayHasKey('day_of_week_multiplier', $breakdown);
        $this->assertArrayHasKey('ml_optimization', $breakdown);
        $this->assertArrayHasKey('final_price', $breakdown);

        // Verify transparency data
        $this->assertIsNumeric($breakdown['base_fare']);
        $this->assertIsNumeric($breakdown['time_demand_multiplier']);
        $this->assertIsNumeric($breakdown['seat_availability_multiplier']);
        $this->assertGreaterThan(0, $breakdown['final_price']);
    }

    /** @test */
    public function it_handles_alternative_time_suggestions()
    {
        $baseDateTime = '2025-12-20 17:00:00'; // Evening peak
        $suggestions = $this->pricingService->getAlternativeTimeSuggestions($baseDateTime, 'Manila', 'Baguio');

        $this->assertIsArray($suggestions);
        $this->assertGreaterThanOrEqual(1, count($suggestions));
        $this->assertLessThanOrEqual(3, count($suggestions)); // Max 3 suggestions

        foreach ($suggestions as $suggestion) {
            $this->assertArrayHasKey('departure_time', $suggestion);
            $this->assertArrayHasKey('estimated_price', $suggestion);
            $this->assertArrayHasKey('savings', $suggestion);
            $this->assertIsString($suggestion['departure_time']);
            $this->assertIsNumeric($suggestion['estimated_price']);
            $this->assertIsNumeric($suggestion['savings']);
        }
    }

    /** @test */
    public function it_validates_ml_api_integration()
    {
        // Test ML API endpoint
        $mlRequest = [
            'route_from' => 'Manila',
            'route_to' => 'Baguio',
            'departure_time' => '2025-12-20 14:00:00',
            'available_seats' => 25,
            'total_seats' => 40,
            'base_fare' => 750,
        ];

        $response = $this->postJson('/api/predictions/supply-demand', $mlRequest);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'predicted_price',
            'demand_level',
            'confidence_score',
            'factors',
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertIsNumeric($data['predicted_price']);
        $this->assertIsString($data['demand_level']);
        $this->assertIsNumeric($data['confidence_score']);
        $this->assertGreaterThanOrEqual(0, $data['confidence_score']);
        $this->assertLessThanOrEqual(1, $data['confidence_score']);
    }

    /** @test */
    public function it_performs_under_high_load()
    {
        // Create multiple schedules for stress testing
        Schedule::factory()->count(100)->create();

        $startTime = microtime(true);
        $requests = 50;

        for ($i = 0; $i < $requests; $i++) {
            $schedule = Schedule::inRandomOrder()->first();
            
            $response = $this->pricingController->calculatePrice(new \Illuminate\Http\Request([
                'route_from' => $schedule->route_from,
                'route_to' => $schedule->route_to,
                'depart_date' => '2025-12-20',
                'departure_time' => $schedule->departure_time,
                'available_seats' => $schedule->available_seats,
                'total_seats' => $schedule->total_seats,
            ]));

            $this->assertEquals(200, $response->getStatusCode());
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $averageTime = $totalTime / $requests;

        // Performance assertions
        $this->assertLessThan(1.0, $averageTime, 'Average response time should be less than 1 second');
        $this->assertLessThan(30.0, $totalTime, 'Total time for 50 requests should be less than 30 seconds');
    }

    /** @test */
    public function it_handles_edge_cases_gracefully()
    {
        // Test with no available seats
        $schedule = Schedule::factory()->create([
            'available_seats' => 0,
            'total_seats' => 40,
        ]);

        $response = $this->pricingController->calculatePrice(new \Illuminate\Http\Request([
            'route_from' => $schedule->route_from,
            'route_to' => $schedule->route_to,
            'depart_date' => '2025-12-20',
            'departure_time' => $schedule->departure_time,
            'available_seats' => 0,
            'total_seats' => 40,
        ]));

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertGreaterThan(0, $data['final_price']);

        // Test with very high demand
        $scheduleHighDemand = Schedule::factory()->create([
            'departure_time' => '2025-12-20 17:00:00', // Peak time
            'available_seats' => 1,
            'total_seats' => 40,
        ]);

        $breakdown = $this->pricingService->getPricingBreakdown($scheduleHighDemand);
        $this->assertGreaterThan($scheduleHighDemand->base_fare, $breakdown['final_price']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
