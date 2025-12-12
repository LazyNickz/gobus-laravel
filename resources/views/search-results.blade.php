<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Search Results — GoBus</title>
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

        .progress-step.active {
            background: rgba(255,255,255,0.2);
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
            color: white;
            font-weight: 600;
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
            font-weight: 600;
        }

        .btn-secondary:hover {
            background: var(--primary-blue);
            color: white;
        }

        .form-input {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.2s ease;
            width: 100%;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0,102,204,0.1);
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            display: block;
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
                <div class="progress-step active">
                    <i class="fas fa-search mb-2"></i>
                    <div class="text-sm font-medium">Search Results</div>
                </div>
                <div class="progress-step">
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
                        <div class="text-lg font-semibold text-gray-900">{{ $origin ?? request()->get('origin', '') }}</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-sm text-gray-500">To</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $destination ?? request()->get('destination', '') }}</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-sm text-gray-500">Date</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $depart_date ?? request()->get('depart_date', '') }}</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-sm text-gray-500">Passengers</div>
                        <div class="text-lg font-semibold text-gray-900">1 Adult</div>
                    </div>
                </div>
                <div class="lg:mt-0 mt-4 text-center lg:text-right">
                    <a href="{{ url('/user/reservations') }}" class="btn-secondary px-4 py-2 text-sm mr-4">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Search
                    </a>
                    <a href="{{ url('/trip-selection?' . http_build_query(request()->only(['origin', 'destination', 'depart_date', 'adults']))) }}" class="btn-primary px-6 py-2 text-sm">
                        <i class="fas fa-bus mr-2"></i>View Available Trips
                    </a>
                </div>
            </div>
        </div>

        <!-- Search Results Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Main Form -->
            <div class="lg:col-span-2">
                <div class="booking-card">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-clipboard-list text-blue-600 mr-3"></i>
                        Confirm Trip Details
                    </h2>




                    <div class="space-y-6">
                        <!-- Trip Summary -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg">
                            <div class="text-center">
                                <div class="text-sm text-gray-500">From</div>
                                <div class="text-lg font-semibold text-gray-900">{{ request()->get('origin', '') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm text-gray-500">To</div>
                                <div class="text-lg font-semibold text-gray-900">{{ request()->get('destination', '') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm text-gray-500">Departure Date</div>
                                <div class="text-lg font-semibold text-gray-900">{{ request()->get('depart_date', '') }}</div>
                            </div>
                        </div>

                        <!-- Trip Selection Info -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-info-circle text-blue-600 text-xl mr-3"></i>
                                <h3 class="text-lg font-semibold text-blue-900">Ready to Select Your Trip</h3>
                            </div>
                            <p class="text-blue-800 mb-4">Click the button below to view available buses and select your preferred departure time.</p>
                            
                            <div class="bg-white rounded-lg p-4 border border-blue-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-route text-blue-600 mr-2"></i>
                                        <span class="text-gray-700 font-medium">{{ request()->get('origin', '') }} → {{ request()->get('destination', '') }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-blue-600 mr-2"></i>
                                        <span class="text-gray-700 font-medium">{{ request()->get('depart_date', '') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->

                        <div class="flex flex-col sm:flex-row gap-4 pt-6">
                            <a href="{{ url('/user/reservations') }}" class="btn-secondary px-6 py-3 text-center">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Search
                            </a>
                            <a href="{{ url('/trip-selection?' . http_build_query(request()->only(['origin', 'destination', 'depart_date', 'adults']))) }}" class="btn-primary px-8 py-3 flex-1 text-center">
                                <i class="fas fa-bus mr-2"></i>Proceed to Trip Selection
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="lg:col-span-1">
                <div class="info-card mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Trip Information
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center">
                            <i class="fas fa-route text-gray-400 w-4 mr-3"></i>
                            <span>Route: {{ request()->get('origin', '') }} → {{ request()->get('destination', '') }}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-calendar text-gray-400 w-4 mr-3"></i>
                            <span>Date: {{ request()->get('depart_date', '') }}</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-clock text-gray-400 w-4 mr-3"></i>
                            <span>Duration: Approximately 5-6 hours</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt text-gray-400 w-4 mr-3"></i>
                            <span>Direct route available</span>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-shield-alt text-blue-600 mr-2"></i>
                        Booking Protection
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 w-4 mr-3"></i>
                            <span>Secure payment processing</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 w-4 mr-3"></i>
                            <span>Free cancellation up to 24h before</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 w-4 mr-3"></i>
                            <span>24/7 customer support</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 w-4 mr-3"></i>
                            <span>Real-time bus tracking</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
