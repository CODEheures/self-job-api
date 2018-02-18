<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Invitation;
use App\Mail\ResetPasswordStepTwo;
use App\Notifications\ResetPasswordStepOne;
use App\Notifications\TeamInvitation;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function resetPassword(Request $request) {
        $this->validate($request, [
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = hash('sha512', json_encode($user->email . strval($user->updated_at)));
            DB::table('password_resets')->insert([
                'email' => $user->email,
                'token' => $token,
            ]);

            $user->notify(new ResetPasswordStepOne($token));
            return response()->json('ok');
        } else {
            return response()->json('ko', 401);
        }
    }

    public function confirmResetPassword($token) {
        $passwordReset = DB::table('password_resets')->where('token', $token)->first();
        if ($passwordReset) {

            $user = User::where('email', $passwordReset->email)->first();

            $newPassword = '';
            for($i = 0; $i<10; $i++) {
                $rand = mt_rand(33,122);
                if (in_array($rand, array_merge(range(48,57), range(65,90), range(97,122)))){
                    $newPassword .=  chr($rand);
                } else {
                    $i--;
                }
            }

            $user->password = User::encodePassword($newPassword);
            $user->save();

            DB::table('password_resets')->where('email', $user->email)->delete();

            return new ResetPasswordStepTwo($newPassword);
        } else {
            return response()->json('ko', 401);
        }
    }

    public function updatePassword(Request $request) {
        $this->validate($request, [
            'password' => 'required|min:6|confirmed'
        ]);

        Auth::user()->update(['password' => User::encodePassword($request->password)]);


        return response()->json('ok', 200);
    }
}
