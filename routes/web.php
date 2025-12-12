
<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;

use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\AdminScheduleController;

use App\Http\Controllers\DynamicPricingController;

use App\Http\Controllers\TripSelectionController;

use App\Http\Controllers\NewAdminScheduleController;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Schedule; // <-- added for debug route

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Clean consolidated routes — controller-based, no duplicates.
|
*/

// Root -> reservations
Route::get('/', fn() => redirect('/user-reservations'));

// Public login views
Route::view('/admin-login', 'admin-login')->name('admin.login');
Route::view('/user-login', 'user-login')->name('user.login');

// User web auth (form POST)
Route::post('/user-register', [AuthController::class, 'register'])->name('user.register');
Route::post('/user-login',    [AuthController::class, 'login'])->name('user.login.submit');
Route::get( '/user-logout',   [AuthController::class, 'logout'])->name('user.logout');



// Admin web auth (form POST) - bypass CSRF for admin login
Route::post('/admin-login', function(Request $request) {
    $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
    $admin = \DB::table('admins')->where('email', strtolower($data['email']))->first();
    if (!$admin || !\Hash::check($data['password'], $admin->password)) {
        return redirect()->back()->withErrors(['login' => 'Invalid admin credentials'])->withInput();
    }

    $request->session()->put('gobus_admin_logged', true);
    $request->session()->put('gobus_admin_email', $admin->email);
    $request->session()->put('gobus_admin_name', $admin->name ?? $admin->email);

    \Log::info('Admin logged in', ['email' => $admin->email, 'session_id' => $request->session()->getId()]);

    // Redirect directly to the admin schedules view route
    return redirect()->route('admin.schedules.index');


})->name('admin.login.submit');
Route::get( '/admin-logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// AJAX / API (CSRF protected via web middleware)
Route::post('/api/auth/login',    [AuthController::class, 'ajaxLogin'])->name('api.auth.login');
Route::post('/api/auth/register', [AuthController::class, 'ajaxRegister'])->name('api.auth.register');

// User profile (protected by gobus.auth middleware)
Route::get('/user/profile', [ProfileController::class, 'index'])
    ->middleware('gobus.auth')
    ->name('user.profile');

// User reservations view — pass session user if present
Route::get('/user/reservations', function(Request $request){
    $user = null;
    if ($request->session()->get('gobus_user_logged')) {
        $user = [
            'email' => $request->session()->get('gobus_user_email'),
            'name'  => $request->session()->get('gobus_user_name'),
        ];
    }
    return view('user-reservations', ['user' => $user]);
})->name('user.reservations');

// Reservations API / controller endpoints
Route::post('/reservations', [ReservationController::class,'store'])->name('reservations.store');
Route::get('/reservations',  [ReservationController::class,'index'])->name('reservations.index');



// Schedules API + stats
Route::post('/schedules', [ScheduleController::class,'store'])->name('schedules.store');
Route::get('/schedules/stats', [ScheduleController::class,'stats'])->name('schedules.stats');

// Schedules routes (list/search for users, create/store for admins)
Route::resource('schedules', ScheduleController::class)
    ->only(['index', 'create', 'store', 'show']);






// Admin area — direct controller check for admin session
Route::post('/admin/upload-schedules', [AdminScheduleController::class, 'upload'])->name('admin.upload.schedules');



// New Admin Schedule Routes (for clean database integration)

Route::prefix('admin/schedules')->name('admin.schedules.')->group(function() {
    Route::get('/', [NewAdminScheduleController::class, 'index'])->name('index');
    Route::post('/bulk', [NewAdminScheduleController::class, 'storeBulk'])->name('bulk')->withoutMiddleware([VerifyCsrfToken::class]);
    Route::get('/data', [NewAdminScheduleController::class, 'getSchedules'])->name('data');
    Route::put('/{id}', [NewAdminScheduleController::class, 'update'])->name('update')->withoutMiddleware([VerifyCsrfToken::class]);
    Route::delete('/{id}', [NewAdminScheduleController::class, 'destroy'])->name('destroy')->withoutMiddleware([VerifyCsrfToken::class]);
    Route::get('/stats', [NewAdminScheduleController::class, 'getStats'])->name('stats');
});


// Session check route for admin
Route::get('/admin/check-session', function() {
    return response()->json([
        'authenticated' => session()->has('gobus_admin_logged'),
        'email' => session()->get('gobus_admin_email'),
        'name' => session()->get('gobus_admin_name')
    ]);
})->withoutMiddleware([VerifyCsrfToken::class]);

Route::get('/admin/reservations', fn() => view('admin-reservations'))->name('admin.reservations');
// Legacy / convenience redirects (avoid direct blade file access)
Route::get('/user-reservations', fn() => redirect('/user/reservations'));
Route::get('/user-reservations.blade.php', fn() => redirect('/user/reservations'));
Route::get('/login', fn() => redirect('/user-login'));

Route::get('/admin-schedules.blade.php', fn() => redirect('/admin/schedules'));
Route::get('/admin-schedules.html', fn() => redirect('/admin/schedules'));
Route::get('/admin-reservations.blade.php', fn() => redirect('/admin/reservations'));
Route::get('/admin-reservations.html', fn() => redirect('/admin/reservations'));
Route::get('/admin-reservations.blade.php', fn() => redirect('/admin/reservations'));
Route::get('/admin-reservations.html', fn() => redirect('/admin/reservations'));

Route::get('/predict', function() {
    return view('predict');
});


Route::post('/predict', [App\Http\Controllers\PredictionController::class, 'predict']);









// Dynamic pricing endpoint for 7-day demand-based pricing
Route::get('/api/dynamic-pricing', [DynamicPricingController::class, 'getDynamicPricing'])->name('dynamic.pricing');

// Bulk demand prediction for multiple dates (for trip selection)
Route::get('/predict-bulk', function(Request $request) {
    $origin = $request->input('origin');
    $destination = $request->input('destination');
    $startDate = $request->input('start_date');
    $dates = $request->input('dates', []);
    
    if (!$origin || !$destination || !$startDate) {
        return response()->json([
            'success' => false,
            'error' => 'Missing required parameters: origin, destination, start_date'
        ], 400);
    }
    
    $client = new \GuzzleHttp\Client();
    $predictions = [];
    
    // Get base route info for distance calculation
    $baseSchedule = \App\Models\Schedule::where('route_from', $origin)
        ->where('route_to', $destination)
        ->first();
    
    $distance = $baseSchedule ? $baseSchedule->distance_km ?? 240 : 240; // Default distance
    $avgSpeed = $baseSchedule ? $baseSchedule->avg_speed ?? 60 : 60; // Default speed
    
    // If specific dates provided, use them; otherwise generate next 7 days
    if (empty($dates)) {
        $start = new DateTime($startDate);
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $start->format('Y-m-d');
            $start->modify('+1 day');
        }
    }
    
    foreach ($dates as $date) {
        $dateTime = new DateTime($date);
        $dayOfWeek = (int) $dateTime->format('w');
        $isWeekend = in_array($dayOfWeek, [0, 6]) ? 1 : 0;
        
        // Enhanced holiday check with more precise dates
        $isHoliday = 0;
        $holidayName = '';
        if (in_array($date, ['2025-12-25', '2026-12-25'])) {
            $isHoliday = 1;
            $holidayName = 'Christmas';
        } elseif (in_array($date, ['2026-01-01', '2027-01-01'])) {
            $isHoliday = 1;
            $holidayName = 'New Year';
        } elseif (in_array($date, ['2025-12-30', '2025-12-31'])) {
            $isHoliday = 1;
            $holidayName = 'Year End';
        }
        
        // Use typical departure time (e.g., 08:00)
        $departureTime = '08:00';
        
        // Calculate days to holidays with proper diff calculation
        $daysToChristmas = (new DateTime('2025-12-25'))->diff($dateTime)->days;
        $daysToNewYear = (new DateTime('2026-01-01'))->diff($dateTime)->days;
        
        $payload = [
            "distance_km" => $distance,
            "avg_speed" => $avgSpeed,
            "is_weekend" => $isWeekend,
            "is_holiday" => $isHoliday,
            "date" => $date,
            "time" => $departureTime,
            "days_to_christmas" => $daysToChristmas,
            "days_to_new_year" => $daysToNewYear,
            "route" => "{$origin}-{$destination}",
            "origin" => $origin,
            "destination" => $destination
        ];
        
        try {
            $response = $client->post("http://127.0.0.1:8001/predict", [
                "json" => $payload,
                "timeout" => 30,
                "connect_timeout" => 10
            ]);
            
            $result = json_decode($response->getBody(), true);
            $predictedDemand = $result['prediction'] ?? 0;
            
            // Enhanced business logic with more nuanced pricing tiers
            // Classify demand level based on ML prediction with more precise thresholds
            if ($predictedDemand > 60) {
                $demandLevel = 'high';
                $demandColor = 'red';
                $baseMultiplier = 1.25; // 25% premium for very high demand
            } elseif ($predictedDemand > 40) {
                $demandLevel = 'medium-high';
                $demandColor = 'orange';
                $baseMultiplier = 1.10; // 10% premium for medium-high demand
            } elseif ($predictedDemand > 20) {
                $demandLevel = 'medium';
                $demandColor = 'yellow';
                $baseMultiplier = 1.0; // Normal pricing for medium demand
            } elseif ($predictedDemand > 10) {
                $demandLevel = 'medium-low';
                $demandColor = 'lightblue';
                $baseMultiplier = 0.95; // 5% discount for medium-low demand
            } else {
                $demandLevel = 'low';
                $demandColor = 'green';
                $baseMultiplier = 0.85; // 15% discount for low demand
            }
            
            // Apply enhanced weekend/weekday logic
            $isWeekend = in_array($dayOfWeek, [0, 6]); // 0=Sunday, 6=Saturday
            $isFriday = ($dayOfWeek == 5); // Friday gets partial weekend pricing
            $isMonday = ($dayOfWeek == 1); // Monday gets slight weekday adjustment
            
            if ($isHoliday) {
                // Holiday pricing: High demand periods get premium, but we encourage travel
                if ($predictedDemand > 40) {
                    $priceMultiplier = $baseMultiplier * 1.15; // 15% holiday premium
                } else {
                    $priceMultiplier = $baseMultiplier * 0.95; // 5% holiday discount to encourage travel
                }
            } elseif ($isWeekend) {
                // Weekend pricing: Encourage weekend travel with discounts
                $weekendDiscount = 0.12; // 12% weekend discount
                $priceMultiplier = $baseMultiplier * (1 - $weekendDiscount);
            } elseif ($isFriday) {
                // Friday gets slight weekend-like pricing (partial weekend effect)
                $priceMultiplier = $baseMultiplier * 0.97; // 3% Friday discount
            } elseif ($isMonday) {
                // Monday gets slight adjustment (post-weekend effect)
                $priceMultiplier = $baseMultiplier * 1.02; // 2% Monday premium
            } else {
                // Regular weekday pricing
                $priceMultiplier = $baseMultiplier;
            }
            
            // Add slight random variation (±2%) to make prices more realistic
            $variation = 1 + (rand(-20, 20) / 1000); // ±2% variation
            $finalPriceMultiplier = $priceMultiplier * $variation;
            
            $predictions[$date] = [
                'predicted_demand' => round($predictedDemand, 2),
                'demand_level' => $demandLevel,
                'demand_color' => $demandColor,
                'price_multiplier' => round($finalPriceMultiplier, 3),
                'is_weekend' => $isWeekend,
                'is_holiday' => $isHoliday,
                'holiday_name' => $holidayName,
                'day_of_week' => $dateTime->format('D'),
                'day_name' => $dateTime->format('l'),
                'date_formatted' => $dateTime->format('M j, Y')
            ];
            
        } catch (\Exception $e) {
            // Enhanced fallback with more realistic demand simulation
            $isWeekend = in_array($dayOfWeek, [0, 6]);
            $isFriday = ($dayOfWeek == 5);
            $isMonday = ($dayOfWeek == 1);
            
            // Simulate more realistic demand patterns
            $baseDemand = 35; // Base demand level
            if ($isWeekend) {
                $predictedDemand = $baseDemand + rand(10, 25); // Higher weekend demand
            } elseif ($isFriday) {
                $predictedDemand = $baseDemand + rand(5, 15); // Friday premium
            } elseif ($isMonday) {
                $predictedDemand = $baseDemand - rand(5, 10); // Lower Monday demand
            } else {
                $predictedDemand = $baseDemand + rand(-10, 10); // Regular weekday variation
            }
            
            // Classify simulated demand
            if ($predictedDemand > 60) {
                $demandLevel = 'high';
                $demandColor = 'red';
                $baseMultiplier = 1.25;
            } elseif ($predictedDemand > 40) {
                $demandLevel = 'medium-high';
                $demandColor = 'orange';
                $baseMultiplier = 1.10;
            } elseif ($predictedDemand > 20) {
                $demandLevel = 'medium';
                $demandColor = 'yellow';
                $baseMultiplier = 1.0;
            } elseif ($predictedDemand > 10) {
                $demandLevel = 'medium-low';
                $demandColor = 'lightblue';
                $baseMultiplier = 0.95;
            } else {
                $demandLevel = 'low';
                $demandColor = 'green';
                $baseMultiplier = 0.85;
            }
            
            // Apply pricing logic
            if ($isWeekend) {
                $priceMultiplier = $baseMultiplier * 0.88; // 12% weekend discount
            } elseif ($isFriday) {
                $priceMultiplier = $baseMultiplier * 0.97; // 3% Friday discount
            } elseif ($isMonday) {
                $priceMultiplier = $baseMultiplier * 1.02; // 2% Monday premium
            } else {
                $priceMultiplier = $baseMultiplier;
            }
            
            $predictions[$date] = [
                'predicted_demand' => round($predictedDemand, 2),
                'demand_level' => $demandLevel,
                'demand_color' => $demandColor,
                'price_multiplier' => round($priceMultiplier, 3),
                'is_weekend' => $isWeekend,
                'is_holiday' => 0,
                'holiday_name' => '',
                'day_of_week' => $dateTime->format('D'),
                'day_name' => $dateTime->format('l'),
                'date_formatted' => $dateTime->format('M j, Y'),
                'note' => 'Simulated prediction (ML service unavailable)'
            ];
        }
    }
    
    return response()->json([
        'success' => true,
        'predictions' => $predictions,
        'total_dates' => count($dates),
        'origin' => $origin,
        'destination' => $destination,
        'start_date' => $startDate
    ]);

})->name('predict.bulk');

// Search entry (from user-reservations form) — preserve inputs and require login before showing the detailed page
Route::post('/search', function(Request $request) {
    // collect relevant search fields (no bustype)
    $data = $request->only(['origin','destination','depart_date']);
    $qs = http_build_query($data);

    if (!$request->session()->get('gobus_user_logged')) {
        $next = '/search-results?' . $qs;
        return redirect('/user-login?next=' . urlencode($next));
    }

    return redirect('/search-results?' . $qs);
})->name('search.submit');


// Detailed search page (requires login)
Route::get('/search-results', function(Request $request){
    if (!$request->session()->get('gobus_user_logged')) {
        $next = $request->getRequestUri();
        return redirect('/user-login?next=' . urlencode($next));
    }

    // gather inputs (origin, destination, depart_date) and pass to view
    $inputs = $request->only(['origin','destination','depart_date']);
    $user = null;
    if ($request->session()->get('gobus_user_logged')) {
        $user = [
            'email' => $request->session()->get('gobus_user_email'),
            'name'  => $request->session()->get('gobus_user_name'),
        ];
    }
    return view('search-results', array_merge($inputs, ['user' => $user]));
})->name('search.results');




// Trip selection page (accessible without login for demo)
Route::get('/trip-selection', function(Request $request){
    // gather inputs (origin, destination, depart_date, adults) and pass to view
    $inputs = $request->only(['origin','destination','depart_date','adults']);
    $user = null;
    if ($request->session()->get('gobus_user_logged')) {
        $user = [
            'email' => $request->session()->get('gobus_user_email'),
            'name'  => $request->session()->get('gobus_user_name'),
        ];
    }
    
    // Get schedules for the selected date and route
    $schedules = collect([]);
    if (!empty($inputs['origin']) && !empty($inputs['destination']) && !empty($inputs['depart_date'])) {
        $schedules = \App\Models\Schedule::search([
            'route_from' => $inputs['origin'],
            'route_to' => $inputs['destination'],
            'date' => $inputs['depart_date']
        ])->where('status', 'active')
          ->orderBy('departure_time')
          ->get();
    }
    
    // If no schedules found and we have search params, try broader search
    if ($schedules->isEmpty() && !empty($inputs['origin']) && !empty($inputs['destination'])) {
        $schedules = \App\Models\Schedule::where('route_from', 'like', '%' . $inputs['origin'] . '%')
            ->where('route_to', 'like', '%' . $inputs['destination'] . '%')
            ->where('status', 'active')
            ->orderBy('departure_time')
            ->limit(10) // Limit results
            ->get();
    }
    
    // If still no schedules, create demo data for Manila-Baguio route
    if ($schedules->isEmpty() && 
        (!empty($inputs['origin']) && !empty($inputs['destination'])) && 
        (strpos(strtolower($inputs['origin']), 'manila') !== false || strpos(strtolower($inputs['destination']), 'baguio') !== false)) {
        
        $demoSchedules = [
            [
                'id' => 999001,
                'route_from' => 'Manila',
                'route_to' => 'Baguio', 
                'departure_time' => '2025-12-20 08:00:00',
                'arrival_time' => '2025-12-20 13:30:00',
                'bus_number' => 'REG-001',
                'seats' => 40,
                'available_seats' => 32,
                'fare' => 850,
                'status' => 'active',
                'bus_type' => 'regular',
                'capacity' => 40,
            ],
            [
                'id' => 999002,
                'route_from' => 'Manila',
                'route_to' => 'Baguio',
                'departure_time' => '2025-12-20 10:00:00', 
                'arrival_time' => '2025-12-20 15:30:00',
                'bus_number' => 'DLX-101',
                'seats' => 25,
                'available_seats' => 18,
                'fare' => 1200,
                'status' => 'active',
                'bus_type' => 'deluxe',
                'capacity' => 25,
            ],
            [
                'id' => 999003,
                'route_from' => 'Manila',
                'route_to' => 'Baguio',
                'departure_time' => '2025-12-20 14:00:00',
                'arrival_time' => '2025-12-20 19:30:00', 
                'bus_number' => 'REG-045',
                'seats' => 40,
                'available_seats' => 40,
                'fare' => 750,
                'status' => 'active',
                'bus_type' => 'regular',
                'capacity' => 40,
            ],
            [
                'id' => 999004,
                'route_from' => 'Manila',
                'route_to' => 'Baguio',
                'departure_time' => '2025-12-20 16:00:00',
                'arrival_time' => '2025-12-20 21:30:00',
                'bus_number' => 'REG-089', 
                'seats' => 40,
                'available_seats' => 25,
                'fare' => 900,
                'status' => 'active',
                'bus_type' => 'regular',
                'capacity' => 40,
            ]
        ];
        
        // Convert to Eloquent collection
        $schedules = collect($demoSchedules)->map(function($data) {
            $schedule = new \App\Models\Schedule();
            foreach ($data as $key => $value) {
                $schedule->setAttribute($key, $value);
            }
            return $schedule;
        });
    }
    

    return view('trip-selection', array_merge($inputs, [
        'user' => $user, 
        'schedules' => $schedules,
        'inputs' => $inputs // Pass inputs to JavaScript
    ]));
})->name('trip.selection');

// AJAX endpoints for dynamic trip selection
Route::get('/api/trip-selection/dates', [TripSelectionController::class, 'getAvailableDates'])->name('trip.selection.dates');
Route::get('/api/trip-selection/schedules', [TripSelectionController::class, 'getSchedules'])->name('trip.selection.schedules');
Route::get('/api/trip-selection/stats', [TripSelectionController::class, 'getRouteStats'])->name('trip.selection.stats');


// DEV / DEBUG: lightweight GET endpoint to add a schedule (no CSRF). REMOVE in production.
Route::get('/debug/add-schedule', function(Request $request){
    $data = $request->only([
        'route_from','route_to','departure_time','arrival_time','bus_number',
        'seats','available_seats','fare','status'
    ]);
    // basic normalization
    $data['seats'] = (int) ($data['seats'] ?? 0);
    $data['available_seats'] = isset($data['available_seats']) ? (int)$data['available_seats'] : $data['seats'];
    $data['fare'] = (float) ($data['fare'] ?? 0);
    $data['status'] = $data['status'] ?? 'active';
    $data['created_by'] = null;

    $schedule = Schedule::create([
        'route_from' => $data['route_from'] ?? 'unknown',
        'route_to' => $data['route_to'] ?? 'unknown',
        'departure_time' => $data['departure_time'] ?? now(),
        'arrival_time' => $data['arrival_time'] ?? null,
        'bus_number' => $data['bus_number'] ?? null,
        'seats' => $data['seats'],
        'available_seats' => $data['available_seats'],
        'fare' => $data['fare'],
        'status' => $data['status'],
        'created_by' => $data['created_by']
    ]);

    return response()->json(['ok' => true, 'id' => $schedule->id]);
})->name('debug.add_schedule');


// DEBUG: Test bulk schedule creation directly (bypass all middleware)
Route::post('/debug/bulk-schedules', function(Request $request){
    try {
        $schedules = $request->all();
        
        if (!is_array($schedules) || empty($schedules)) {
            return response()->json(['success' => false, 'error' => 'No schedules provided'], 400);
        }

        $created = [];
        foreach ($schedules as $scheduleData) {
            $schedule = Schedule::create($scheduleData);
            $created[] = $schedule;
        }

        return response()->json([
            'success' => true,
            'message' => 'Schedules created successfully',
            'created' => count($created),
            'schedules' => $created
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ], 500);
    }
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);


// DEBUG: Simple GET test for schedule creation
Route::get('/debug/test-schedule', function(){
    try {
        $schedule = Schedule::create([
            'route_from' => 'Test Manila',
            'route_to' => 'Test Baguio',
            'departure_time' => now(),
            'arrival_time' => null,
            'bus_number' => 'TEST-GET-001',
            'seats' => 40,
            'available_seats' => 40,
            'fare' => 800,
            'status' => 'active',
            'bus_type' => 'regular',
            'trip_type' => 'single',
            'capacity' => 40,
            'created_by' => 'debug'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'schedule' => $schedule
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ], 500);
    }
});

// GET-based schedule generation (bypass CSRF by using GET)
Route::get('/admin/schedules/generate', function(Request $request){
    try {
        $routeFrom = $request->query('route_from');
        $routeTo = $request->query('route_to');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $times = $request->query('times', '08:00,12:00,16:00');
        $busType = $request->query('bus_type', 'regular');
        $price = (float) $request->query('price', 800);
        $capacity = (int) $request->query('capacity', 40);
        $tripType = $request->query('trip_type', 'single');
        $activeDays = $request->query('active_days', '0,1,2,3,4,5,6');

        if (!$routeFrom || !$routeTo || !$startDate) {
            return response()->json([
                'success' => false,
                'error' => 'Missing required parameters: route_from, route_to, start_date'
            ], 400);
        }

        $timeArray = explode(',', $times);
        $daysArray = array_map('intval', explode(',', $activeDays));

        $schedules = [];
        $start = new DateTime($startDate);
        $end = $endDate ? new DateTime($endDate) : $start;

        for ($date = clone $start; $date <= $end; $date->modify('+1 day')) {
            $dayOfWeek = (int) $date->format('w');
            
            // Check if this day is active
            if (!in_array($dayOfWeek, $daysArray)) {
                continue;
            }
            
            // Add schedules for each time
            foreach ($timeArray as $time) {
                $time = trim($time);
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
                    continue;
                }
                
                list($hours, $minutes) = explode(':', $time);
                $departureTime = clone $date;
                $departureTime->setTime((int)$hours, (int)$minutes, 0);
                
                $busNumber = sprintf('%s-%03d', 
                    $busType === 'deluxe' ? 'DLX' : 'REG', 
                    rand(1, 999)
                );
                
                $schedules[] = [
                    'route_from' => $routeFrom,
                    'route_to' => $routeTo,
                    'departure_time' => $departureTime->format('Y-m-d H:i:s'),
                    'arrival_time' => null,
                    'bus_number' => $busNumber,
                    'seats' => $capacity,
                    'available_seats' => $capacity,
                    'fare' => $price,
                    'status' => 'active',
                    'bus_type' => $busType,
                    'trip_type' => $tripType,
                    'capacity' => $capacity,
                    'created_by' => 'admin'
                ];
            }
        }
        
        if (empty($schedules)) {
            return response()->json([
                'success' => false,
                'error' => 'No schedules generated. Check your parameters.'
            ], 400);
        }
        
        // Create schedules in database
        $created = [];
        foreach ($schedules as $scheduleData) {
            $schedule = Schedule::create($scheduleData);
            $created[] = $schedule;
        }

        return response()->json([
            'success' => true,
            'message' => 'Schedules generated successfully',
            'created' => count($created),
            'schedules' => $created
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ], 500);
    }
});
