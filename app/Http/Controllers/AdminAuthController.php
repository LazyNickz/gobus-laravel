<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminAuthController extends Controller
{
    public function login(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $admin = DB::table('admins')->where('email', strtolower($data['email']))->first();
        if (!$admin || !Hash::check($data['password'], $admin->password)) {
            return redirect()->back()->withErrors(['login' => 'Invalid admin credentials']);
        }
        $r->session()->put('gobus_admin_logged', true);
        $r->session()->put('gobus_admin_email', $admin->email);
        $r->session()->put('gobus_admin_name', $admin->name ?? $admin->email);

        // FIX: Make sure this is the correct redirect!
        return redirect('/admin/schedules');
    }

    public function logout(Request $r)
    {
        $r->session()->forget(['gobus_admin_logged','gobus_admin_email','gobus_admin_name']);
        return redirect('/admin-login');
    }
}
