<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ScheduleController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Consolidated, controller-based routes. No duplicate definitions or stray closures.
|
*/

// Root -> reservations
Route::get('/', fn() => redirect('/user-reservations'));

// Public login views
Route::view('/admin-login', 'admin-login')->name('admin.login');
Route::view('/user-login', 'user-login')->name('user.login');

// User web auth (form POST) — controller handlers
Route::post('/user-register', [AuthController::class, 'register'])->name('user.register');
Route::post('/user-login',    [AuthController::class, 'login'])->name('user.login.submit');
Route::get( '/user-logout',   [AuthController::class, 'logout'])->name('user.logout');

// Admin web auth (form POST) — controller handlers
Route::post('/admin-login',  [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::get( '/admin-logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// AJAX / API (CSRF protected via web middleware)
Route::post('/api/auth/login',    [AuthController::class, 'ajaxLogin'])->name('api.auth.login');
Route::post('/api/auth/register', [AuthController::class, 'ajaxRegister'])->name('api.auth.register');

// User profile (protected by gobus.auth middleware)
Route::get('/user/profile', [ProfileController::class, 'index'])
    ->middleware('gobus.auth')
    ->name('user.profile');

// User reservations — handled by controller (view or JSON)
Route::get('/user/reservations', [ReservationController::class, 'index'])->name('user.reservations');

// Reservations API / controller endpoints
Route::post('/reservations', [ReservationController::class,'store'])->name('reservations.store');
Route::get('/reservations',  [ReservationController::class,'index'])->name('reservations.index');

// Schedules API + stats
Route::post('/schedules', [ScheduleController::class,'store'])->name('schedules.store');
Route::get('/schedules/stats', [ScheduleController::class,'stats'])->name('schedules.stats');

// Admin area — protected by gobus.admin middleware
Route::middleware('gobus.admin')->group(function(){
    Route::get('/admin/schedules',    fn() => view('admin-schedules'))->name('admin.schedules');
    Route::get('/admin/reservations', fn() => view('admin-reservations'))->name('admin.reservations');
    // add other admin controller routes here as needed
});

// Legacy / convenience redirects
Route::get('/user-reservations', fn() => redirect('/user/reservations'));
Route::get('/user-reservations.blade.php', fn() => redirect('/user/reservations'));
Route::get('/login', fn() => redirect('/user-login'));

Route::get('/admin-schedules.blade.php', fn() => redirect('/admin/schedules'));
Route::get('/admin-schedules.html', fn() => redirect('/admin/schedules'));
Route::get('/admin-reservations.blade.php', fn() => redirect('/admin/reservations'));
Route::get('/admin-reservations.html', fn() => redirect('/admin/reservations'));
