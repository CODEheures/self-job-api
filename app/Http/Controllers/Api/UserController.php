<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Invitation;
use App\Notifications\TeamInvitation;
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
        $result = $user->only(['name', 'email', 'pref_language', 'pictureUrl', 'company', 'can_manage_team']);
        return response()->json($result);
    }

    public function exist(Request $request) {
        $exist = true;
        if($request->filled('email') && filter_var($request->email, FILTER_VALIDATE_EMAIL) && User::where('email', $request->email)->count() == 0) {
            $exist = false;
        }

        return response()->json($exist);
    }

    public function isInvitedAndFree(Request $request) {
        $isInvited = false;
        $isFree = false;
        if($request->filled('email')
            && filter_var($request->email, FILTER_VALIDATE_EMAIL))
        {
            $isInvited = Invitation::where('email', $request->email)->count() > 0;
            $isFree = User::where('email', $request->email)->count() == 0;
        }

        return response()->json(['isInvited' => $isInvited, 'isFree' => $isFree]);
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

    public function invite(Request $request) {

        $this->validate($request, [
            'email' => 'required|email|unique:users,email'
        ]);

        $user = Auth::user();
        if ($user->can_manage_team) {
            $invitation = Invitation::where('email', $request->email)->first();
            if ($invitation) {
                $invitation->company_id = $user->company_id;
                $invitation->save();
            } else {
                $invitation = Invitation::Create([
                    'email' => $request->email,
                    'company_id' => $user->company_id
                ]);
            }

            $invitation->notify(new TeamInvitation());

            return response()->json('ok');
        } else {
            return response()->json('ko', 401);
        }


    }
}
