<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    // show user profile (protected by gobus.auth middleware)
    public function index(Request $r)
    {
        $user = null;
        if ($r->session()->get('gobus_user_logged')) {
            $user = [
                'email' => $r->session()->get('gobus_user_email'),
                'name' => $r->session()->get('gobus_user_name'),
            ];
        }
        return view('user-profile', ['user' => $user]);
    }
}
