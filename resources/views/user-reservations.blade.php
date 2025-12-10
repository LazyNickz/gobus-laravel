<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GoBus — Book Your Trip</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- added: shared app/admin styles for consistent color scheme -->
    <link rel="stylesheet" href="{{ url('frontend/admin.css') }}">
</head>
<body class="bg-gray-100 font-sans">
    <!-- HERO SECTION -->
    <div class="relative w-full h-screen bg-cover bg-center" style="background-image: url('/images/hero-bus.jpg');">
        <!-- make overlay non-interactive so it won't block the nav/buttons -->
        <div class="absolute inset-0 bg-black bg-opacity-40" style="pointer-events:none;"></div>

        <!-- NAVBAR -->
        <nav class="absolute top-0 left-0 w-full flex items-center justify-between p-6 text-white" style="z-index:999;pointer-events:auto;">
            <!-- use primary/accent from admin.css variables -->
            <h1 class="text-2xl font-bold" style="color:var(--accent)">GoBus</h1>
            <ul class="flex space-x-8 text-lg">
                <li><a href="#" class="hover:underline">Book</a></li>
                <li><a href="#" class="hover:underline">Manage</a></li>
                <li><a href="#" class="hover:underline">Travel Info</a></li>
                <li><a href="#" class="hover:underline">About</a></li>
            </ul>

            <!-- DYNAMIC LOGIN / PROFILE BUTTON -->
            @if(!empty($user))
              @php
                $firstName = explode(' ', trim($user['name']))[0] ?? $user['name'];
                $initial = strtoupper(substr($firstName, 0, 1));
              @endphp
              <div class="navbar-action">
                <div class="profile" title="{{ $user['name'] }}">
                  <div class="avatar">{{ $initial }}</div>
                  <div class="profile-name">{{ $firstName }}</div>
                </div>
                <a href="{{ url('/user-logout') }}" class="btn btn-ghost">Logout</a>
              </div>
            @else
              <!-- replaced inline tailwind yellow with shared button class for consistent colors -->
              <a href="{{ url('/user-login') }}?next=/user/reservations" class="btn btn-primary" role="button" tabindex="0">Log in</a>
            @endif
        </nav>

        <!-- CENTERED TITLE -->
        <div class="absolute inset-0 flex flex-col items-center justify-center z-20 text-center text-white">
            <h1 class="text-6xl font-extrabold drop-shadow-lg" style="color:var(--primary)">ILOILO</h1>
            <p class="mt-3 text-xl drop-shadow-lg" style="color:var(--muted)">Explore the beauty of Western Visayas</p>
        </div>

        <!-- BOOKING CARD -->
        <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 w-11/12 max-w-4xl z-30">
            <!-- use shared .card to match admin UI -->
            <div class="card">
                <form class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <!-- FROM -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">From</label>
                        <select class="w-full p-3 border rounded-lg">
                            <option>Manila</option>
                            <option>Cebu</option>
                            <option>Davao</option>
                        </select>
                    </div>

                    <!-- TO -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">To</label>
                        <select class="w-full p-3 border rounded-lg">
                            <option>Iloilo</option>
                            <option>Bacolod</option>
                            <option>Cagayan de Oro</option>
                        </select>
                    </div>

                    <!-- DEPART -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Depart</label>
                        <input type="date" class="w-full p-3 border rounded-lg" />
                    </div>

                    <!-- BUTTON -->
                    <div class="flex items-end">
                        <!-- use shared button class -->
                        <button class="btn btn-primary w-full">Search Buses</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- POPULAR ROUTES SECTION -->
    <div class="py-16 bg-white text-center">
        <h2 class="text-3xl font-bold mb-6" style="color:var(--primary)">Popular Routes</h2>
        <div class="flex justify-center space-x-12">
            <div>
                <img src="/images/davao.jpg" class="w-40 h-40 rounded-full shadow-lg" />
                <p class="mt-3 font-semibold">Davao City</p>
            </div>
            <div>
                <img src="/images/iloilo.jpg" class="w-40 h-40 rounded-full shadow-lg" />
                <p class="mt-3 font-semibold">Iloilo City</p>
            </div>
            <div>
                <img src="/images/baguio.jpg" class="w-40 h-40 rounded-full shadow-lg" />
                <p class="mt-3 font-semibold">Baguio City</p>
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

</body>
</html>