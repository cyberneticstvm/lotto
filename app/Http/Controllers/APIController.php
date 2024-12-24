<?php

namespace App\Http\Controllers;

use App\Models\Play;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class APIController extends Controller
{
    private $token;
    function __construct()
    {
        $this->token = '1a2b3c4d5e6f7g8h9i';
    }
    function getAuthUser(Request $request)
    {
        $headers = $this->getHeader($request);
        $user = null;
        if ($headers['authorization'] == $this->token) {
            $credentials = array('email' => $request->json('email'), 'password' => $request->json('password'));
            if (Auth::attempt($credentials)):
                $user = Auth::getProvider()->retrieveByCredentials($credentials);
            endif;
            if ($user):
                return response()->json([
                    'status' => true,
                    'user' => $user,
                    'message' => 'success',
                ], 200);
            else:
                return response()->json([
                    'status' => false,
                    'user' => $user,
                    'message' => 'Invalid Credentials',
                ], 404);
            endif;
        } else {
            return response()->json([
                'status' => false,
                'user' => $user,
                'message' => 'Invalid Authentication Token',
            ], 500);
        }
    }

    function getNextPlay(Request $request)
    {
        $play = Play::where('locked_from', '>=', Carbon::now()->format('H:i:s'));
        return response()->json([
            'status' => true,
            'play' => Carbon::now()->format('H:i:s'),
            'message' => 'success',
        ], 200);
    }

    function getHeader($request)
    {
        $headers = collect($request->header())->transform(function ($item) {
            return $item[0];
        });
        return $headers;
    }
}
