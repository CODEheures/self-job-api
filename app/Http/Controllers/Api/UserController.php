<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{


    public function getUser() {
        $user = Auth::user()->only(['name', 'email']);
        return response()->json($user);
    }

    public function exist(Request $request) {
        $exist = true;
        if($request->filled('email') && filter_var($request->email, FILTER_VALIDATE_EMAIL) && User::where('email', $request->email)->count() == 0) {
            $exist = false;
        }

        return response()->json($exist);
    }
}
