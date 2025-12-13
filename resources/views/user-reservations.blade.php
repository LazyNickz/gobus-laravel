
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Book Trip — GoBus</title>
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

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
            border: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            cursor: pointer;
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
            padding: 12px 24px;
            cursor: pointer;
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

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Booking Card -->
        <div class="booking-card">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-bus text-blue-600 mr-3"></i>
                Book Your Trip
            </h2>

            <form action="/search" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                @csrf

                <!-- FROM -->
                <div>
                    <label class="form-label">From</label>
                    <select id="origin-select" name="origin" class="form-input">
                        <option value="Manila">Manila</option>
                        <option value="Cebu">Cebu</option>
                        <option value="Davao">Davao</option>
                    </select>
                </div>

                <!-- TO (populated dynamically) -->
                <div>
                    <label class="form-label">To</label>
                    <select id="destination-select" name="destination" class="form-input">
                        <!-- options inserted by JS -->
                    </select>
                </div>

                <!-- DEPART -->
                <div>
                    <label class="form-label">Depart</label>
                    <input type="date" class="form-input" name="depart_date" />
                </div>

                <!-- BUTTON -->
                <div class="flex items-end">
                    <button class="btn-primary w-full" type="submit">
                        <i class="fas fa-search mr-2"></i>Search Buses
                    </button>
                </div>
            </form>
        </div>

        <!-- Popular Routes Section -->
        <div class="mt-12">
            <h3 class="text-2xl font-bold text-gray-900 mb-8 text-center">Popular Routes</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-40 h-40 mx-auto rounded-full overflow-hidden shadow-lg">
                        <img src="/images/davao.jpg" class="w-full h-full object-cover" />
                    </div>
                    <p class="mt-4 font-semibold text-gray-900">Davao City</p>
                </div>
                <div class="text-center">
                    <div class="w-40 h-40 mx-auto rounded-full overflow-hidden shadow-lg">
                        <img src="/images/iloilo.jpg" class="w-full h-full object-cover" />
                    </div>
                    <p class="mt-4 font-semibold text-gray-900">Iloilo City</p>
                </div>
                <div class="text-center">
                    <div class="w-40 h-40 mx-auto rounded-full overflow-hidden shadow-lg">
                        <img src="/images/baguio.jpg" class="w-full h-full object-cover" />
                    </div>
                    <p class="mt-4 font-semibold text-gray-900">Baguio City</p>
                </div>
            </div>
        </div>
    </div>

    <!-- sync server session into client sessionStorage when user present -->
    @if(!empty($user))
    <script>
      try{
        sessionStorage.setItem('gobus_user_logged','1');
        sessionStorage.setItem('gobus_user_email', {!! json_encode($user['email']) !!} );
        sessionStorage.setItem('gobus_user_name', {!! json_encode($user['name']) !!} );
      }catch(e){}
    </script>
    @endif

    <!-- dynamic origin->destination script -->
    <script>
    (function () {
        const map = {
            "Cebu": ["Bacolod", "Dumaguete"],
            "Manila": ["Batangas", "Baguio"],
            "Davao": ["Cagayan de Oro"]
        };

        const originSelect = document.getElementById('origin-select');
        const destSelect = document.getElementById('destination-select');

        function populateDestinations(origin) {
            while (destSelect.firstChild) destSelect.removeChild(destSelect.firstChild);
            const choices = map[origin] || [];
            if (choices.length === 0) {
                const opt = document.createElement('option');
                opt.value = "";
                opt.textContent = "No destinations";
                opt.disabled = true;
                opt.selected = true;
                destSelect.appendChild(opt);
                return;
            }
            choices.forEach((d, idx) => {
                const opt = document.createElement('option');
                opt.value = d;
                opt.textContent = d;
                if (idx === 0) opt.selected = true;
                destSelect.appendChild(opt);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const initialOrigin = originSelect.value || "Manila";
            populateDestinations(initialOrigin);
        });

        originSelect.addEventListener('change', function (e) {
            populateDestinations(e.target.value);
        });
    })();
    </script>

</body>
</html>