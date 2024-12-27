<?php

namespace App\Http\Controllers;

use App\Models\BlockedNumber;
use App\Models\Order;
use App\Models\Play;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function PHPUnit\Framework\isEmpty;

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
        $play = Play::where('locked_from', '>=', Carbon::now()->format('H:i:s'))->first();
        return response()->json([
            'status' => true,
            'play' => $play,
            'message' => 'success',
        ], 200);
    }

    function getCurrentUsers(Request $request)
    {
        $users = User::all();
        return response()->json([
            'status' => true,
            'users' => $users,
            'message' => 'success',
        ], 200);
    }

    function getPlays(Request $request)
    {
        $plays = Play::all();
        return response()->json([
            'status' => true,
            'plays' => $plays,
            'message' => 'success',
        ], 200);
    }

    function getPlaysForEdit(Request $request)
    {
        $plays = Play::all();
        return response()->json([
            'status' => true,
            'plays' => $plays,
            'message' => 'success',
        ], 200);
    }

    function getPlay(Request $request)
    {
        $play = Play::find($request->json('play_id'));
        return response()->json([
            'status' => true,
            'play' => $play,
            'message' => 'success',
        ], 200);
    }

    function getPlayByCode(Request $request)
    {
        $play = Play::where('code', $request->json('play_code'))->first();
        return response()->json([
            'status' => true,
            'play' => $play,
            'message' => 'success',
        ], 200);
    }

    function updatePlay(Request $request)
    {
        Play::where('id', $request->json('play_id'))->update([
            'name' => $request->json('name'),
            'locked_from' => $request->json('locked_from'),
            'locked_to' => $request->json('locked_to'),
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Success! Play has been updated successfully.',
        ], 200);
    }

    function getTicket(Request $request)
    {
        $ticket = Ticket::where('name', $request->json('ticket_name'))->first();
        return response()->json([
            'status' => true,
            'ticket' => $ticket,
            'message' => 'success',
        ], 200);
    }

    function getOrderCount(Request $request)
    {
        $count = Order::where('ticket_number', $request->json('ticket_number'))->where('play_id', $request->json('play_id'))->whereDate('play_date', Carbon::parse($request->json('play_date')))->get()->count();
        return response()->json([
            'status' => true,
            'count' => $count,
            'message' => 'success',
        ], 200);
    }

    function getBlockedNumberCount(Request $request)
    {
        $count = BlockedNumber::where('number', $request->json('number'))->get()->count();
        return response()->json([
            'status' => true,
            'count' => $count,
            'message' => 'success',
        ], 200);
    }

    function saveOrder(Request $request)
    {
        $items = json_decode($request['items'], true);
        /*foreach ($items as $key => $item):
            $data[] = [
                $item['ticket_number'],
            ];
        endforeach;*/
        return response()->json([
            'status' => true,
            'items' => $items,
            'message' => 'Order Saved Successfully!',
            'role' => $request['role'],
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
