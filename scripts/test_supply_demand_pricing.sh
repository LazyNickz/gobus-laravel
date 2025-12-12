#!/bin/bash

# Supply and Demand Pricing Implementation Testing Script
# This script validates the complete implementation and performance

echo "=================================================="
echo "Supply & Demand Dynamic Pricing - Validation Test"
echo "=================================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    local status=$1
    local message=$2
    case $status in
        "SUCCESS")
            echo -e "${GREEN}✓ SUCCESS:${NC} $message"
            ;;
        "ERROR")
            echo -e "${RED}✗ ERROR:${NC} $message"
            ;;
        "INFO")
            echo -e "${BLUE}ℹ INFO:${NC} $message"
            ;;
        "WARNING")
            echo -e "${YELLOW}⚠ WARNING:${NC} $message"
            ;;
    esac
}

# Function to check if a service is running
check_service() {
    local service_name=$1
    local pid_file=$2
    
    if [ -f "$pid_file" ]; then
        local pid=$(cat "$pid_file")
        if kill -0 "$pid" 2>/dev/null; then
            print_status "SUCCESS" "$service_name is running (PID: $pid)"
            return 0
        else
            print_status "ERROR" "$service_name is not running (stale PID file)"
            return 1
        fi
    else
        print_status "ERROR" "$service_name PID file not found: $pid_file"
        return 1
    fi
}

# Function to run PHP unit tests
run_tests() {
    echo ""
    echo "=== Running Unit Tests ==="
    echo ""
    
    # Run the specific supply and demand pricing tests
    php artisan test tests/Feature/SupplyDemandPricingTest.php --verbose
    
    if [ $? -eq 0 ]; then
        print_status "SUCCESS" "All unit tests passed"
        return 0
    else
        print_status "ERROR" "Some unit tests failed"
        return 1
    fi
}

# Function to test API endpoints
test_api_endpoints() {
    echo ""
    echo "=== Testing API Endpoints ==="
    echo ""
    
    local base_url="http://localhost:8000"
    
    # Test dynamic pricing endpoint
    print_status "INFO" "Testing Dynamic Pricing API..."
    local pricing_response=$(curl -s -w "%{http_code}" -X POST "$base_url/api/dynamic-pricing/calculate" \
        -H "Content-Type: application/json" \
        -d '{
            "route_from": "Manila",
            "route_to": "Baguio",
            "depart_date": "2025-12-20",
            "departure_time": "08:00",
            "available_seats": 20,
            "total_seats": 40,
            "base_fare": 750
        }')
    
    local http_code="${pricing_response: -3}"
    local response_body="${pricing_response%???}"
    
    if [ "$http_code" = "200" ]; then
        print_status "SUCCESS" "Dynamic pricing API responded successfully"
        echo "Response: $response_body" | jq '.' 2>/dev/null || echo "$response_body"
    else
        print_status "ERROR" "Dynamic pricing API failed (HTTP: $http_code)"
    fi
    
    # Test ML API supply/demand prediction
    print_status "INFO" "Testing ML API Supply/Demand Prediction..."
    local ml_response=$(curl -s -w "%{http_code}" -X POST "$base_url/api/predictions/supply-demand" \
        -H "Content-Type: application/json" \
        -d '{
            "route_from": "Manila",
            "route_to": "Baguio",
            "departure_time": "2025-12-20 14:00:00",
            "available_seats": 25,
            "total_seats": 40,
            "base_fare": 750
        }')
    
    local ml_http_code="${ml_response: -3}"
    local ml_response_body="${ml_response%???}"
    
    if [ "$ml_http_code" = "200" ]; then
        print_status "SUCCESS" "ML API supply/demand prediction responded successfully"
        echo "Response: $ml_response_body" | jq '.' 2>/dev/null || echo "$ml_response_body"
    else
        print_status "ERROR" "ML API supply/demand prediction failed (HTTP: $ml_http_code)"
    fi
    
    # Test trip selection API
    print_status "INFO" "Testing Trip Selection API..."
    local trip_response=$(curl -s -w "%{http_code}" "$base_url/api/trip-selection/schedules?origin=Manila&destination=Baguio&date=2025-12-20&adults=1")
    
    local trip_http_code="${trip_response: -3}"
    local trip_response_body="${trip_response%???}"
    
    if [ "$trip_http_code" = "200" ]; then
        print_status "SUCCESS" "Trip selection API responded successfully"
        echo "Response: $trip_response_body" | jq '.' 2>/dev/null || echo "$trip_response_body"
    else
        print_status "ERROR" "Trip selection API failed (HTTP: $trip_http_code)"
    fi
}

# Function to test pricing calculations
test_pricing_calculations() {
    echo ""
    echo "=== Testing Pricing Calculations ==="
    echo ""
    
    # Create a temporary PHP script to test pricing calculations
    cat > /tmp/test_pricing.php << 'EOF'
<?php
require_once '/Applications/XAMPP/xamppfiles/htdocs/project-bus/vendor/autoload.php';

use App\Services\SupplyDemandService;
use Illuminate\Support\Facades\Log;

// Initialize Laravel
$app = require_once '/Applications/XAMPP/xamppfiles/htdocs/project-bus/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pricingService = new SupplyDemandService();

// Test cases
$testCases = [
    [
        'name' => 'Peak Hour (7 AM)',
        'hour' => 7,
        'available_seats' => 15,
        'total_seats' => 40,
        'day' => 5, // Friday
        'base_fare' => 750
    ],
    [
        'name' => 'Low Demand (2 AM)',
        'hour' => 2,
        'available_seats' => 35,
        'total_seats' => 40,
        'day' => 2, // Tuesday
        'base_fare' => 750
    ],
    [
        'name' => 'Evening Peak (5 PM)',
        'hour' => 17,
        'available_seats' => 5,
        'total_seats' => 40,
        'day' => 6, // Saturday
        'base_fare' => 750
    ],
    [
        'name' => 'Weekend (Sunday)',
        'hour' => 10,
        'available_seats' => 25,
        'total_seats' => 40,
        'day' => 7, // Sunday
        'base_fare' => 750
    ]
];

foreach ($testCases as $testCase) {
    echo "Testing: {$testCase['name']}\n";
    

    // Calculate components
    $timeMultiplier = $pricingService->calculateTimeMultiplier($testCase['hour'] . ':00:00');
    $dayMultiplier = $pricingService->calculateDayMultiplier('2025-12-' . str_pad($testCase['day'], 2, '0', STR_PAD_LEFT));
    $seatMultiplier = $pricingService->calculateSupplyMultiplier(
        $testCase['available_seats'], 
        $testCase['total_seats']
    );
    
    $finalPrice = $testCase['base_fare'] * $timeMultiplier * $dayMultiplier * $seatMultiplier;
    $demandLevel = $pricingService->classifyDemandLevel(
        ($timeMultiplier - 1) + ($dayMultiplier - 1) + (1 - $seatMultiplier)
    );
    
    echo "  Time Multiplier: " . number_format($timeMultiplier, 3) . "\n";
    echo "  Day Multiplier: " . number_format($dayMultiplier, 3) . "\n";
    echo "  Seat Multiplier: " . number_format($seatMultiplier, 3) . "\n";
    echo "  Final Price: ₱" . number_format($finalPrice, 0) . "\n";
    echo "  Demand Level: $demandLevel\n";
    echo "  Price Change: " . (($finalPrice / $testCase['base_fare'] - 1) * 100) . "%\n";
    echo "\n";
}
EOF

    php /tmp/test_pricing.php
    rm -f /tmp/test_pricing.php
}

# Function to check service dependencies
check_dependencies() {
    echo ""
    echo "=== Checking Service Dependencies ==="
    echo ""
    
    # Check if ML API is running
    print_status "INFO" "Checking ML API service..."
    check_service "ML API" "/Applications/XAMPP/xamppfiles/htdocs/project-bus/ml-api/ml_api.pid"
    
    # Check if web server is running
    print_status "INFO" "Checking web server..."
    check_service "Web Server" "/Applications/XAMPP/xamppfiles/htdocs/project-bus/storage/framework/sessions/.gitignore"
    
    # Check database connection
    print_status "INFO" "Checking database connection..."
    php artisan migrate:status > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        print_status "SUCCESS" "Database connection established"
    else
        print_status "ERROR" "Database connection failed"
    fi
}

# Function to validate frontend integration
validate_frontend() {
    echo ""
    echo "=== Validating Frontend Integration ==="
    echo ""
    
    # Check if trip selection page loads
    print_status "INFO" "Checking trip selection page accessibility..."
    
    # Check for pricing transparency elements in the Blade template
    if grep -q "pricing-transparency" /Applications/XAMPP/xamppfiles/htdocs/project-bus/resources/views/trip-selection.blade.php; then
        print_status "SUCCESS" "Pricing transparency elements found in frontend"
    else
        print_status "WARNING" "Pricing transparency elements not found in frontend"
    fi
    
    # Check for demand indicators
    if grep -q "demand-indicator" /Applications/XAMPP/xamppfiles/htdocs/project-bus/resources/views/trip-selection.blade.php; then
        print_status "SUCCESS" "Demand indicator elements found in frontend"
    else
        print_status "WARNING" "Demand indicator elements not found in frontend"
    fi
    
    # Check for alternative time suggestions
    if grep -q "alternative-time" /Applications/XAMPP/xamppfiles/htdocs/project-bus/resources/views/trip-selection.blade.php; then
        print_status "SUCCESS" "Alternative time suggestion elements found in frontend"
    else
        print_status "WARNING" "Alternative time suggestion elements not found in frontend"
    fi
}

# Function to generate performance report
generate_performance_report() {
    echo ""
    echo "=== Performance Report ==="
    echo ""
    
    # Create a performance test
    cat > /tmp/performance_test.php << 'EOF'
<?php
require_once '/Applications/XAMPP/xamppfiles/htdocs/project-bus/vendor/autoload.php';

$app = require_once '/Applications/XAMPP/xamppfiles/htdocs/project-bus/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\SupplyDemandService;

$pricingService = new SupplyDemandService();

echo "Performance Test Results:\n";
echo "========================\n\n";

$iterations = 1000;
$totalTime = 0;

for ($i = 0; $i < $iterations; $i++) {
    $startTime = microtime(true);
    

    // Simulate pricing calculation
    $timeMultiplier = $pricingService->calculateTimeMultiplier(rand(0, 23) . ':00:00');
    $dayMultiplier = $pricingService->calculateDayMultiplier('2025-12-' . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT));
    $seatMultiplier = $pricingService->calculateSupplyMultiplier(rand(1, 40), 40);
    $demandLevel = 'medium'; // Simplified for performance test
    
    $endTime = microtime(true);
    $totalTime += ($endTime - $startTime);
}

$averageTime = ($totalTime / $iterations) * 1000; // Convert to milliseconds

echo "Iterations: $iterations\n";
echo "Total Time: " . number_format($totalTime, 4) . " seconds\n";
echo "Average Time: " . number_format($averageTime, 4) . " ms\n";
echo "Throughput: " . number_format($iterations / $totalTime, 2) . " calculations/second\n";

if ($averageTime < 1.0) {
    echo "\n✓ Performance: EXCELLENT (Average < 1ms)\n";
} elseif ($averageTime < 5.0) {
    echo "\n✓ Performance: GOOD (Average < 5ms)\n";
} elseif ($averageTime < 10.0) {
    echo "\n⚠ Performance: ACCEPTABLE (Average < 10ms)\n";
} else {
    echo "\n✗ Performance: NEEDS IMPROVEMENT (Average > 10ms)\n";
}
EOF

    php /tmp/performance_test.php
    rm -f /tmp/performance_test.php
}

# Main execution
main() {
    echo "Starting Supply & Demand Pricing Implementation Validation..."
    echo ""
    
    # Check dependencies first
    check_dependencies
    
    # Run tests
    run_tests
    
    # Test API endpoints
    test_api_endpoints
    
    # Test pricing calculations
    test_pricing_calculations
    
    # Validate frontend integration
    validate_frontend
    
    # Generate performance report
    generate_performance_report
    
    echo ""
    echo "=================================================="
    echo "Validation Complete"
    echo "=================================================="
    echo ""
    echo "Next Steps:"
    echo "1. Review any failed tests and fix issues"
    echo "2. Monitor real-world performance metrics"
    echo "3. Collect user feedback on pricing transparency"
    echo "4. Fine-tune multipliers based on actual data"
    echo "5. Deploy to production environment"
    echo ""
}

# Run the main function
main "$@"
