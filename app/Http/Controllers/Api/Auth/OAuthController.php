<?php

namespace App\Http\Controllers\Api\Auth;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Client;

class OAuthController extends Controller
{
    private $client;

    public function __construct() {
        $this->client = Client::find(1);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function register(Request $request) {
        //Request Validation
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed'
        ]);

        //User Creation
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        //Get Tokens and return this
        return $this->createResponse('password', $request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function login(Request $request) {
        //Request Validation
        $this->validate($request, [
           'email' => 'required|email',
           'password' => 'required'
        ]);

        //Get Tokens and return this
        return $this->createResponse('password', $request);
    }

    /**
     *
     * Use Bearer Refresh Token to authenticate
     *
     * @param Request $request
     * @return mixed
     */
    public function refresh(Request $request) {
        //Request Validation
        $this->validate($request, [
            'refresh_token' => 'required'
        ]);

        //Get Tokens and return this
        return $this->createResponse('refresh_token', $request);
    }


    /**
     *
     * Use Bearer Token to authenticate
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {

        $access_token = Auth::user()->token();

        DB::table('oauth_refresh_tokens')->where('access_token_id', $access_token->id)->update(['revoked' => true]);

        $access_token->revoke();

        $response = response()->json([], 204);

        return $response;
    }


    /**
     * @param string $paramType ("password" for register & login, "refresh_token" for refresh)
     * @param Request $request
     * @return mixed
     */
    private function createResponse(string $paramType, Request $request) {
        $params = [
            'grant_type' => $paramType,
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
        ];

        if($paramType === 'password'){
            $params = array_merge($params, ['username' => $request->email, 'password' => $request->password, 'scope' => '*']);
        }

        $request->request->add($params);

        $proxy = Request::create('oauth/token', 'POST');

        $response = Route::dispatch($proxy);


        return $response;
    }
}
