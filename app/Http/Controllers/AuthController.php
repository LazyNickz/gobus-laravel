<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    // web POST register (form)
    public function register(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
        ]);

        // Remove auto-login - redirect to login page instead
        return redirect('/user-login')->with('success', 'Registration successful! Please login with your credentials.');
    }

    // web POST login (form)
    public function login(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = User::where('email', strtolower($data['email']))->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return redirect()->back()->withErrors(['login' => 'Invalid credentials'])->withInput();
        }
        $r->session()->put('gobus_user_logged', true);
        $r->session()->put('gobus_user_email', $user->email);
        $r->session()->put('gobus_user_name', $user->name);

        $next = $r->input('next');
        if ($next && strpos($next, '/') === 0) return redirect($next);
        return redirect('/user/reservations');
    }

    public function logout(Request $r)
    {
        $r->session()->forget(['gobus_user_logged','gobus_user_email','gobus_user_name']);
        return redirect('/user-reservations');
    }

    // AJAX JSON login (CSRF-protected, returns JSON)
    public function ajaxLogin(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = User::where('email', strtolower($data['email']))->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['ok' => false, 'error' => 'Invalid credentials'], 422);
        }
        $r->session()->put('gobus_user_logged', true);
        $r->session()->put('gobus_user_email', $user->email);
        $r->session()->put('gobus_user_name', $user->name);
        return response()->json(['ok' => true, 'redirect' => '/user/reservations']);
    }


    // AJAX JSON register
    public function ajaxRegister(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
        ]);

        // Remove auto-login - redirect to login page instead
        return response()->json(['ok' => true, 'redirect' => '/user-login', 'message' => 'Registration successful! Please login with your credentials.']);
    }
}
