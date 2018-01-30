<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{


    public function getUser() {
        $user = Auth::user();
        $user->load(['company' => function ($query) {
            $query->select(['id', 'name']);
        }]);
        $result = $user->only(['name', 'email', 'pref_language', 'pictureUrl', 'company']);
        return response()->json($result);
    }

    public function exist(Request $request) {
        $exist = true;
        if($request->filled('email') && filter_var($request->email, FILTER_VALIDATE_EMAIL) && User::where('email', $request->email)->count() == 0) {
            $exist = false;
        }

        return response()->json($exist);
    }

    public function setProperty(Request $request) {
        if($request->filled('property') && $request->filled('value')) {
            $property = $request->property;
            $value = $request->value;
            $user = Auth::user();
            $user->$property = $value;
            $user->save();
            return response()->json([]);
        }

        return response()->json([],422);
    }

}
