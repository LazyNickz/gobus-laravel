<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Select Trip — GoBus</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0066cc;
            --secondary-yellow: #ffd700;
            --light-blue: #e6f3ff;
            --dark-blue: #003d7a;
            --success-green: #28a745;
            --danger-red: #dc3545;
        }

        .booking-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }



        .progress-step {
            position: relative;
            flex: 1;
            text-align: center;
            padding: 1rem;
        }

        /* Only show connecting lines for completed steps */
        .progress-step.completed::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -50%;
            width: 100%;
            height: 2px;
            background: var(--secondary-yellow);
            z-index: 1;
        }

        .progress-step:last-child::after {
            display: none;
        }

        .progress-step.completed {
            background: rgba(255,255,255,0.1);
        }

        .progress-step.active {
            background: rgba(255,255,255,0.2);
        }

        /* Active and future steps don't show connecting lines */
        .progress-step.active::after,
        .progress-step:not(.completed):not(.active)::after {
            display: none;
        }

        .date-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
            cursor: pointer;
        }

        .date-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .date-card.selected {
            border-color: var(--primary-blue);
            background: var(--light-blue);
        }

        .date-card.unavailable {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .trip-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            background: white;
        }

        .trip-card:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 10px 25px rgba(0,102,204,0.1);
            transform: translateY(-2px);
        }

        .trip-card.selected {
            border-color: var(--primary-blue);
            background: var(--light-blue);
        }

        .filter-dropdown {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .filter-dropdown:focus-within {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0,102,204,0.1);
        }

        .notification-bubble {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }


        .bus-icon {
            color: var(--primary-blue);
            font-size: 1.5rem;
        }

        .price-tag {
            color: var(--success-green);
            font-weight: 700;
        }

        .unavailable-price {
            color: var(--danger-red);
            font-weight: 600;
        }

        .demand-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .demand-low {
            background-color: #dcfce7;
            color: #166534;
        }

        .demand-medium-low {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .demand-medium {
            background-color: #fef3c7;
            color: #92400e;
        }

        .demand-medium-high {
            background-color: #fed7aa;
            color: #9a3412;
        }

        .demand-high {
            background-color: #fecaca;
            color: #991b1b;
        }

        .pricing-transparency {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
        }

        .alternative-time {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 0.75rem;
            margin: 0.5rem 0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .alternative-time:hover {
            background: #e2e8f0;
            border-color: #94a3b8;
        }

        .alternative-time.savings {
            border-color: #22c55e;
            background: #f0fdf4;
        }

        .price-breakdown {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .price-breakdown .factor {
            color: #6b7280;
        }

        .price-breakdown .value {
            font-weight: 600;
        }

        .price-breakdown .increase {
            color: #dc2626;
        }

        .price-breakdown .decrease {
            color: #16a34a;
        }

        .booking-summary {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #f3f4f6;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,102,204,0.3);
        }

        .btn-secondary {
            background: white;
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            border-radius: 8px;
            transition: all 0.3s ease;
        }


        .btn-secondary:hover {
            background: var(--primary-blue);
            color: white;
        }

        .booking-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="booking-header">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-yellow-400 rounded-lg flex items-center justify-center">
                        <i class="fas fa-bus text-blue-900 text-xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold">GoBus</h1>
                </div>

                <!-- Navigation -->
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="/book" class="text-white hover:text-yellow-300 transition-colors font-medium">Book</a>
                    <a href="/manage" class="text-white hover:text-yellow-300 transition-colors font-medium">Manage</a>
                    <a href="/routes" class="text-white hover:text-yellow-300 transition-colors font-medium">Routes</a>
                    <a href="/help" class="text-white hover:text-yellow-300 transition-colors font-medium">Help</a>
                </nav>

                <!-- Login Button -->
                <div class="flex items-center space-x-4">
                    @if(isset($user) && $user)
                        <span class="text-white text-sm">Welcome, {{ $user['name'] ?? $user['email'] }}</span>
                        <a href="/user-logout" class="btn-secondary px-4 py-2 text-sm">Logout</a>
                    @else
                        <a href="/user-login" class="btn-secondary px-4 py-2 text-sm">Login</a>
                    @endif
                </div>
            </div>
        </div>
    </header>




    <!-- Booking Progress Indicator -->
    <div class="booking-header">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex">
                <div class="progress-step completed">
                    <i class="fas fa-check mb-2"></i>
                    <div class="text-sm font-medium">Search Results</div>
                </div>
                <div class="progress-step active">
                    <i class="fas fa-map-marker-alt mb-2"></i>
                    <div class="text-sm font-medium">Select Trip</div>
                </div>
                <div class="progress-step">
                    <i class="fas fa-user mb-2"></i>
                    <div class="text-sm font-medium">Passenger Details</div>
                </div>
                <div class="progress-step">
                    <i class="fas fa-plus mb-2"></i>
                    <div class="text-sm font-medium">Add-ons</div>
                </div>
                <div class="progress-step">
                    <i class="fas fa-credit-card mb-2"></i>
                    <div class="text-sm font-medium">Payment</div>
                </div>
                <div class="progress-step">
                    <i class="fas fa-check mb-2"></i>
                    <div class="text-sm font-medium">Confirmation</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Booking Summary Bar -->
        <div class="booking-summary p-6 mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 flex-1">
                    <div class="text-center lg:text-left">
                        <div class="text-sm text-gray-500">From</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $inputs['origin'] ?? 'Manila' }}</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-sm text-gray-500">To</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $inputs['destination'] ?? 'Baguio' }}</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-sm text-gray-500">Date</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $inputs['depart_date'] ?? 'Dec 20, 2025' }}</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-sm text-gray-500">Passengers</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $inputs['adults'] ?? '1' }} Adult</div>
                    </div>
                </div>
                <div class="lg:mt-0 mt-4 text-center lg:text-right">
                    <a href="{{ url('/user/reservations') }}" class="btn-secondary px-4 py-2 text-sm mr-4">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Search
                    </a>
                </div>
            </div>
        </div>

        <!-- Floating Notification -->
        <div class="notification-bubble fixed bottom-6 right-6 bg-blue-600 text-white px-4 py-3 rounded-full shadow-lg z-50">
            <div class="flex items-center space-x-2">
                <i class="fas fa-users"></i>
                <span class="text-sm font-medium">12 people booked this route today</span>
            </div>
        </div>


        <!-- Date Selector -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-semibold text-gray-900">Select Date</h3>
                <div class="flex items-center space-x-4">
                    <button id="prevWeekBtn" class="btn-secondary px-4 py-2 text-sm">
                        <i class="fas fa-chevron-left mr-2"></i>Previous Week
                    </button>
                    <button id="nextWeekBtn" class="btn-secondary px-4 py-2 text-sm">
                        Next Week<i class="fas fa-chevron-right ml-2"></i>
                    </button>
                </div>
            </div>
            <div id="datesContainer" class="flex space-x-4 overflow-x-auto pb-4">
                <!-- Dynamic date cards will be loaded here -->
                <div class="flex-shrink-0 w-32 h-24 border border-gray-300 rounded-lg p-4 text-center">
                    <div class="text-sm text-gray-500">Loading...</div>
                    <div class="text-lg font-bold text-gray-400">...</div>
                    <div class="text-xs text-gray-400 mt-1">Please wait</div>
                </div>
            </div>
        </div>


        <!-- Filters -->
        <div class="booking-card mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 lg:mb-0">Filter & Sort</h3>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 flex-1 lg:ml-8">
                    <!-- Time of Departure -->
                    <div class="filter-dropdown">
                        <select id="departureTimeFilter" class="w-full p-3 bg-transparent border-0 rounded-lg focus:ring-0">
                            <option value="">All Times</option>
                            <option value="morning">Morning (6AM - 12PM)</option>
                            <option value="afternoon">Afternoon (12PM - 6PM)</option>
                            <option value="evening">Evening (6PM - 12AM)</option>
                        </select>
                    </div>

                    <!-- Bus Class -->
                    <div class="filter-dropdown">
                        <select id="busClassFilter" class="w-full p-3 bg-transparent border-0 rounded-lg focus:ring-0">
                            <option value="">All Classes</option>
                            <option value="regular">Standard</option>
                            <option value="deluxe">Deluxe</option>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div class="filter-dropdown">
                        <select id="priceRangeFilter" class="w-full p-3 bg-transparent border-0 rounded-lg focus:ring-0">
                            <option value="">All Prices</option>
                            <option value="0-500">Under ₱500</option>
                            <option value="500-1000">₱500 - ₱1,000</option>
                            <option value="1000-1500">₱1,000 - ₱1,500</option>
                            <option value="1500+">Over ₱1,500</option>
                        </select>
                    </div>

                    <!-- Sort -->
                    <div class="filter-dropdown">
                        <select id="sortByFilter" class="w-full p-3 bg-transparent border-0 rounded-lg focus:ring-0">
                            <option value="lowest-price">Lowest Price</option>
                            <option value="earliest-trip">Earliest Trip</option>
                            <option value="fastest-route">Fastest Route</option>
                            <option value="most-seats">Most Seats</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Active Filters -->
            <div id="activeFilters" class="hidden mt-4 pt-4 border-t border-gray-200">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-gray-700">Active Filters:</span>
                    <div id="activeFiltersList"></div>
                    <button id="clearFiltersBtn" class="text-sm text-blue-600 hover:text-blue-800 ml-4">Clear All</button>
                </div>
            </div>
        </div>


        <!-- Trip Results -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-900">
                    <span id="resultsCount">{{ $schedules->count() }}</span> Available Trips
                </h3>
            </div>

            @if($schedules->count() > 0)
                <div id="tripsContainer" class="space-y-4">
                    @foreach($schedules as $schedule)
                        <div class="booking-card trip-card rounded-xl p-6 cursor-pointer" data-trip-id="{{ $schedule->id }}">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                <!-- Trip Info -->
                                <div class="flex items-center space-x-6 flex-1">
                                    <!-- Bus Icon -->
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-bus bus-icon"></i>
                                    </div>

                                    <!-- Time & Route Info -->
                                    <div class="flex-1">
                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                                            <!-- Departure -->
                                            <div class="text-center lg:text-left">
                                                <div class="text-2xl font-bold text-gray-900">
                                                    {{ \Carbon\Carbon::parse($schedule->departure_time)->format('H:i') }}
                                                </div>
                                                <div class="text-sm text-gray-600">{{ $schedule->route_from }}</div>
                                            </div>

                                            <!-- Duration & Route -->
                                            <div class="text-center">
                                                <div class="flex items-center justify-center space-x-2 mb-1">
                                                    <div class="text-sm text-gray-600">{{ $schedule->route_from }}</div>
                                                    <i class="fas fa-arrow-right text-gray-400"></i>
                                                    <div class="text-sm text-gray-600">{{ $schedule->route_to }}</div>
                                                </div>
                                                <div class="text-sm text-gray-500">~5h 30m</div>
                                                <div class="text-xs text-gray-400 mt-1">{{ $schedule->bus_type === 'deluxe' ? 'Deluxe' : 'Standard' }} Bus</div>
                                            </div>

                                            <!-- Arrival -->
                                            <div class="text-center lg:text-right">
                                                <div class="text-2xl font-bold text-gray-900">
                                                    {{ \Carbon\Carbon::parse($schedule->departure_time)->addHours(5)->addMinutes(30)->format('H:i') }}
                                                </div>
                                                <div class="text-sm text-gray-600">{{ $schedule->route_to }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                    <!-- Trip Details & Price -->
                    <div class="mt-6 lg:mt-0 lg:ml-8 lg:text-right">
                        <div class="flex items-center justify-between lg:justify-end space-x-8 mb-4">
                            <div class="text-center">
                                <div class="text-sm text-gray-600">Available Seats</div>
                                <div class="text-lg font-semibold text-green-600">{{ $schedule->available_seats }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm text-gray-600">Bus Number</div>
                                <div class="text-lg font-semibold text-gray-900">{{ $schedule->bus_number }}</div>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <div class="price-tag text-3xl">₱{{ number_format($schedule->fare, 0) }}</div>
                            <div class="text-xs text-gray-500 mt-1">ML-Optimized Price</div>
                            <div class="text-sm text-gray-600 mt-1">per person</div>
                            
                            <!-- Demand Level Indicator -->
                            <div class="mt-3 flex items-center justify-end space-x-2">
                                <div class="demand-indicator demand-medium">
                                    <i class="fas fa-chart-line mr-1"></i>
                                    Medium Demand
                                </div>
                                <button onclick="togglePricingTransparency({{ $schedule->id }})" 
                                        class="text-xs text-blue-600 hover:text-blue-800"
                                        title="View pricing breakdown">
                                    <i class="fas fa-info-circle mr-1"></i>Why this price?
                                </button>
                            </div>
                            
                            <!-- Pricing Transparency Panel (Hidden by default) -->
                            <div id="pricing-transparency-{{ $schedule->id }}" 
                                 class="pricing-transparency hidden mt-3 text-left">
                                <div class="text-sm font-semibold text-gray-900 mb-2">Pricing Breakdown</div>
                                <div class="price-breakdown">
                                    <span class="factor">Base fare</span>
                                    <span class="value">₱750</span>
                                </div>
                                <div class="price-breakdown">
                                    <span class="factor">Time demand</span>
                                    <span class="value increase">+₱50 (Peak hour)</span>
                                </div>
                                <div class="price-breakdown">
                                    <span class="factor">Seat availability</span>
                                    <span class="value increase">+₱25 (Limited seats)</span>
                                </div>
                                <div class="price-breakdown">
                                    <span class="factor">ML prediction</span>
                                    <span class="value">₱25 (Optimized)</span>
                                </div>
                                <div class="border-t border-gray-300 mt-2 pt-2">
                                    <div class="price-breakdown font-semibold">
                                        <span class="factor">Final price</span>
                                        <span class="value">₱{{ number_format($schedule->fare, 0) }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Alternative Times Suggestion -->
                            <div class="mt-3 text-left">
                                <div class="text-xs text-gray-600 mb-2">💡 Alternative times:</div>
                                <div class="space-y-1">
                                    <div class="alternative-time savings">
                                        <div class="text-xs">
                                            <div class="flex justify-between items-center">
                                                <span>09:00 - Save ₱100</span>
                                                <span class="text-green-600 font-semibold">₱750</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alternative-time">
                                        <div class="text-xs">
                                            <div class="flex justify-between items-center">
                                                <span>15:00 - Same price</span>
                                                <span class="font-semibold">₱825</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button onclick="selectTrip({{ $schedule->id }})" 
                                    class="btn-primary px-8 py-3 text-white font-semibold mt-4 w-full lg:w-auto"
                                    {{ $schedule->available_seats <= 0 ? 'disabled' : '' }}>
                                {{ $schedule->available_seats > 0 ? 'Select Trip' : 'Fully Booked' }}
                            </button>
                        </div>
                    </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            @else
                <div class="booking-card text-center py-16">
                    <i class="fas fa-bus text-gray-300 text-6xl mb-4"></i>
                    <div class="text-gray-500 text-xl mb-4">No trips found for the selected date.</div>
                    <button class="btn-primary px-6 py-3 text-white font-semibold">Search Different Date</button>
                </div>
            @endif
        </div>
    </div>



    <script>
        // Pass data to JavaScript
        const searchInputs = @json($inputs ?? []);
        let allTrips = [];
        let filteredTrips = [];
        let currentWeekOffset = 0;
        let selectedDate = null;
        

        // Extract user input date for date selection starting point
        const userInputDate = searchInputs.depart_date || new Date().toISOString().split('T')[0];
        const userDate = new Date(userInputDate);
        
        // Fix: Calculate week offset to center user date in the date picker
        function calculateWeekOffsetFromUserDate() {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const userDateCopy = new Date(userDate);
            userDateCopy.setHours(0, 0, 0, 0);
            
            // Calculate difference in days
            const diffTime = userDateCopy.getTime() - today.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            // Start from the user's date and show 7 days around it (center the date)
            // The JavaScript loads 7 days starting from getWeekStartDate(currentWeekOffset)
            // We want the user date to be in the middle of the 7-day range
            const weekOffset = Math.floor(diffDays / 7) * 7;
            return weekOffset;
        }
        

        // Initialize week offset based on user input date
        currentWeekOffset = calculateWeekOffsetFromUserDate();
        
        // Set selected date to user input date
        selectedDate = userInputDate;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeFilters();
            initializeDateNavigation();
            loadAvailableDates();
        });


        async function loadAvailableDates() {
            const container = document.getElementById('datesContainer');
            container.innerHTML = '<div class="flex-shrink-0 w-32 h-24 border border-gray-300 rounded-lg p-4 text-center"><div class="text-sm text-gray-500">Loading...</div><div class="text-lg font-bold text-gray-400">...</div><div class="text-xs text-gray-400 mt-1">Please wait</div></div>';

            try {
                const params = new URLSearchParams({
                    origin: searchInputs.origin || '',
                    destination: searchInputs.destination || '',
                    start_date: getWeekStartDate(currentWeekOffset),
                    days: 7
                });

                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

                const response = await fetch(`/api/trip-selection/dates?${params}`, {
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);

                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.dates && data.dates.length > 0) {
                        renderDateCards(data.dates);
                    } else {
                        console.warn('API returned no data, using fallback');
                        renderFallbackDates();
                    }
                } else {
                    console.warn('API response not ok, using fallback');
                    renderFallbackDates();
                }
            } catch (error) {
                console.error('Error loading dates:', error);
                console.log('Falling back to demo dates');
                renderFallbackDates();
            }
        }

        function renderFallbackDates() {
            const container = document.getElementById('datesContainer');
            container.innerHTML = '';
            
            // Generate fallback demo dates
            const baseDate = new Date(userInputDate);
            const fallbackDates = [];
            
            for (let i = 0; i < 7; i++) {
                const date = new Date(baseDate);
                date.setDate(date.getDate() + i);
                
                // Simulate availability (most dates have trips)
                const isAvailable = Math.random() > 0.15; // 85% chance of having trips
                const availableTrips = isAvailable ? Math.floor(Math.random() * 8) + 2 : 0;
                const baseFare = 800 + Math.floor(Math.random() * 400); // ₱800-1200
                

                fallbackDates.push({
                    date: date.toISOString().split('T')[0],
                    day_name: date.toLocaleDateString('en-US', { weekday: 'long' }),
                    day_short: date.toLocaleDateString('en-US', { weekday: 'short' }),
                    month_day: date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
                    available_trips: availableTrips,
                    min_fare: baseFare,
                    ml_optimized_fare: baseFare,
                    supply_demand_price: baseFare, // Added for consistency
                    original_fare: baseFare,
                    price_change: 0,
                    price_change_percent: 0,
                    is_available: isAvailable,
                    total_schedules: availableTrips,
                    has_ml_prediction: false,
                    has_supply_demand_pricing: false // Added for consistency
                });
            }
            
            renderDateCards(fallbackDates);
            
            // Show notification that we're using demo data
            showNotification('Using demo data - ML pricing unavailable', 'warning');
        }

        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existing = document.querySelector('.fallback-notification');
            if (existing) {
                existing.remove();
            }
            
            const notification = document.createElement('div');
            notification.className = `fallback-notification fixed top-20 right-4 z-50 px-4 py-3 rounded-lg shadow-lg max-w-sm ${
                type === 'warning' ? 'bg-yellow-100 text-yellow-800 border border-yellow-300' : 
                type === 'error' ? 'bg-red-100 text-red-800 border border-red-300' : 
                'bg-blue-100 text-blue-800 border border-blue-300'
            }`;
            notification.innerHTML = `
                <div class="flex items-center space-x-2">
                    <i class="fas fa-info-circle"></i>
                    <span class="text-sm font-medium">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-gray-500 hover:text-gray-700">×</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }


        function renderDateCards(dates) {
            const container = document.getElementById('datesContainer');
            container.innerHTML = '';

            dates.forEach((dateData) => {
                // Check if this date matches the user input date
                const isUserInputDate = dateData.date === userInputDate;
                const dateCard = createDateCard(dateData, isUserInputDate);
                container.appendChild(dateCard);
            });
        }




        function createDateCard(dateData, isUserInputDate = false) {
            const card = document.createElement('div');
            const isSelected = isUserInputDate || (selectedDate === dateData.date);
            const isSelectedClass = isSelected ? 'selected' : '';
            const isAvailableClass = dateData.is_available ? '' : 'unavailable';

            const dayLabel = getDayLabel(dateData.date, dateData);
            

            let priceDisplay = '';
            // Try both field names for compatibility
            const pricingField = dateData.ml_optimized_fare || dateData.supply_demand_price;
            const hasMLPrediction = dateData.has_ml_prediction || dateData.has_supply_demand_pricing;
            
            if (dateData.is_available && pricingField) {
                const hasPriceChange = dateData.price_change && Math.abs(dateData.price_change) > 0;
                const priceChangeClass = hasPriceChange ? (dateData.price_change > 0 ? 'text-red-600' : 'text-green-600') : 'text-gray-600';
                const priceChangeIcon = hasPriceChange ? (dateData.price_change > 0 ? '↑' : '↓') : '';
                const priceChangeText = hasPriceChange ? `<div class="text-xs ${priceChangeClass}">${priceChangeIcon}₱${Math.abs(dateData.price_change)}</div>` : '';
                
                priceDisplay = `
                    <div class="price-tag text-lg">
                        <div>₱${pricingField}</div>
                        ${priceChangeText}
                        <div class="text-xs text-gray-500 mt-1">
                            ${hasMLPrediction ? 'ML-Optimized' : 'Standard Price'}
                        </div>
                    </div>
                `;
            } else {
                priceDisplay = `<div class="unavailable-price text-lg">Not Available</div>`;
            }


            card.className = `date-card ${isSelectedClass} ${isAvailableClass} flex-shrink-0 w-32 h-24 border rounded-lg p-4 text-center cursor-pointer`;
            card.setAttribute('data-date', dateData.date);
            card.setAttribute('data-available-trips', dateData.available_trips);
            card.setAttribute('data-min-fare', pricingField || dateData.min_fare || '');
            
            if (dateData.is_available) {
                card.style.cursor = 'pointer';
                card.onclick = () => selectDate(card);
            } else {
                card.style.cursor = 'not-allowed';
            }

            card.innerHTML = `
                <div class="text-sm text-gray-600">${dayLabel}</div>
                <div class="text-lg font-bold text-gray-900">${dateData.month_day}</div>
                ${priceDisplay}
                ${dateData.available_trips > 0 ? `<div class="text-xs text-gray-500 mt-1">${dateData.available_trips} trips</div>` : ''}
            `;

            // Select user input date by default, or the first available date
            if ((isUserInputDate || (!selectedDate && dateData.is_available))) {
                selectedDate = dateData.date;
                if (dateData.is_available) {
                    // Only load trips if this is the user input date or first available date
                    loadTripsForDate(dateData.date);
                }
            }

            return card;
        }


        function getDayLabel(dateStr, dateData = null) {
            const today = new Date().toDateString();
            const targetDate = new Date(dateStr).toDateString();
            
            if (today === targetDate) {
                return 'Today';
            }
            
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            if (tomorrow.toDateString() === targetDate) {
                return 'Tomorrow';
            }
            
            return dateData?.day_short || new Date(dateStr).toLocaleDateString('en-US', { weekday: 'short' });
        }

        async function selectDate(dateCard) {
            // Remove selected class from all cards
            document.querySelectorAll('.date-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            dateCard.classList.add('selected');
            
            // Update selected date and load trips
            selectedDate = dateCard.getAttribute('data-date');
            loadTripsForDate(selectedDate);
        }


        async function loadTripsForDate(date) {
            const container = document.getElementById('tripsContainer');
            const resultsCount = document.getElementById('resultsCount');
            
            // Show loading state
            container.innerHTML = '<div class="booking-card text-center py-16"><i class="fas fa-spinner fa-spin text-gray-300 text-4xl mb-4"></i><div class="text-gray-500 text-xl">Loading trips...</div></div>';
            
            try {
                const params = new URLSearchParams({
                    origin: searchInputs.origin || '',
                    destination: searchInputs.destination || '',
                    date: date,
                    adults: searchInputs.adults || '1'
                });

                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

                const response = await fetch(`/api/trip-selection/schedules?${params}`, {
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);

                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.schedules && data.schedules.length > 0) {
                        renderTrips(data.schedules);
                        resultsCount.textContent = data.schedules.length;
                    } else {
                        console.warn('No trips found for date, using fallback');
                        renderFallbackTrips(date);
                    }
                } else {
                    console.warn('API response not ok for trips, using fallback');
                    renderFallbackTrips(date);
                }
            } catch (error) {
                console.error('Error loading trips:', error);
                console.log('Falling back to demo trips');
                renderFallbackTrips(date);
            }
        }

        function renderFallbackTrips(date) {
            const container = document.getElementById('tripsContainer');
            const resultsCount = document.getElementById('resultsCount');
            
            // Generate fallback demo trips
            const demoTrips = [
                {
                    id: 999001,
                    route_from: searchInputs.origin || 'Manila',
                    route_to: searchInputs.destination || 'Baguio',
                    departure_time: `${date} 08:00:00`,
                    arrival_time: `${date} 13:30:00`,
                    bus_number: 'REG-001',
                    bus_type: 'regular',
                    seats: 40,
                    available_seats: 32,
                    fare: 850,
                    ml_optimized_price: 850,
                    original_price: 850,
                    price_change: 0,
                    price_change_percent: 0,
                    demand_level: 'medium',
                    is_booking_available: true
                },
                {
                    id: 999002,
                    route_from: searchInputs.origin || 'Manila',
                    route_to: searchInputs.destination || 'Baguio',
                    departure_time: `${date} 10:00:00`,
                    arrival_time: `${date} 15:30:00`,
                    bus_number: 'DLX-101',
                    bus_type: 'deluxe',
                    seats: 25,
                    available_seats: 18,
                    fare: 1200,
                    ml_optimized_price: 1200,
                    original_price: 1200,
                    price_change: 0,
                    price_change_percent: 0,
                    demand_level: 'medium-high',
                    is_booking_available: true
                },
                {
                    id: 999003,
                    route_from: searchInputs.origin || 'Manila',
                    route_to: searchInputs.destination || 'Baguio',
                    departure_time: `${date} 14:00:00`,
                    arrival_time: `${date} 19:30:00`,
                    bus_number: 'REG-045',
                    bus_type: 'regular',
                    seats: 40,
                    available_seats: 40,
                    fare: 750,
                    ml_optimized_price: 750,
                    original_price: 750,
                    price_change: 0,
                    price_change_percent: 0,
                    demand_level: 'low',
                    is_booking_available: true
                },
                {
                    id: 999004,
                    route_from: searchInputs.origin || 'Manila',
                    route_to: searchInputs.destination || 'Baguio',
                    departure_time: `${date} 16:00:00`,
                    arrival_time: `${date} 21:30:00`,
                    bus_number: 'REG-089',
                    bus_type: 'regular',
                    seats: 40,
                    available_seats: 25,
                    fare: 900,
                    ml_optimized_price: 900,
                    original_price: 900,
                    price_change: 0,
                    price_change_percent: 0,
                    demand_level: 'medium-low',
                    is_booking_available: true
                }
            ];
            
            renderTrips(demoTrips);
            resultsCount.textContent = demoTrips.length;
            
            // Show notification that we're using demo data
            showNotification('Using demo trips - ML pricing unavailable', 'warning');
        }

        function renderTrips(schedules) {
            const container = document.getElementById('tripsContainer');
            container.innerHTML = '';

            schedules.forEach(schedule => {
                const tripCard = createTripCard(schedule);
                container.appendChild(tripCard);
            });

            // Reinitialize filters after rendering new trips
            initializeFilters();
        }



        function createTripCard(schedule) {
            const card = document.createElement('div');
            card.className = 'booking-card trip-card rounded-xl p-6 cursor-pointer';
            card.setAttribute('data-trip-id', schedule.id || '');

            // Safely handle price change calculations
            const priceChange = schedule.price_change || 0;
            const priceChangePercent = schedule.price_change_percent || 0;
            const priceChangeText = priceChange !== 0 
                ? `<div class="text-xs ${priceChange > 0 ? 'text-red-600' : 'text-green-600'}">
                    ${priceChange > 0 ? '+' : ''}₱${Math.abs(priceChange)} (${Math.abs(priceChangePercent).toFixed(1)}%)
                   </div>`
                : '';

            // Safe demand level class mapping
            const demandLevel = schedule.demand_level || 'medium';
            const demandLevelClass = {
                'low': 'demand-low',
                'medium-low': 'demand-medium-low', 
                'medium': 'demand-medium',
                'medium-high': 'demand-medium-high',
                'high': 'demand-high'
            };

            const demandLabel = {
                'low': 'Low Demand',
                'medium-low': 'Medium-Low Demand',
                'medium': 'Medium Demand',
                'medium-high': 'Medium-High Demand',
                'high': 'High Demand'
            };

            // Generate pricing breakdown
            const baseFare = Math.floor(schedule.ml_optimized_price * 0.85);
            const timeDemand = Math.floor(Math.random() * 50) + 25; // Random time-based premium
            const seatAvailability = Math.floor(Math.random() * 30) + 10; // Random seat-based premium
            const mlOptimization = Math.floor(Math.random() * 40) + 15; // Random ML optimization

            // Generate alternative times
            const currentHour = new Date(schedule.departure_time).getHours();
            const alternatives = [];
            
            // Generate 2 alternative times
            for (let i = 0; i < 2; i++) {
                const offset = Math.random() > 0.5 ? 1 : -1;
                const altHour = currentHour + (offset * (2 + Math.floor(Math.random() * 3)));
                const altPrice = schedule.ml_optimized_price + (Math.random() > 0.5 ? -100 : Math.random() * 50);
                const savings = schedule.ml_optimized_price - altPrice;
                
                if (altHour >= 6 && altHour <= 22) {
                    alternatives.push({
                        time: `${altHour.toString().padStart(2, '0')}:00`,
                        price: Math.max(altPrice, 650),
                        savings: Math.max(savings, 0),
                        hasSavings: savings > 50
                    });
                }
            }

            card.innerHTML = `
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <!-- Trip Info -->
                    <div class="flex items-center space-x-6 flex-1">
                        <!-- Bus Icon -->
                        <div class="flex-shrink-0">
                            <i class="fas fa-bus bus-icon"></i>
                        </div>

                        <!-- Time & Route Info -->
                        <div class="flex-1">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                                <!-- Departure -->
                                <div class="text-center lg:text-left">
                                    <div class="text-2xl font-bold text-gray-900">
                                        ${formatTime(schedule.departure_time)}
                                    </div>
                                    <div class="text-sm text-gray-600">${schedule.route_from}</div>
                                </div>

                                <!-- Duration & Route -->
                                <div class="text-center">
                                    <div class="flex items-center justify-center space-x-2 mb-1">
                                        <div class="text-sm text-gray-600">${schedule.route_from}</div>
                                        <i class="fas fa-arrow-right text-gray-400"></i>
                                        <div class="text-sm text-gray-600">${schedule.route_to}</div>
                                    </div>
                                    <div class="text-sm text-gray-500">~5h 30m</div>
                                    <div class="text-xs text-gray-400 mt-1">${schedule.bus_type === 'deluxe' ? 'Deluxe' : 'Standard'} Bus</div>
                                </div>

                                <!-- Arrival -->
                                <div class="text-center lg:text-right">
                                    <div class="text-2xl font-bold text-gray-900">
                                        ${formatTime(schedule.arrival_time || addHours(schedule.departure_time, 5, 30))}
                                    </div>
                                    <div class="text-sm text-gray-600">${schedule.route_to}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trip Details & Price -->
                    <div class="mt-6 lg:mt-0 lg:ml-8 lg:text-right">
                        <div class="flex items-center justify-between lg:justify-end space-x-8 mb-4">
                            <div class="text-center">
                                <div class="text-sm text-gray-600">Available Seats</div>
                                <div class="text-lg font-semibold text-green-600">${schedule.available_seats}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm text-gray-600">Bus Number</div>
                                <div class="text-lg font-semibold text-gray-900">${schedule.bus_number}</div>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <div class="price-tag text-3xl">₱${numberFormat(schedule.ml_optimized_price)}</div>
                            <div class="text-xs text-gray-500 mt-1">ML-Optimized Price</div>
                            ${priceChangeText}
                            
                            <!-- Demand Level Indicator -->
                            <div class="mt-3 flex items-center justify-end space-x-2">
                                <div class="demand-indicator ${demandLevelClass[demandLevel]}">
                                    <i class="fas fa-chart-line mr-1"></i>
                                    ${demandLabel[demandLevel]}
                                </div>
                                <button onclick="togglePricingTransparency(${schedule.id})" 
                                        class="text-xs text-blue-600 hover:text-blue-800"
                                        title="View pricing breakdown">
                                    <i class="fas fa-info-circle mr-1"></i>Why this price?
                                </button>
                            </div>
                            
                            <!-- Pricing Transparency Panel (Hidden by default) -->
                            <div id="pricing-transparency-${schedule.id}" 
                                 class="pricing-transparency hidden mt-3 text-left">
                                <div class="text-sm font-semibold text-gray-900 mb-2">Pricing Breakdown</div>
                                <div class="price-breakdown">
                                    <span class="factor">Base fare</span>
                                    <span class="value">₱${baseFare}</span>
                                </div>
                                <div class="price-breakdown">
                                    <span class="factor">Time demand</span>
                                    <span class="value increase">+₱${timeDemand} (Peak hour)</span>
                                </div>
                                <div class="price-breakdown">
                                    <span class="factor">Seat availability</span>
                                    <span class="value increase">+₱${seatAvailability} (Limited seats)</span>
                                </div>
                                <div class="price-breakdown">
                                    <span class="factor">ML prediction</span>
                                    <span class="value">₱${mlOptimization} (Optimized)</span>
                                </div>
                                <div class="border-t border-gray-300 mt-2 pt-2">
                                    <div class="price-breakdown font-semibold">
                                        <span class="factor">Final price</span>
                                        <span class="value">₱${numberFormat(schedule.ml_optimized_price)}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Alternative Times Suggestion -->
                            ${alternatives.length > 0 ? `
                            <div class="mt-3 text-left">
                                <div class="text-xs text-gray-600 mb-2">💡 Alternative times:</div>
                                <div class="space-y-1">
                                    ${alternatives.map(alt => `
                                        <div class="alternative-time ${alt.hasSavings ? 'savings' : ''}">
                                            <div class="text-xs">
                                                <div class="flex justify-between items-center">
                                                    <span>${alt.time} - ${alt.hasSavings ? 'Save ₱' + Math.round(alt.savings) : 'Same price'}</span>
                                                    <span class="${alt.hasSavings ? 'text-green-600' : ''} font-semibold">₱${numberFormat(alt.price)}</span>
                                                </div>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                            ` : ''}
                            
                            <button onclick="selectTrip(${schedule.id})" 
                                    class="btn-primary px-8 py-3 text-white font-semibold mt-4 w-full lg:w-auto"
                                    ${schedule.available_seats <= 0 ? 'disabled' : ''}>
                                ${schedule.available_seats > 0 ? 'Select Trip' : 'Fully Booked'}
                            </button>
                        </div>
                    </div>
                </div>
            `;

            return card;
        }

        function showNoTrips() {
            const container = document.getElementById('tripsContainer');
            const resultsCount = document.getElementById('resultsCount');
            
            container.innerHTML = `
                <div class="booking-card text-center py-16">
                    <i class="fas fa-bus text-gray-300 text-6xl mb-4"></i>
                    <div class="text-gray-500 text-xl mb-4">No trips found for the selected date.</div>
                    <button class="btn-primary px-6 py-3 text-white font-semibold" onclick="loadAvailableDates()">Try Different Date</button>
                </div>
            `;
            
            resultsCount.textContent = '0';
        }

        function showError(message) {
            const container = document.getElementById('datesContainer');
            container.innerHTML = `
                <div class="flex-shrink-0 w-full border border-red-300 rounded-lg p-4 text-center bg-red-50">
                    <div class="text-sm text-red-600">${message}</div>
                    <button class="btn-primary px-4 py-2 text-white text-sm mt-2" onclick="loadAvailableDates()">Retry</button>
                </div>
            `;
        }

        function initializeDateNavigation() {
            const prevBtn = document.getElementById('prevWeekBtn');
            const nextBtn = document.getElementById('nextWeekBtn');
            
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    currentWeekOffset -= 7;
                    loadAvailableDates();
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    currentWeekOffset += 7;
                    loadAvailableDates();
                });
            }
        }



        function getWeekStartDate(offset = 0) {
            // Fix: Start from user input date and adjust to show user date in the range
            const baseDate = new Date(userInputDate);
            baseDate.setDate(baseDate.getDate() + offset);
            
            // Don't adjust to Monday - just show the exact 7 days starting from offset
            // This ensures the user date appears in the displayed range
            return baseDate.toISOString().split('T')[0];
        }

        function formatTime(dateTime) {
            return new Date(dateTime).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
        }

        function addHours(dateTime, hours, minutes = 0) {
            const date = new Date(dateTime);
            date.setHours(date.getHours() + hours);
            date.setMinutes(date.getMinutes() + minutes);
            return date.toISOString();
        }

        function numberFormat(num) {
            return new Intl.NumberFormat('en-PH').format(num);
        }

        // Legacy functions for backward compatibility
        function initializeFilters() {
            const filterIds = ['departureTimeFilter', 'busClassFilter', 'priceRangeFilter', 'sortByFilter'];
            
            filterIds.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('change', applyFilters);
                }
            });

            const clearBtn = document.getElementById('clearFiltersBtn');
            if (clearBtn) {
                clearBtn.addEventListener('click', clearAllFilters);
            }
        }

        function applyFilters() {
            const filters = getCurrentFilters();
            let hasActiveFilters = Object.values(filters).some(value => value !== '' && value !== null && value !== undefined);

            // Get all trip cards and filter them
            const allTrips = Array.from(document.querySelectorAll('.trip-card'));
            const filteredTrips = allTrips.filter(trip => {
                return tripMatchesFilters(trip, filters);
            });

            // Sort trips
            sortTrips(filteredTrips, filters.sortBy);

            // Update display
            updateTripDisplay(filteredTrips);

            // Update active filters
            updateActiveFilters(filters, hasActiveFilters);
        }

        function getCurrentFilters() {
            return {
                departureTime: document.getElementById('departureTimeFilter')?.value || '',
                busClass: document.getElementById('busClassFilter')?.value || '',
                priceRange: document.getElementById('priceRangeFilter')?.value || '',
                sortBy: document.getElementById('sortByFilter')?.value || 'lowest-price'
            };
        }

        function tripMatchesFilters(trip, filters) {
            const departureTime = getTripDepartureTime(trip);
            const busClass = getTripBusClass(trip);
            const price = getTripPrice(trip);

            // Check departure time filter
            if (filters.departureTime && !timeMatchesFilter(departureTime, filters.departureTime)) {
                return false;
            }

            // Check bus class filter
            if (filters.busClass && !busClass.toLowerCase().includes(filters.busClass.toLowerCase())) {
                return false;
            }

            // Check price range filter
            if (filters.priceRange && !priceMatchesFilter(price, filters.priceRange)) {
                return false;
            }

            return true;
        }

        function getTripDepartureTime(trip) {
            const timeElement = trip.querySelector('.text-2xl.font-bold.text-gray-900');
            return timeElement ? timeElement.textContent.trim() : '';
        }

        function getTripBusClass(trip) {
            const classElement = trip.querySelector('.text-xs.text-gray-400');
            return classElement ? classElement.textContent.trim() : 'Standard';
        }

        function getTripPrice(trip) {
            const priceElement = trip.querySelector('.price-tag.text-3xl');
            if (priceElement) {
                const priceText = priceElement.textContent.replace(/[^\d]/g, '');
                return parseFloat(priceText) || 0;
            }
            return 0;
        }

        function timeMatchesFilter(time, filter) {
            if (!time) return false;
            
            const hour = parseInt(time.split(':')[0]);
            
            switch (filter) {
                case 'morning':
                    return hour >= 6 && hour < 12;
                case 'afternoon':
                    return hour >= 12 && hour < 18;
                case 'evening':
                    return hour >= 18 && hour < 24;
                default:
                    return true;
            }
        }

        function priceMatchesFilter(price, filter) {
            switch (filter) {
                case '0-500':
                    return price < 500;
                case '500-1000':
                    return price >= 500 && price <= 1000;
                case '1000-1500':
                    return price > 1000 && price <= 1500;
                case '1500+':
                    return price > 1500;
                default:
                    return true;
            }
        }

        function sortTrips(trips, sortBy) {
            trips.sort((a, b) => {
                switch (sortBy) {
                    case 'lowest-price':
                        return getTripPrice(a) - getTripPrice(b);
                    case 'earliest-trip':
                        return getTripDepartureTime(a).localeCompare(getTripDepartureTime(b));
                    case 'fastest-route':
                        return getTripDepartureTime(a).localeCompare(getTripDepartureTime(b));
                    case 'most-seats':
                        const seatsA = parseInt(a.querySelector('.text-lg.font-semibold.text-green-600')?.textContent || '0');
                        const seatsB = parseInt(b.querySelector('.text-lg.font-semibold.text-green-600')?.textContent || '0');
                        return seatsB - seatsA;
                    default:
                        return 0;
                }
            });
        }

        function updateTripDisplay(trips) {
            // Hide all trips
            document.querySelectorAll('.trip-card').forEach(trip => {
                trip.style.display = 'none';
            });

            // Show filtered trips
            trips.forEach(trip => {
                trip.style.display = 'block';
            });

            // Update results count
            const resultsCount = document.getElementById('resultsCount');
            if (resultsCount) {
                resultsCount.textContent = trips.length;
            }
        }

        function updateActiveFilters(filters, hasActiveFilters) {
            const activeFiltersContainer = document.getElementById('activeFilters');
            const activeFiltersList = document.getElementById('activeFiltersList');
            
            if (!activeFiltersContainer || !activeFiltersList) return;

            if (!hasActiveFilters) {
                activeFiltersContainer.classList.add('hidden');
                return;
            }

            activeFiltersContainer.classList.remove('hidden');
            activeFiltersList.innerHTML = '';

            // Add filter chips
            if (filters.departureTime) {
                addFilterChip(activeFiltersList, `Time: ${getFilterLabel('departureTime', filters.departureTime)}`);
            }
            if (filters.busClass) {
                addFilterChip(activeFiltersList, `Class: ${getFilterLabel('busClass', filters.busClass)}`);
            }
            if (filters.priceRange) {
                addFilterChip(activeFiltersList, `Price: ₱${filters.priceRange.replace('-', ' - ₱')}`);
            }
            if (filters.sortBy !== 'lowest-price') {
                addFilterChip(activeFiltersList, `Sort: ${getFilterLabel('sortBy', filters.sortBy)}`);
            }
        }

        function addFilterChip(container, text) {
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800';
            chip.innerHTML = `${text} <button class="ml-2 text-blue-600 hover:text-blue-800" onclick="removeFilter()">×</button>`;
            container.appendChild(chip);
        }

        function getFilterLabel(type, value) {
            const labels = {
                departureTime: {
                    'morning': 'Morning',
                    'afternoon': 'Afternoon',
                    'evening': 'Evening'
                },
                busClass: {
                    'regular': 'Standard',
                    'deluxe': 'Deluxe'
                },
                sortBy: {
                    'lowest-price': 'Lowest Price',
                    'earliest-trip': 'Earliest Trip',
                    'fastest-route': 'Fastest Route',
                    'most-seats': 'Most Seats'
                }
            };
            
            return labels[type]?.[value] || value;
        }

        function removeFilter() {
            // Reset all filters to default
            const filters = ['departureTimeFilter', 'busClassFilter', 'priceRangeFilter'];
            filters.forEach(id => {
                const element = document.getElementById(id);
                if (element) element.value = '';
            });

            const sortElement = document.getElementById('sortByFilter');
            if (sortElement) sortElement.value = 'lowest-price';

            applyFilters();
        }

        function clearAllFilters() {
            removeFilter();
        }


        function togglePricingTransparency(scheduleId) {
            const panel = document.getElementById(`pricing-transparency-${scheduleId}`);
            if (panel) {
                panel.classList.toggle('hidden');
                
                // Update button state
                const button = panel.previousElementSibling.querySelector('button');
                if (button) {
                    const icon = button.querySelector('i');
                    if (panel.classList.contains('hidden')) {
                        button.innerHTML = '<i class="fas fa-info-circle mr-1"></i>Why this price?';
                    } else {
                        button.innerHTML = '<i class="fas fa-times mr-1"></i>Hide details';
                    }
                }
            }
        }

        function selectAlternativeTime(altTime, altPrice, currentTripId) {
            // Visual feedback for alternative selection
            const alternatives = document.querySelectorAll('.alternative-time');
            alternatives.forEach(alt => alt.classList.remove('selected'));
            
            const selectedAlt = event.currentTarget;
            selectedAlt.classList.add('selected');
            
            // Update price display
            const priceElement = selectedAlt.closest('.text-right').querySelector('.price-tag');
            if (priceElement) {
                priceElement.innerHTML = `₱${numberFormat(altPrice)}`;
            }
            
            // Show selection feedback
            showNotification(`Alternative time selected: ${altTime} - ₱${numberFormat(altPrice)}`, 'success');
        }

        function updatePriceTrends(scheduleId, priceHistory) {
            // Create or update price trend visualization
            const trendsContainer = document.createElement('div');
            trendsContainer.className = 'price-trends mt-2 text-xs text-gray-600';
            trendsContainer.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>📈 Price trend (7 days)</span>
                    <div class="flex items-center space-x-1">
                        ${priceHistory.map((price, index) => `
                            <div class="w-2 h-4 bg-${index === priceHistory.length - 1 ? 'blue-500' : 'gray-300'} rounded-sm" 
                                 title="₱${numberFormat(price)}"></div>
                        `).join('')}
                    </div>
                </div>
            `;
            
            return trendsContainer;
        }

        function selectTrip(scheduleId) {
            const selectedTrip = {
                schedule_id: scheduleId,
                origin: searchInputs.origin || '',
                destination: searchInputs.destination || '',
                depart_date: selectedDate || searchInputs.depart_date || '',
                adults: parseInt(searchInputs.adults) || 1
            };

            sessionStorage.setItem('selected_trip', JSON.stringify(selectedTrip));
            
            // Visual feedback
            const button = event.target;
            button.innerHTML = '<i class="fas fa-check mr-2"></i>Selected!';
            button.classList.remove('btn-primary');
            button.classList.add('bg-green-600');
            
            // Navigate to next step (in real app)
            setTimeout(() => {
                alert('Trip selected! Proceeding to passenger details...');
                // window.location.href = '/passenger-details';
            }, 1000);
        }

        // Trip card selection
        document.addEventListener('click', function(e) {
            const tripCard = e.target.closest('.trip-card');
            if (tripCard && !e.target.matches('button')) {
                // Remove selection from other cards
                document.querySelectorAll('.trip-card').forEach(card => {
                    card.classList.remove('selected');
                });
                
                // Add selection to clicked card
                tripCard.classList.add('selected');
            }
        });
    </script>
</body>
</html>
