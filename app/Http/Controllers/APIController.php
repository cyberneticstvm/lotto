<?php

namespace App\Http\Controllers;

use App\Models\BlockedNumber;
use App\Models\Order;
use App\Models\Play;
use App\Models\Result;
use App\Models\Scheme;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

    function getAllUsers(Request $request)
    {
        $users = User::all();
        return response()->json([
            'status' => true,
            'users' => $users,
            'message' => 'success',
        ], 200);
    }

    function saveUser(Request $request)
    {
        User::insert([
            'name' => $request->json('name'),
            'email' => $request->json('email'),
            'password' => Hash::make($request->json('password')),
            'role' => $request->json('role'),
            'parent_id' => $request->json('parent_id'),
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Success! User saved successfully.',
        ], 200);
    }

    function deleteUser(Request $request)
    {
        User::where('id', $request->json('uid'))->delete();
        return response()->json([
            'status' => true,
            'message' => 'Success! User deleted successfully.',
        ], 200);
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
        $users = User::where('role', 'user')->select('id', 'name')->when($request->json('role') == 'leader', function ($q) use ($request) {
            return $q->where('parent_id', $request->json('user_id'));
        })->when($request->json('role') == 'user', function ($q) use ($request) {
            return $q->where('id', $request->json('user_id'));
        })->get();
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
            'locked_from' => Carbon::parse($request->json('locked_from'))->format('H:i:s'),
            'locked_to' => Carbon::parse($request->json('locked_to'))->format('H:i:s'),
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
        $order = Order::where('ticket_number', $request->json('ticket_number'))->where('play_id', $request->json('play_id'))->whereDate('play_date', Carbon::parse($request->json('play_date')))->get();
        return response()->json([
            'status' => true,
            'count' => $order->sum('ticket_count'),
            'message' => 'success',
        ], 200);
    }

    function getBlockedNumberCount(Request $request)
    {
        $number = BlockedNumber::where('number', $request->json('ticket_number'))->first();
        return response()->json([
            'status' => true,
            'count' => $number ? $number->max_count : 0,
            'message' => 'success',
        ], 200);
    }

    function saveOrder(Request $request)
    {
        $items = json_decode($request['items'], true);
        $role = $request['role'];
        $uid = $request['user_id'];
        if ($role != 'leader'):
            $uid = User::where('id', $uid)->first()->parent_id;
        endif;
        $billnumber = Order::latest()->first()->bill_number;
        $data = [];
        foreach ($items as $key => $item):
            $data[] = [
                'bill_number' => $billnumber ? $billnumber + 1 : 1,
                'ticket_id' => $item['ticketId'],
                'ticket_name' => strtolower($item['ticket']),
                'user_id' => $item['userId'],
                'parent_id' => $uid,
                'play_id' => $item['playId'],
                'play_code' => $item['play'],
                'ticket_number' => $item['number'],
                'ticket_count' => $item['count'],
                'user_rate' => $item['userPrice'],
                'leader_rate' => $item['leaderPrice'],
                'admin_rate' => $item['adminPrice'],
                'play_date' => explode(' ', $item['playDate'])[0],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        endforeach;
        Order::insert($data);
        return response()->json([
            'status' => true,
            'message' => 'Order Saved Successfully!',
        ], 200);
    }

    function saveBlockedNumber(Request $request)
    {
        BlockedNumber::insert([
            'number' => $request->json('number'),
            'max_count' => $request->json('max_count'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Number blocked successfully.',
        ], 200);
    }

    function getBlockedNumber()
    {
        $bnos = BlockedNumber::all();
        return response()->json([
            'status' => true,
            'blockednumbers' => $bnos,
            'message' => 'success',
        ], 200);
    }

    function deleteBlockedNumber(Request $request)
    {
        BlockedNumber::where('id', $request->json('bnid'))->delete();
        return response()->json([
            'status' => true,
            'message' => 'Number removed successfully.',
        ], 200);
    }

    function saveResult(Request $request)
    {
        Result::insert([
            'play_id' => $request->json('play_id'),
            'play_date' => $request->json('play_date'),
            'p1' => $request->json('p1'),
            'p2' => $request->json('p2'),
            'p3' => $request->json('p3'),
            'p4' => $request->json('p4'),
            'p5' => $request->json('p5'),
            'p6' => $request->json('p6'),
            'p7' => $request->json('p7'),
            'p8' => $request->json('p8'),
            'p9' => $request->json('p9'),
            'p10' => $request->json('p10'),
            'p11' => $request->json('p11'),
            'p12' => $request->json('p12'),
            'p13' => $request->json('p13'),
            'p14' => $request->json('p14'),
            'p15' => $request->json('p15'),
            'p16' => $request->json('p16'),
            'p17' => $request->json('p17'),
            'p18' => $request->json('p18'),
            'p19' => $request->json('p19'),
            'p20' => $request->json('p20'),
            'p21' => $request->json('p21'),
            'p22' => $request->json('p22'),
            'p23' => $request->json('p23'),
            'p24' => $request->json('p24'),
            'p25' => $request->json('p25'),
            'p26' => $request->json('p26'),
            'p27' => $request->json('p27'),
            'p28' => $request->json('p28'),
            'p29' => $request->json('p29'),
            'p30' => $request->json('p30'),
            'p31' => $request->json('p31'),
            'p32' => $request->json('p32'),
            'p33' => $request->json('p33'),
            'p34' => $request->json('p34'),
            'p35' => $request->json('p35'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Result updated successfully.',
        ], 200);
    }

    function getAllTickets(Request $request)
    {
        $tickets = Ticket::all();
        return response()->json([
            'status' => true,
            'tickets' => $tickets,
            'message' => 'success',
        ], 200);
    }

    function getTicketForEdit(Request $request)
    {
        $ticket = Ticket::where('id', $request->json('ticket_id'))->first();
        return response()->json([
            'status' => true,
            'ticket' => $ticket,
            'message' => 'success',
        ], 200);
    }

    function updateTicket(Request $request)
    {
        Ticket::where('id', $request->json('ticket_id'))->update([
            'admin_rate' => $request->json('admin_rate'),
            'leader_rate' => $request->json('leader_rate'),
            'user_rate' => $request->json('user_rate'),
            'max_count' => $request->json('max_count'),
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Ticket updated successfully.',
        ], 200);
    }

    function getAllSchemes(Request $request)
    {
        $schemes = Scheme::leftJoin('tickets', 'schemes.ticket_id', 'tickets.id')->select('schemes.id', 'tickets.name', 'schemes.position', 'schemes.count', 'schemes.amount', 'schemes.super')->get();
        return response()->json([
            'status' => true,
            'schemes' => $schemes,
            'message' => 'success',
        ], 200);
    }

    function getSchemeForEdit(Request $request)
    {
        $scheme = Scheme::where('id', $request->json('scheme_id'))->first();
        return response()->json([
            'status' => true,
            'scheme' => $scheme,
            'message' => 'success',
        ], 200);
    }

    function updateScheme(Request $request)
    {
        Scheme::where('id', $request->json('scheme_id'))->update([
            'amount' => $request->json('amount'),
            'count' => $request->json('count'),
            'super' => $request->json('super'),
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Scheme updated successfully.',
        ], 200);
    }

    function getResult(Request $request)
    {
        $result = Result::whereDate('play_date', $request->json('play_date'))->where('play_id', $request->json('play_id'))->first();
        return response()->json([
            'status' => true,
            'result' => $result,
            'message' => 'success',
        ], 200);
    }

    function getNumberWiseReport(Request $request)
    {
        $result = Order::whereDate('play_date', $request->json('play_date'))->when($request->json('play_id'), function ($q) use ($request) {
            return $q->where('play_id', $request->json('play_id'));
        })->when($request->json('ticket_id'), function ($q) use ($request) {
            return $q->where('ticket_id', $request->json('ticket_id'));
        })->when($request->json('ticket_number'), function ($q) use ($request) {
            return $q->where('ticket_number', $request->json('ticket_number'));
        })->when($request->json('role') == 'leader', function ($q) use ($request) {
            return $q->where('parent_id', $request->json('user_id'));
        })->when($request->json('role') == 'user', function ($q) use ($request) {
            return $q->where('user_id', $request->json('user_id'));
        })->when($request->json('option') == 1, function ($q) {
            return $q->whereIn('ticket_id', [6, 7, 8]);
        })->when($request->json('option') == 2, function ($q) {
            return $q->whereIn('ticket_id', [3, 4, 5]);
        })->when($request->json('option') == 3, function ($q) {
            return $q->whereIn('ticket_id', [1, 2]);
        })->get();
        return response()->json([
            'status' => true,
            'record' => $result,
            'count' => $result->sum('ticket_count'),
            'message' => 'success',
        ], 200);
    }

    function getPlaysForReport(Request $request)
    {
        $all = Play::selectRaw("'0' as id, 'All' as name");
        $play = Play::select('id', 'name')->union($all)->get();
        return response()->json([
            'status' => true,
            'play' => $play,
            'message' => 'success',
        ], 200);
    }

    function getTicketsForReport(Request $request)
    {
        $all = Ticket::selectRaw("'0' as id, 'All' as name");
        $ticket = Ticket::select('id', 'name')->union($all)->get();
        return response()->json([
            'status' => true,
            'ticket' => $ticket,
            'message' => 'success',
        ], 200);
    }

    function getUsersForReport(Request $request)
    {
        $all = User::selectRaw("'0' as id, 'All' as name");
        $user = User::where('role', 'user')->select('id', 'name')->when($request->json('role') == 'leader', function ($q) use ($request) {
            return $q->where('parent_id', $request->json('user_id'));
        })->when($request->json('role') == 'user', function ($q) use ($request) {
            return $q->where('id', $request->json('user_id'));
        });
        if ($request->json('role') != 'user') {
            $user->union($all);
        }
        return response()->json([
            'status' => true,
            'user' => $user->get(),
            'message' => 'success',
        ], 200);
    }

    function getSalesReport(Request $request)
    {
        $ratecol = 'orders.user_rate';
        if ($request->json('role') == 'admin'):
            $ratecol = 'orders.admin_rate';
        endif;
        if ($request->json('role') == 'leader'):
            $ratecol = 'orders.leader_rate';
        endif;
        $data = Order::selectRaw("orders.id, SUM(orders.ticket_count) AS ticket_count, $ratecol * orders.ticket_count AS total")->whereBetween('play_date', [$request->json('from_date'), $request->json('to_date')])->when($request->json('play_id') > 0, function ($q) use ($request) {
            return $q->where('play_id', $request->json('play_id'));
        })->when($request->json('ticket_id') > 0, function ($q) use ($request) {
            return $q->where('ticket_id', $request->json('ticket_id'));
        })->when($request->json('ticket_number') != null, function ($q) use ($request) {
            return $q->where('ticket_number', $request->json('ticket_number'));
        })->when($request->json('bill_number') != null, function ($q) use ($request) {
            return $q->where('bill_number', $request->json('bill_number'));
        })->when($request->json('role') == 'leader', function ($q) use ($request) {
            return $q->where('parent_id', $request->json('user_id'));
        })->when($request->json('salesUser') > 0 || $request->json('role') == 'user', function ($q) use ($request) {
            return $q->where('user_id', ($request->json('salesUser') > 0) ? $request->json('salesUser') : $request->json('user_id'));
        })->when($request->json('option') == 1, function ($q) {
            return $q->whereIn('ticket_id', [6, 7, 8]);
        })->when($request->json('option') == 2, function ($q) {
            return $q->whereIn('ticket_id', [3, 4, 5]);
        })->when($request->json('option') == 3, function ($q) {
            return $q->whereIn('ticket_id', [1, 2]);
        })->groupBy('orders.id')->get();
        return response()->json([
            'status' => true,
            'count' => $data->sum('ticket_count'),
            'total' => $data->sum('total'),
            'message' => 'success',
        ], 200);
    }

    function getSalesReportByUser(Request $request)
    {
        $ratecol = 'o.user_rate';
        if ($request->json('role') == 'admin'):
            $ratecol = 'o.admin_rate';
        endif;
        if ($request->json('role') == 'leader'):
            $ratecol = 'o.leader_rate';
        endif;
        $record = collect(DB::select("SELECT tbl1.id, tbl1.name, SUM(tbl1.ticket_count) AS ticket_count, SUM(tbl1.total) AS total FROM (SELECT o.id AS orderid, u.id, u.name, o.ticket_name, o.ticket_number, SUM(o.ticket_count) AS ticket_count, $ratecol * o.ticket_count AS total FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.play_date BETWEEN ? AND ? AND IF(? > 0, o.play_id = ?, 1) AND IF(? > 0, o.ticket_id = ?, 1) AND IF (? != '', o.ticket_number = ?, 1) AND IF(? != '', o.bill_number = ?, 1) AND IF(? = 'leader', o.parent_id = ?, 1) AND IF(? = 'user', o.user_id = ?, 1) AND IF(? > 0 , o.user_id = ?, 1) AND IF(?=1, o.ticket_id IN(6,7,8), 1) AND IF(?=2, o.ticket_id IN(3,4,5), 1) AND IF(?=3, o.ticket_id IN(1,2), 1) GROUP BY orderid) AS tbl1 GROUP BY id, `name`", [$request->json('from_date'), $request->json('to_date'), $request->json('play_id'), $request->json('play_id'), $request->json('ticket_id'), $request->json('ticket_id'), $request->json('ticket_number'), $request->json('ticket_number'), $request->json('bill_number'), $request->json('bill_number'), $request->json('role'), $request->json('user_id'), $request->json('role'), $request->json('user_id'), $request->json('salesUser'), $request->json('user_id'), $request->json('option'), $request->json('option'), $request->json('option')]));

        return response()->json([
            'status' => true,
            'record' => $record,
            'total' => $record->sum('total'),
            'count' => $record->sum('ticket_count'),
            'message' => 'success',
        ], 200);
    }

    function getSalesReportByBill(Request $request)
    {
        $ratecol = 'o.user_rate';
        if ($request->json('role') == 'admin'):
            $ratecol = 'o.admin_rate';
        endif;
        if ($request->json('role') == 'leader'):
            $ratecol = 'o.leader_rate';
        endif;
        $day = date('Y-m-d');
        $time = date('H:i:s');
        $data = collect(DB::select("SELECT tbl1.id, tbl1.name, tbl1.bill_number, tbl1.play_date, SUM(tbl1.ticket_count) AS ticket_count, SUM(tbl1.total) AS total, tbl1.dlt, tbl1.play_time FROM (SELECT o.id AS orderid, u.id, u.name, o.bill_number, o.ticket_name, o.ticket_number, DATE_FORMAT(o.play_date, '%d/%m/%y') AS play_date, DATE_FORMAT(o.created_at, '%H:%i:%s') AS play_time, SUM(o.ticket_count) AS ticket_count, $ratecol * o.ticket_count AS total, CASE WHEN o.play_date >= '$day' AND p.locked_from > '$time' THEN 'y' ELSE 'n' END AS dlt FROM orders o LEFT JOIN users u ON o.user_id = u.id LEFT JOIN plays p ON p.id = o.play_id WHERE o.play_date BETWEEN ? AND ? AND IF(? > 0, o.play_id = ?, 1) AND IF(? > 0, o.ticket_id = ?, 1) AND IF (? != '', o.ticket_number = ?, 1) AND IF(? != '', o.bill_number = ?, 1) AND o.user_id = ? AND IF(?=1, o.ticket_id IN(6,7,8), 1) AND IF(?=2, o.ticket_id IN(3,4,5), 1) AND IF(?=3, o.ticket_id IN(1,2), 1) GROUP BY orderid) AS tbl1 GROUP BY bill_number", [$request->json('from_date'), $request->json('to_date'), $request->json('play_id'), $request->json('play_id'), $request->json('ticket_id'), $request->json('ticket_id'), $request->json('ticket_number'), $request->json('ticket_number'), $request->json('bill_number'), $request->json('bill_number'), $request->json('selectedUser'), $request->json('option'), $request->json('option'), $request->json('option')]));
        return response()->json([
            'status' => true,
            'record' => $data,
            'total' => $data->sum('total'),
            'count' => $data->sum('ticket_count'),
            'message' => 'success',
        ], 200);
    }

    function getSalesReportByBillAll(Request $request)
    {
        $ratecol = 'user_rate';
        if ($request->json('role') == 'admin'):
            $ratecol = 'admin_rate';
        endif;
        if ($request->json('role') == 'leader'):
            $ratecol = 'leader_rate';
        endif;
        $data = Order::where('bill_number', $request->json('bill_number'))->selectRaw("id, play_date, bill_number, ticket_number, play_code, ticket_count, $ratecol * ticket_count as price")->when($request->json('option') == 1, function ($q) {
            return $q->whereIn('ticket_id', [6, 7, 8]);
        })->when($request->json('option') == 2, function ($q) {
            return $q->whereIn('ticket_id', [3, 4, 5]);
        })->when($request->json('option') == 3, function ($q) {
            return $q->whereIn('ticket_id', [1, 2]);
        })->get();
        return response()->json([
            'status' => true,
            'record' => $data,
            'message' => 'success',
        ], 200);
    }

    function getBillsForDelete(Request $request)
    {
        $bills = Order::leftJoin('plays AS p', 'orders.play_id', 'p.id')->whereDate('orders.play_date', '>=', Carbon::today())->whereTime('p.locked_from', '>', Carbon::now())->selectRaw("orders.id, orders.play_date, orders.ticket_name, orders.ticket_number, orders.bill_number, p.code, DATE_FORMAT(orders.play_date, '%d/%b/%y') AS pdate")->when($request->json('role') == 'user', function ($q) use ($request) {
            return $q->where('orders.user_id', $request->json('user_id'));
        })->when($request->json('role') == 'leader', function ($q) use ($request) {
            return $q->where('orders.parent_id', $request->json('user_id'));
        })->get();
        return response()->json([
            'status' => true,
            'bills' => $bills,
            'message' => 'success',
        ], 200);
    }

    function deleteBill(Request $request)
    {
        if ($request->json('bill_id') > 0):
            Order::where('bill_number', $request->json('bill_number'))->where('id', $request->json('bill_id'))->delete();
        else:
            Order::where('bill_number', $request->json('bill_number'))->delete();
        endif;
        return response()->json([
            'status' => true,
            'message' => 'Bill deleted successfully.',
        ], 200);
    }

    function getNetPayReport(Request $request)
    {
        $ratecol = 'o.user_rate';
        if ($request->json('role') == 'admin'):
            $ratecol = 'o.admin_rate';
        endif;
        if ($request->json('role') == 'leader'):
            $ratecol = 'o.leader_rate';
        endif;

        $data = collect(DB::select("SELECT tbl3.id, tbl3.name AS name, SUM(tbl3.purchase) AS purchase, SUM(tbl3.won) AS won, SUM(tbl3.purchase) - SUM(tbl3.won) AS balance, tbl3.play_date FROM (SELECT tbl2.play_date, tbl2.uid AS id, tbl2.uname AS name, tbl2.bill_number, tbl2.ticket_name, tbl2.ticket_number, tbl2.ticket_count, tbl2.position, tbl2.purchase, tbl2.p1 + tbl2.p2 + tbl2.p3 + tbl2.p4 + tbl2.p5 + tbl2.p6 + tbl2.p7 + tbl2.p8 + tbl2.p9 + tbl2.p10 + tbl2.p11 + tbl2.p12 + tbl2.p13 + tbl2.p14 + tbl2.p15 + tbl2.p16 + tbl2.p17 + tbl2.p18 + tbl2.p19 + tbl2.p20 + tbl2.p21 + tbl2.p22 + tbl2.p23 + tbl2.p24 + tbl2.p25 + tbl2.p26 + tbl2.p27 + tbl2.p28 + tbl2.p29 + tbl2.p30 + tbl2.p31 + tbl2.p32 + tbl2.p33 + tbl2.p34 + tbl2.p35 + tbl2.ab + tbl2.bc + tbl2.ac + tbl2.a + tbl2.b + tbl2.c + tbl2.bx1 AS won, tbl2.s1 + tbl2.s2 + tbl2.s3 + tbl2.s4 + tbl2.s5 + tbl2.s6 + tbl2.s7 + tbl2.s8 + tbl2.s9 + tbl2.s10 + tbl2.s11 + tbl2.s12 + tbl2.s13 + tbl2.s14 + tbl2.s15 + tbl2.s16 + tbl2.s17 + tbl2.s18 + tbl2.s19 + tbl2.s20 + tbl2.s21 + tbl2.s22 + tbl2.s23 + tbl2.s24 + tbl2.s25 + tbl2.s26 + tbl2.p27 + tbl2.s28 + tbl2.s29 + tbl2.s30 + tbl2.s31 + tbl2.s32 + tbl2.s33 + tbl2.s34 + tbl2.s35 + tbl2.sab + tbl2.sbc + tbl2.sac + tbl2.sa + tbl2.sb + tbl2.sc + tbl2.sbx1 AS super FROM(SELECT tbl1.*, CASE WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id IN(1,2) OR tbl1.ticket_id IN(3,4,5,6,7,8) THEN '1' WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id IN(1,2) THEN '2' WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id IN(1) THEN '3' WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id IN(1) THEN '4' WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id IN(1) THEN '5' WHEN tbl1.ticket_id = 2 AND tbl1.ticket_number != r.p1 THEN 2 ELSE 6 END AS position, CASE WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=1) WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id = 2 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=7) ELSE 0 END AS p1, CASE WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=2) ELSE 0 END AS p2, CASE WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=3) ELSE 0 END AS p3, CASE WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=4) ELSE 0 END AS p4, CASE WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=5) ELSE 0 END AS p5, CASE WHEN tbl1.ticket_number = r.p6 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p6, CASE WHEN tbl1.ticket_number = r.p7 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p7, CASE WHEN tbl1.ticket_number = r.p8 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p8, CASE WHEN tbl1.ticket_number = r.p9 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p9, CASE WHEN tbl1.ticket_number = r.p10 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p10, CASE WHEN tbl1.ticket_number = r.p11 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p11, CASE WHEN tbl1.ticket_number = r.p12 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p12, CASE WHEN tbl1.ticket_number = r.p13 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p13, CASE WHEN tbl1.ticket_number = r.p14 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p14, CASE WHEN tbl1.ticket_number = r.p15 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p15, CASE WHEN tbl1.ticket_number = r.p16 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p16, CASE WHEN tbl1.ticket_number = r.p17 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p17, CASE WHEN tbl1.ticket_number = r.p18 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p18, CASE WHEN tbl1.ticket_number = r.p19 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p19, CASE WHEN tbl1.ticket_number = r.p20 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p20, CASE WHEN tbl1.ticket_number = r.p21 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p21, CASE WHEN tbl1.ticket_number = r.p22 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p22, CASE WHEN tbl1.ticket_number = r.p23 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p23, CASE WHEN tbl1.ticket_number = r.p24 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p24, CASE WHEN tbl1.ticket_number = r.p25 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p25, CASE WHEN tbl1.ticket_number = r.p26 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p26, CASE WHEN tbl1.ticket_number = r.p27 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p27, CASE WHEN tbl1.ticket_number = r.p28 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p28, CASE WHEN tbl1.ticket_number = r.p29 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p29, CASE WHEN tbl1.ticket_number = r.p30 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p30, CASE WHEN tbl1.ticket_number = r.p31 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p31, CASE WHEN tbl1.ticket_number = r.p32 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p32, CASE WHEN tbl1.ticket_number = r.p33 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p33, CASE WHEN tbl1.ticket_number = r.p34 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p34, CASE WHEN tbl1.ticket_number = r.p35 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p35, CASE WHEN tbl1.ticket_name = 'ab' AND tbl1.ticket_number = LEFT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=13) ELSE 0 END AS ab, CASE WHEN tbl1.ticket_name = 'bc' AND tbl1.ticket_number = RIGHT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=14) ELSE 0 END AS bc, CASE WHEN tbl1.ticket_name = 'ac' AND tbl1.ticket_number = CONCAT(LEFT(r.p1, 1),RIGHT(r.p1, 1)) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=15) ELSE 0 END AS ac, CASE WHEN tbl1.ticket_name = 'a' AND tbl1.ticket_number = LEFT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=16) ELSE 0 END AS a, CASE WHEN tbl1.ticket_name = 'b' AND tbl1.ticket_number = substring(r.p1, 2, 1) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=17) ELSE 0 END AS b, CASE WHEN tbl1.ticket_name = 'c' AND tbl1.ticket_number = RIGHT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=18) ELSE 0 END AS c, CASE WHEN tbl1.ticket_id = 2 AND LOCATE(LEFT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(RIGHT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(substring(tbl1.ticket_number, 2, 1), r.p1) > 0 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=8) ELSE 0 END AS bx1, CASE WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=1) ELSE 0 END AS s1, CASE WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=2) ELSE 0 END AS s2, CASE WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=3) ELSE 0 END AS s3, CASE WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=4) ELSE 0 END AS s4, CASE WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=5) ELSE 0 END AS s5, CASE WHEN tbl1.ticket_number = r.p6 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s6, CASE WHEN tbl1.ticket_number = r.p7 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s7, CASE WHEN tbl1.ticket_number = r.p8 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s8, CASE WHEN tbl1.ticket_number = r.p9 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s9, CASE WHEN tbl1.ticket_number = r.p10 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s10, CASE WHEN tbl1.ticket_number = r.p11 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s11, CASE WHEN tbl1.ticket_number = r.p12 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s12, CASE WHEN tbl1.ticket_number = r.p13 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s13, CASE WHEN tbl1.ticket_number = r.p14 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s14, CASE WHEN tbl1.ticket_number = r.p15 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s15, CASE WHEN tbl1.ticket_number = r.p16 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s16, CASE WHEN tbl1.ticket_number = r.p17 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s17, CASE WHEN tbl1.ticket_number = r.p18 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s18, CASE WHEN tbl1.ticket_number = r.p19 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s19, CASE WHEN tbl1.ticket_number = r.p20 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s20, CASE WHEN tbl1.ticket_number = r.p21 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s21, CASE WHEN tbl1.ticket_number = r.p22 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s22, CASE WHEN tbl1.ticket_number = r.p23 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s23, CASE WHEN tbl1.ticket_number = r.p24 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s24, CASE WHEN tbl1.ticket_number = r.p25 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s25, CASE WHEN tbl1.ticket_number = r.p26 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s26, CASE WHEN tbl1.ticket_number = r.p27 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s27, CASE WHEN tbl1.ticket_number = r.p28 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s28, CASE WHEN tbl1.ticket_number = r.p29 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s29, CASE WHEN tbl1.ticket_number = r.p30 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s30, CASE WHEN tbl1.ticket_number = r.p31 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s31, CASE WHEN tbl1.ticket_number = r.p32 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s32, CASE WHEN tbl1.ticket_number = r.p33 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s33, CASE WHEN tbl1.ticket_number = r.p34 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s34, CASE WHEN tbl1.ticket_number = r.p35 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s35, CASE WHEN tbl1.ticket_name = 'ab' AND tbl1.ticket_number = LEFT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=13) ELSE 0 END AS sab, CASE WHEN tbl1.ticket_name = 'bc' AND tbl1.ticket_number = RIGHT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=14) ELSE 0 END AS sbc, CASE WHEN tbl1.ticket_name = 'ac' AND tbl1.ticket_number = CONCAT(LEFT(r.p1, 1),RIGHT(r.p1, 1)) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=15) ELSE 0 END AS sac, CASE WHEN tbl1.ticket_name = 'a' AND tbl1.ticket_number = LEFT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=16) ELSE 0 END AS sa, CASE WHEN tbl1.ticket_name = 'b' AND tbl1.ticket_number = substring(r.p1, 2, 1) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=17) ELSE 0 END AS sb, CASE WHEN tbl1.ticket_name = 'c' AND tbl1.ticket_number = RIGHT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=18) ELSE 0 END AS sc, CASE WHEN tbl1.ticket_id = 2 AND LOCATE(LEFT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(RIGHT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(substring(tbl1.ticket_number, 2, 1), r.p1) > 0 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=8) ELSE 0 END AS sbx1, CASE WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id = 2 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=8) ELSE 0 END AS sbx2, CASE WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id = 2 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=9) ELSE 0 END AS sbx3, CASE WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id = 2 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=10) ELSE 0 END AS sbx4, CASE WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id = 2 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=11) ELSE 0 END AS sbx5, CASE WHEN tbl1.ticket_number = r.p6 AND tbl1.ticket_id = 2 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=12) ELSE 0 END AS sbx6 FROM (SELECT r.id AS resultid, p.id AS playid, p.code AS playcode, o.id AS order_id, o.bill_number, o.play_date, o.ticket_id, u.id AS uid, u.name AS uname, o.ticket_number, t.name AS ticket_name, o.ticket_count, $ratecol*o.ticket_count AS purchase FROM orders o LEFT JOIN tickets t ON t.id = o.ticket_id LEFT JOIN users u ON o.user_id = u.id LEFT JOIN plays p ON p.id = o.play_id LEFT JOIN results r ON r.play_id = o.play_id AND r.play_date = o.play_date WHERE IF(? = 'user', o.user_id = ?, 1) AND IF(? > 0, o.play_id = ?, 1) AND o.play_date BETWEEN ? AND ?) AS tbl1 LEFT JOIN schemes s ON s.ticket_id = tbl1.ticket_id LEFT JOIN results r ON r.id = tbl1.resultid GROUP BY tbl1.order_id) AS tbl2) AS tbl3 GROUP BY id, play_date", [$request->json('role'), $request->json('user_id'), $request->json('play_id'), $request->json('play_id'), $request->json('from_date'), $request->json('to_date')]));

        return response()->json([
            'status' => true,
            'record' => $data,
            'purchase' => $data->sum('purchase'),
            'winning' => $data->sum('won'),
            'balance' => $data->sum('balance'),
            'message' => 'success',
        ], 200);
    }

    function getAccountSummaryReport(Request $request)
    {
        $ratecol = 'o.user_rate';
        if ($request->json('role') == 'admin'):
            $ratecol = 'o.admin_rate';
        endif;
        if ($request->json('role') == 'leader'):
            $ratecol = 'o.leader_rate';
        endif;
        $data = collect(DB::select("SELECT tbl3.id, tbl3.name AS name, SUM(tbl3.purchase) AS purchase, SUM(tbl3.won) AS won, SUM(tbl3.won) - SUM(tbl3.purchase) AS balance, tbl3.play_date FROM (SELECT tbl2.play_date, tbl2.uid AS id, tbl2.uname AS name, tbl2.bill_number, tbl2.ticket_name, tbl2.ticket_number, tbl2.ticket_count, tbl2.position, tbl2.purchase, tbl2.p1 + tbl2.p2 + tbl2.p3 + tbl2.p4 + tbl2.p5 + tbl2.p6 + tbl2.p7 + tbl2.p8 + tbl2.p9 + tbl2.p10 + tbl2.p11 + tbl2.p12 + tbl2.p13 + tbl2.p14 + tbl2.p15 + tbl2.p16 + tbl2.p17 + tbl2.p18 + tbl2.p19 + tbl2.p20 + tbl2.p21 + tbl2.p22 + tbl2.p23 + tbl2.p24 + tbl2.p25 + tbl2.p26 + tbl2.p27 + tbl2.p28 + tbl2.p29 + tbl2.p30 + tbl2.p31 + tbl2.p32 + tbl2.p33 + tbl2.p34 + tbl2.p35 + tbl2.ab + tbl2.bc + tbl2.ac + tbl2.a + tbl2.b + tbl2.c + tbl2.bx1 AS won, tbl2.s1 + tbl2.s2 + tbl2.s3 + tbl2.s4 + tbl2.s5 + tbl2.s6 + tbl2.s7 + tbl2.s8 + tbl2.s9 + tbl2.s10 + tbl2.s11 + tbl2.s12 + tbl2.s13 + tbl2.s14 + tbl2.s15 + tbl2.s16 + tbl2.s17 + tbl2.s18 + tbl2.s19 + tbl2.s20 + tbl2.s21 + tbl2.s22 + tbl2.s23 + tbl2.s24 + tbl2.s25 + tbl2.s26 + tbl2.p27 + tbl2.s28 + tbl2.s29 + tbl2.s30 + tbl2.s31 + tbl2.s32 + tbl2.s33 + tbl2.s34 + tbl2.s35 + tbl2.sab + tbl2.sbc + tbl2.sac + tbl2.sa + tbl2.sb + tbl2.sc + tbl2.sbx1 AS super FROM(SELECT tbl1.*, CASE WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id IN(1,2) OR tbl1.ticket_id IN(3,4,5,6,7,8) THEN '1' WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id IN(1,2) THEN '2' WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id IN(1) THEN '3' WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id IN(1) THEN '4' WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id IN(1) THEN '5' WHEN tbl1.ticket_id = 2 AND tbl1.ticket_number != r.p1 THEN 2 ELSE 6 END AS position, CASE WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=1) WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id = 2 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=7) ELSE 0 END AS p1, CASE WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=2) ELSE 0 END AS p2, CASE WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=3) ELSE 0 END AS p3, CASE WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=4) ELSE 0 END AS p4, CASE WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=5) ELSE 0 END AS p5, CASE WHEN tbl1.ticket_number = r.p6 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p6, CASE WHEN tbl1.ticket_number = r.p7 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p7, CASE WHEN tbl1.ticket_number = r.p8 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p8, CASE WHEN tbl1.ticket_number = r.p9 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p9, CASE WHEN tbl1.ticket_number = r.p10 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p10, CASE WHEN tbl1.ticket_number = r.p11 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p11, CASE WHEN tbl1.ticket_number = r.p12 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p12, CASE WHEN tbl1.ticket_number = r.p13 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p13, CASE WHEN tbl1.ticket_number = r.p14 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p14, CASE WHEN tbl1.ticket_number = r.p15 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p15, CASE WHEN tbl1.ticket_number = r.p16 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p16, CASE WHEN tbl1.ticket_number = r.p17 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p17, CASE WHEN tbl1.ticket_number = r.p18 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p18, CASE WHEN tbl1.ticket_number = r.p19 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p19, CASE WHEN tbl1.ticket_number = r.p20 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p20, CASE WHEN tbl1.ticket_number = r.p21 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p21, CASE WHEN tbl1.ticket_number = r.p22 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p22, CASE WHEN tbl1.ticket_number = r.p23 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p23, CASE WHEN tbl1.ticket_number = r.p24 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p24, CASE WHEN tbl1.ticket_number = r.p25 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p25, CASE WHEN tbl1.ticket_number = r.p26 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p26, CASE WHEN tbl1.ticket_number = r.p27 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p27, CASE WHEN tbl1.ticket_number = r.p28 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p28, CASE WHEN tbl1.ticket_number = r.p29 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p29, CASE WHEN tbl1.ticket_number = r.p30 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p30, CASE WHEN tbl1.ticket_number = r.p31 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p31, CASE WHEN tbl1.ticket_number = r.p32 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p32, CASE WHEN tbl1.ticket_number = r.p33 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p33, CASE WHEN tbl1.ticket_number = r.p34 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p34, CASE WHEN tbl1.ticket_number = r.p35 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p35, CASE WHEN tbl1.ticket_name = 'ab' AND tbl1.ticket_number = LEFT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=13) ELSE 0 END AS ab, CASE WHEN tbl1.ticket_name = 'bc' AND tbl1.ticket_number = RIGHT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=14) ELSE 0 END AS bc, CASE WHEN tbl1.ticket_name = 'ac' AND tbl1.ticket_number = CONCAT(LEFT(r.p1, 1),RIGHT(r.p1, 1)) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=15) ELSE 0 END AS ac, CASE WHEN tbl1.ticket_name = 'a' AND tbl1.ticket_number = LEFT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=16) ELSE 0 END AS a, CASE WHEN tbl1.ticket_name = 'b' AND tbl1.ticket_number = substring(r.p1, 2, 1) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=17) ELSE 0 END AS b, CASE WHEN tbl1.ticket_name = 'c' AND tbl1.ticket_number = RIGHT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=18) ELSE 0 END AS c, CASE WHEN tbl1.ticket_id = 2 AND LOCATE(LEFT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(RIGHT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(substring(tbl1.ticket_number, 2, 1), r.p1) > 0 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=8) ELSE 0 END AS bx1, CASE WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=1) ELSE 0 END AS s1, CASE WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=2) ELSE 0 END AS s2, CASE WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=3) ELSE 0 END AS s3, CASE WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=4) ELSE 0 END AS s4, CASE WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=5) ELSE 0 END AS s5, CASE WHEN tbl1.ticket_number = r.p6 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s6, CASE WHEN tbl1.ticket_number = r.p7 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s7, CASE WHEN tbl1.ticket_number = r.p8 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s8, CASE WHEN tbl1.ticket_number = r.p9 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s9, CASE WHEN tbl1.ticket_number = r.p10 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s10, CASE WHEN tbl1.ticket_number = r.p11 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s11, CASE WHEN tbl1.ticket_number = r.p12 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s12, CASE WHEN tbl1.ticket_number = r.p13 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s13, CASE WHEN tbl1.ticket_number = r.p14 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s14, CASE WHEN tbl1.ticket_number = r.p15 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s15, CASE WHEN tbl1.ticket_number = r.p16 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s16, CASE WHEN tbl1.ticket_number = r.p17 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s17, CASE WHEN tbl1.ticket_number = r.p18 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s18, CASE WHEN tbl1.ticket_number = r.p19 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s19, CASE WHEN tbl1.ticket_number = r.p20 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s20, CASE WHEN tbl1.ticket_number = r.p21 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s21, CASE WHEN tbl1.ticket_number = r.p22 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s22, CASE WHEN tbl1.ticket_number = r.p23 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s23, CASE WHEN tbl1.ticket_number = r.p24 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s24, CASE WHEN tbl1.ticket_number = r.p25 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s25, CASE WHEN tbl1.ticket_number = r.p26 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s26, CASE WHEN tbl1.ticket_number = r.p27 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s27, CASE WHEN tbl1.ticket_number = r.p28 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s28, CASE WHEN tbl1.ticket_number = r.p29 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s29, CASE WHEN tbl1.ticket_number = r.p30 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s30, CASE WHEN tbl1.ticket_number = r.p31 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s31, CASE WHEN tbl1.ticket_number = r.p32 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s32, CASE WHEN tbl1.ticket_number = r.p33 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s33, CASE WHEN tbl1.ticket_number = r.p34 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s34, CASE WHEN tbl1.ticket_number = r.p35 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s35, CASE WHEN tbl1.ticket_name = 'ab' AND tbl1.ticket_number = LEFT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=13) ELSE 0 END AS sab, CASE WHEN tbl1.ticket_name = 'bc' AND tbl1.ticket_number = RIGHT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=14) ELSE 0 END AS sbc, CASE WHEN tbl1.ticket_name = 'ac' AND tbl1.ticket_number = CONCAT(LEFT(r.p1, 1),RIGHT(r.p1, 1)) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=15) ELSE 0 END AS sac, CASE WHEN tbl1.ticket_name = 'a' AND tbl1.ticket_number = LEFT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=16) ELSE 0 END AS sa, CASE WHEN tbl1.ticket_name = 'b' AND tbl1.ticket_number = substring(r.p1, 2, 1) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=17) ELSE 0 END AS sb, CASE WHEN tbl1.ticket_name = 'c' AND tbl1.ticket_number = RIGHT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=18) ELSE 0 END AS sc, CASE WHEN tbl1.ticket_id = 2 AND LOCATE(LEFT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(RIGHT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(substring(tbl1.ticket_number, 2, 1), r.p1) > 0 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=8) ELSE 0 END AS sbx1 FROM (SELECT r.id AS resultid, p.id AS playid, p.code AS playcode, o.id AS order_id, o.bill_number, o.play_date, o.ticket_id, u.id AS uid, u.name AS uname, o.ticket_number, t.name AS ticket_name, o.ticket_count, $ratecol*o.ticket_count AS purchase FROM orders o LEFT JOIN tickets t ON t.id = o.ticket_id LEFT JOIN users u ON o.user_id = u.id LEFT JOIN plays p ON p.id = o.play_id LEFT JOIN results r ON r.play_id = o.play_id AND r.play_date = o.play_date WHERE IF(? > 0, o.user_id = ?, 1) AND IF(? > 0, o.play_id = ?, 1) AND o.play_date BETWEEN ? AND ?) AS tbl1 LEFT JOIN schemes s ON s.ticket_id = tbl1.ticket_id LEFT JOIN results r ON r.id = tbl1.resultid GROUP BY tbl1.order_id) AS tbl2) AS tbl3 GROUP BY id", [$request->json('selected_user'), $request->json('selected_user'), $request->json('play_id'), $request->json('play_id'), $request->json('from_date'), $request->json('to_date')]));

        return response()->json([
            'status' => true,
            'record' => $data,
            'purchase' => $data->sum('purchase'),
            'winning' => $data->sum('won'),
            'balance' => $data->sum('balance'),
            'message' => 'success',
        ], 200);
    }

    /*function getWinningReport(Request $request)
    {
        $ratecol = 'o.user_rate';
        if ($request->json('role') == 'admin'):
            $ratecol = 'o.admin_rate';
        endif;
        if ($request->json('role') == 'leader'):
            $ratecol = 'o.leader_rate';
        endif;
        $data = collect(DB::select("SELECT tbl2.name, tbl2.purchase, tbl2.won, tbl2.purchase-tbl2.won AS balance, tbl2.super, tbl2.ticket_count FROM (SELECT tbl1.id, tbl1.name, IFNULL(SUM(tbl1.purchase), 0) AS purchase, SUM(tbl1.p1 + tbl1.p2 + tbl1.p3 + tbl1.p4 + tbl1.p5 + tbl1.p6 + tbl1.p7 + tbl1.p8 + tbl1.p9 + tbl1.p10 + tbl1.p11 + tbl1.p12 + tbl1.p13 + tbl1.p14 + tbl1.p15 + tbl1.p16 + tbl1.p17 + tbl1.p18 + tbl1.p19 + tbl1.p20 + tbl1.p21 + tbl1.p22 + tbl1.p23 + tbl1.p24 + tbl1.p25 + tbl1.p26 + tbl1.p27 + tbl1.p28 + tbl1.p29 + tbl1.p30 + tbl1.p31 + tbl1.p32 + tbl1.p33 + tbl1.p34 + tbl1.p35 + tbl1.ab + tbl1.bc + tbl1.ac + tbl1.a + tbl1.b + tbl1.c) AS won, SUM(tbl1.s1 + tbl1.s2 + tbl1.s3 + tbl1.s4 + tbl1.s5 + tbl1.s6 + tbl1.s7 + tbl1.s8 + tbl1.s9 + tbl1.s10 + tbl1.s11 + tbl1.s12 + tbl1.s13 + tbl1.s14 + tbl1.s15 + tbl1.s16 + tbl1.s17 + tbl1.s18 + tbl1.s19 + tbl1.s20 + tbl1.s21 + tbl1.s22 + tbl1.s23 + tbl1.s24 + tbl1.s25 + tbl1.s26 + tbl1.p27 + tbl1.s28 + tbl1.s29 + tbl1.s30 + tbl1.s31 + tbl1.s32 + tbl1.s33 + tbl1.s34 + tbl1.s35 + tbl1.sab + tbl1.sbc + tbl1.sac + tbl1.sa + tbl1.sb + tbl1.sc) AS super, SUM(tbl1.ticket_count) AS ticket_count FROM (SELECT u.id, u.name, o.ticket_name, o.ticket_number, $ratecol, o.ticket_count, s.amount, s.super, o.ticket_count * $ratecol AS purchase, CASE WHEN o.ticket_number = r.p1 THEN o.ticket_count*s.amount ELSE 0 END AS p1, CASE WHEN o.ticket_number = r.p2 THEN o.ticket_count*s.amount ELSE 0 END AS p2, CASE WHEN o.ticket_number = r.p3 THEN o.ticket_count*s.amount ELSE 0 END AS p3, CASE WHEN o.ticket_number = r.p4 THEN o.ticket_count*s.amount ELSE 0 END AS p4, CASE WHEN o.ticket_number = r.p5 THEN o.ticket_count*s.amount ELSE 0 END AS p5, CASE WHEN o.ticket_number = r.p6 THEN o.ticket_count*s.amount ELSE 0 END AS p6, CASE WHEN o.ticket_number = r.p7 THEN o.ticket_count*s.amount ELSE 0 END AS p7, CASE WHEN o.ticket_number = r.p8 THEN o.ticket_count*s.amount ELSE 0 END AS p8, CASE WHEN o.ticket_number = r.p9 THEN o.ticket_count*s.amount ELSE 0 END AS p9, CASE WHEN o.ticket_number = r.p10 THEN o.ticket_count*s.amount ELSE 0 END AS p10, CASE WHEN o.ticket_number = r.p11 THEN o.ticket_count*s.amount ELSE 0 END AS p11, CASE WHEN o.ticket_number = r.p12 THEN o.ticket_count*s.amount ELSE 0 END AS p12, CASE WHEN o.ticket_number = r.p13 THEN o.ticket_count*s.amount ELSE 0 END AS p13, CASE WHEN o.ticket_number = r.p14 THEN o.ticket_count*s.amount ELSE 0 END AS p14, CASE WHEN o.ticket_number = r.p15 THEN o.ticket_count*s.amount ELSE 0 END AS p15, CASE WHEN o.ticket_number = r.p16 THEN o.ticket_count*s.amount ELSE 0 END AS p16, CASE WHEN o.ticket_number = r.p17 THEN o.ticket_count*s.amount ELSE 0 END AS p17, CASE WHEN o.ticket_number = r.p18 THEN o.ticket_count*s.amount ELSE 0 END AS p18, CASE WHEN o.ticket_number = r.p19 THEN o.ticket_count*s.amount ELSE 0 END AS p19, CASE WHEN o.ticket_number = r.p20 THEN o.ticket_count*s.amount ELSE 0 END AS p20, CASE WHEN o.ticket_number = r.p21 THEN o.ticket_count*s.amount ELSE 0 END AS p21, CASE WHEN o.ticket_number = r.p22 THEN o.ticket_count*s.amount ELSE 0 END AS p22, CASE WHEN o.ticket_number = r.p23 THEN o.ticket_count*s.amount ELSE 0 END AS p23, CASE WHEN o.ticket_number = r.p24 THEN o.ticket_count*s.amount ELSE 0 END AS p24, CASE WHEN o.ticket_number = r.p25 THEN o.ticket_count*s.amount ELSE 0 END AS p25, CASE WHEN o.ticket_number = r.p26 THEN o.ticket_count*s.amount ELSE 0 END AS p26, CASE WHEN o.ticket_number = r.p27 THEN o.ticket_count*s.amount ELSE 0 END AS p27, CASE WHEN o.ticket_number = r.p28 THEN o.ticket_count*s.amount ELSE 0 END AS p28, CASE WHEN o.ticket_number = r.p29 THEN o.ticket_count*s.amount ELSE 0 END AS p29, CASE WHEN o.ticket_number = r.p30 THEN o.ticket_count*s.amount ELSE 0 END AS p30, CASE WHEN o.ticket_number = r.p31 THEN o.ticket_count*s.amount ELSE 0 END AS p31, CASE WHEN o.ticket_number = r.p32 THEN o.ticket_count*s.amount ELSE 0 END AS p32, CASE WHEN o.ticket_number = r.p33 THEN o.ticket_count*s.amount ELSE 0 END AS p33, CASE WHEN o.ticket_number = r.p34 THEN o.ticket_count*s.amount ELSE 0 END AS p34, CASE WHEN o.ticket_number = r.p35 THEN o.ticket_count*s.amount ELSE 0 END AS p35, CASE WHEN o.ticket_name = 'ab' AND o.ticket_number = LEFT(r.p1, 2) THEN o.ticket_count * s.amount ELSE 0 END AS ab, CASE WHEN o.ticket_name = 'bc' AND o.ticket_number = RIGHT(r.p1, 2) THEN o.ticket_count * s.amount ELSE 0 END AS bc, CASE WHEN o.ticket_name = 'ac' AND o.ticket_number = CONCAT(LEFT(r.p1, 1),RIGHT(r.p1, 1)) THEN o.ticket_count * s.amount ELSE 0 END AS ac, CASE WHEN o.ticket_name = 'a' AND o.ticket_number = LEFT(r.p1, 1) THEN o.ticket_count * s.amount ELSE 0 END AS a, CASE WHEN o.ticket_name = 'b' AND o.ticket_number = substring(r.p1, 2, 1) THEN o.ticket_count * s.amount ELSE 0 END AS b, CASE WHEN o.ticket_name = 'c' AND o.ticket_number = RIGHT(r.p1, 1) THEN o.ticket_count * s.amount ELSE 0 END AS c, CASE WHEN o.ticket_number = r.p1 THEN o.ticket_count*s.super ELSE 0 END AS s1, CASE WHEN o.ticket_number = r.p2 THEN o.ticket_count*s.super ELSE 0 END AS s2, CASE WHEN o.ticket_number = r.p3 THEN o.ticket_count*s.super ELSE 0 END AS s3, CASE WHEN o.ticket_number = r.p4 THEN o.ticket_count*s.super ELSE 0 END AS s4, CASE WHEN o.ticket_number = r.p5 THEN o.ticket_count*s.super ELSE 0 END AS s5, CASE WHEN o.ticket_number = r.p6 THEN o.ticket_count*s.super ELSE 0 END AS s6, CASE WHEN o.ticket_number = r.p7 THEN o.ticket_count*s.super ELSE 0 END AS s7, CASE WHEN o.ticket_number = r.p8 THEN o.ticket_count*s.super ELSE 0 END AS s8, CASE WHEN o.ticket_number = r.p9 THEN o.ticket_count*s.super ELSE 0 END AS s9, CASE WHEN o.ticket_number = r.p10 THEN o.ticket_count*s.super ELSE 0 END AS s10, CASE WHEN o.ticket_number = r.p11 THEN o.ticket_count*s.super ELSE 0 END AS s11, CASE WHEN o.ticket_number = r.p12 THEN o.ticket_count*s.super ELSE 0 END AS s12, CASE WHEN o.ticket_number = r.p13 THEN o.ticket_count*s.super ELSE 0 END AS s13, CASE WHEN o.ticket_number = r.p14 THEN o.ticket_count*s.super ELSE 0 END AS s14, CASE WHEN o.ticket_number = r.p15 THEN o.ticket_count*s.super ELSE 0 END AS s15, CASE WHEN o.ticket_number = r.p16 THEN o.ticket_count*s.super ELSE 0 END AS s16, CASE WHEN o.ticket_number = r.p17 THEN o.ticket_count*s.super ELSE 0 END AS s17, CASE WHEN o.ticket_number = r.p18 THEN o.ticket_count*s.super ELSE 0 END AS s18, CASE WHEN o.ticket_number = r.p19 THEN o.ticket_count*s.super ELSE 0 END AS s19, CASE WHEN o.ticket_number = r.p20 THEN o.ticket_count*s.super ELSE 0 END AS s20, CASE WHEN o.ticket_number = r.p21 THEN o.ticket_count*s.super ELSE 0 END AS s21, CASE WHEN o.ticket_number = r.p22 THEN o.ticket_count*s.super ELSE 0 END AS s22, CASE WHEN o.ticket_number = r.p23 THEN o.ticket_count*s.super ELSE 0 END AS s23, CASE WHEN o.ticket_number = r.p24 THEN o.ticket_count*s.super ELSE 0 END AS s24, CASE WHEN o.ticket_number = r.p25 THEN o.ticket_count*s.super ELSE 0 END AS s25, CASE WHEN o.ticket_number = r.p26 THEN o.ticket_count*s.super ELSE 0 END AS s26, CASE WHEN o.ticket_number = r.p27 THEN o.ticket_count*s.super ELSE 0 END AS s27, CASE WHEN o.ticket_number = r.p28 THEN o.ticket_count*s.super ELSE 0 END AS s28, CASE WHEN o.ticket_number = r.p29 THEN o.ticket_count*s.super ELSE 0 END AS s29, CASE WHEN o.ticket_number = r.p30 THEN o.ticket_count*s.super ELSE 0 END AS s30, CASE WHEN o.ticket_number = r.p31 THEN o.ticket_count*s.super ELSE 0 END AS s31, CASE WHEN o.ticket_number = r.p32 THEN o.ticket_count*s.super ELSE 0 END AS s32, CASE WHEN o.ticket_number = r.p33 THEN o.ticket_count*s.super ELSE 0 END AS s33, CASE WHEN o.ticket_number = r.p34 THEN o.ticket_count*s.super ELSE 0 END AS s34, CASE WHEN o.ticket_number = r.p35 THEN o.ticket_count*s.super ELSE 0 END AS s35, CASE WHEN o.ticket_name = 'ab' AND o.ticket_number = LEFT(r.p1, 2) THEN o.ticket_count * s.super ELSE 0 END AS sab, CASE WHEN o.ticket_name = 'bc' AND o.ticket_number = RIGHT(r.p1, 2) THEN o.ticket_count * s.super ELSE 0 END AS sbc, CASE WHEN o.ticket_name = 'ac' AND o.ticket_number = CONCAT(LEFT(r.p1, 1),RIGHT(r.p1, 1)) THEN o.ticket_count * s.super ELSE 0 END AS sac, CASE WHEN o.ticket_name = 'a' AND o.ticket_number = LEFT(r.p1, 1) THEN o.ticket_count * s.super ELSE 0 END AS sa, CASE WHEN o.ticket_name = 'b' AND o.ticket_number = substring(r.p1, 2, 1) THEN o.ticket_count * s.super ELSE 0 END AS sb, CASE WHEN o.ticket_name = 'c' AND o.ticket_number = RIGHT(r.p1, 1) THEN o.ticket_count * s.super ELSE 0 END AS sc FROM users u LEFT JOIN orders o on o.user_id = u.id LEFT JOIN results r ON o.play_id = r.play_id AND o.play_date = r.play_date LEFT JOIN schemes s ON s.ticket_id = o.ticket_id WHERE IF(? = 'user', o.user_id = ?, 1) AND IF(? > 0, o.play_id = ?, 1) AND o.play_date BETWEEN ? AND ? AND IF(?=1, o.ticket_id IN(6,7,8), 1) AND IF(?=2, o.ticket_id IN(3,4,5), 1) AND IF(?=3, o.ticket_id IN(1,2), 1) GROUP BY id, `name`, ticket_name, ticket_number, o.user_id) AS tbl1 GROUP BY id, `name`) AS tbl2", [$request->json('role'), $request->json('user_id'), $request->json('play_id'), $request->json('play_id'), $request->json('from_date'), $request->json('to_date'), $request->json('option'), $request->json('option'), $request->json('option')]));
        return response()->json([
            'status' => true,
            'total' => $data->sum('won'),
            'amount' => $data->sum('won') - $data->sum('super'),
            'count' => $data->sum('ticket_count'),
            'super' => $data->sum('super'),
            'message' => 'success',
        ], 200);
    }*/

    function getWinningDetailsReport(Request $request)
    {
        $ratecol = 'o.user_rate';
        if ($request->json('role') == 'admin'):
            $ratecol = 'o.admin_rate';
        endif;
        if ($request->json('role') == 'leader'):
            $ratecol = 'o.leader_rate';
        endif;
        $data = collect(DB::select("SELECT tbl3.id AS id, tbl3.name AS name, SUM(tbl3.ticket_count) AS ticket_count, SUM(tbl3.won) AS won, SUM(tbl3.super) AS super, SUM(tbl3.won) - SUM(tbl3.super) AS amount FROM (SELECT tbl2.uid AS id, tbl2.uname AS name, tbl2.bill_number, tbl2.ticket_name, tbl2.ticket_number, tbl2.ticket_count, tbl2.position, tbl2.purchase, tbl2.p1 + tbl2.p2 + tbl2.p3 + tbl2.p4 + tbl2.p5 + tbl2.p6 + tbl2.p7 + tbl2.p8 + tbl2.p9 + tbl2.p10 + tbl2.p11 + tbl2.p12 + tbl2.p13 + tbl2.p14 + tbl2.p15 + tbl2.p16 + tbl2.p17 + tbl2.p18 + tbl2.p19 + tbl2.p20 + tbl2.p21 + tbl2.p22 + tbl2.p23 + tbl2.p24 + tbl2.p25 + tbl2.p26 + tbl2.p27 + tbl2.p28 + tbl2.p29 + tbl2.p30 + tbl2.p31 + tbl2.p32 + tbl2.p33 + tbl2.p34 + tbl2.p35 + tbl2.ab + tbl2.bc + tbl2.ac + tbl2.a + tbl2.b + tbl2.c + tbl2.bx1 AS won, tbl2.s1 + tbl2.s2 + tbl2.s3 + tbl2.s4 + tbl2.s5 + tbl2.s6 + tbl2.s7 + tbl2.s8 + tbl2.s9 + tbl2.s10 + tbl2.s11 + tbl2.s12 + tbl2.s13 + tbl2.s14 + tbl2.s15 + tbl2.s16 + tbl2.s17 + tbl2.s18 + tbl2.s19 + tbl2.s20 + tbl2.s21 + tbl2.s22 + tbl2.s23 + tbl2.s24 + tbl2.s25 + tbl2.s26 + tbl2.p27 + tbl2.s28 + tbl2.s29 + tbl2.s30 + tbl2.s31 + tbl2.s32 + tbl2.s33 + tbl2.s34 + tbl2.s35 + tbl2.sab + tbl2.sbc + tbl2.sac + tbl2.sa + tbl2.sb + tbl2.sc + tbl2.sbx1 AS super FROM(SELECT tbl1.*, CASE WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id IN(1,2) OR tbl1.ticket_id IN(3,4,5,6,7,8) THEN '1' WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id IN(1,2) THEN '2' WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id IN(1) THEN '3' WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id IN(1) THEN '4' WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id IN(1) THEN '5' WHEN tbl1.ticket_id = 2 AND tbl1.ticket_number != r.p1 THEN 2 ELSE 6 END AS position, CASE WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=1) WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id = 2 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=7) ELSE 0 END AS p1, CASE WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=2) ELSE 0 END AS p2, CASE WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=3) ELSE 0 END AS p3, CASE WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=4) ELSE 0 END AS p4, CASE WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=5) ELSE 0 END AS p5, CASE WHEN tbl1.ticket_number = r.p6 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p6, CASE WHEN tbl1.ticket_number = r.p7 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p7, CASE WHEN tbl1.ticket_number = r.p8 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p8, CASE WHEN tbl1.ticket_number = r.p9 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p9, CASE WHEN tbl1.ticket_number = r.p10 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p10, CASE WHEN tbl1.ticket_number = r.p11 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p11, CASE WHEN tbl1.ticket_number = r.p12 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p12, CASE WHEN tbl1.ticket_number = r.p13 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p13, CASE WHEN tbl1.ticket_number = r.p14 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p14, CASE WHEN tbl1.ticket_number = r.p15 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p15, CASE WHEN tbl1.ticket_number = r.p16 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p16, CASE WHEN tbl1.ticket_number = r.p17 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p17, CASE WHEN tbl1.ticket_number = r.p18 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p18, CASE WHEN tbl1.ticket_number = r.p19 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p19, CASE WHEN tbl1.ticket_number = r.p20 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p20, CASE WHEN tbl1.ticket_number = r.p21 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p21, CASE WHEN tbl1.ticket_number = r.p22 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p22, CASE WHEN tbl1.ticket_number = r.p23 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p23, CASE WHEN tbl1.ticket_number = r.p24 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p24, CASE WHEN tbl1.ticket_number = r.p25 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p25, CASE WHEN tbl1.ticket_number = r.p26 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p26, CASE WHEN tbl1.ticket_number = r.p27 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p27, CASE WHEN tbl1.ticket_number = r.p28 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p28, CASE WHEN tbl1.ticket_number = r.p29 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p29, CASE WHEN tbl1.ticket_number = r.p30 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p30, CASE WHEN tbl1.ticket_number = r.p31 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p31, CASE WHEN tbl1.ticket_number = r.p32 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p32, CASE WHEN tbl1.ticket_number = r.p33 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p33, CASE WHEN tbl1.ticket_number = r.p34 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p34, CASE WHEN tbl1.ticket_number = r.p35 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p35, CASE WHEN tbl1.ticket_name = 'ab' AND tbl1.ticket_number = LEFT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=13) ELSE 0 END AS ab, CASE WHEN tbl1.ticket_name = 'bc' AND tbl1.ticket_number = RIGHT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=14) ELSE 0 END AS bc, CASE WHEN tbl1.ticket_name = 'ac' AND tbl1.ticket_number = CONCAT(LEFT(r.p1, 1),RIGHT(r.p1, 1)) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=15) ELSE 0 END AS ac, CASE WHEN tbl1.ticket_name = 'a' AND tbl1.ticket_number = LEFT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=16) ELSE 0 END AS a, CASE WHEN tbl1.ticket_name = 'b' AND tbl1.ticket_number = substring(r.p1, 2, 1) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=17) ELSE 0 END AS b, CASE WHEN tbl1.ticket_name = 'c' AND tbl1.ticket_number = RIGHT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=18) ELSE 0 END AS c, CASE WHEN tbl1.ticket_id = 2 AND LOCATE(LEFT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(RIGHT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(substring(tbl1.ticket_number, 2, 1), r.p1) > 0 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=8) ELSE 0 END AS bx1, CASE WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=1) ELSE 0 END AS s1, CASE WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=2) ELSE 0 END AS s2, CASE WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=3) ELSE 0 END AS s3, CASE WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=4) ELSE 0 END AS s4, CASE WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=5) ELSE 0 END AS s5, CASE WHEN tbl1.ticket_number = r.p6 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s6, CASE WHEN tbl1.ticket_number = r.p7 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s7, CASE WHEN tbl1.ticket_number = r.p8 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s8, CASE WHEN tbl1.ticket_number = r.p9 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s9, CASE WHEN tbl1.ticket_number = r.p10 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s10, CASE WHEN tbl1.ticket_number = r.p11 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s11, CASE WHEN tbl1.ticket_number = r.p12 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s12, CASE WHEN tbl1.ticket_number = r.p13 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s13, CASE WHEN tbl1.ticket_number = r.p14 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s14, CASE WHEN tbl1.ticket_number = r.p15 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s15, CASE WHEN tbl1.ticket_number = r.p16 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s16, CASE WHEN tbl1.ticket_number = r.p17 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s17, CASE WHEN tbl1.ticket_number = r.p18 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s18, CASE WHEN tbl1.ticket_number = r.p19 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s19, CASE WHEN tbl1.ticket_number = r.p20 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s20, CASE WHEN tbl1.ticket_number = r.p21 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s21, CASE WHEN tbl1.ticket_number = r.p22 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s22, CASE WHEN tbl1.ticket_number = r.p23 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s23, CASE WHEN tbl1.ticket_number = r.p24 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s24, CASE WHEN tbl1.ticket_number = r.p25 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s25, CASE WHEN tbl1.ticket_number = r.p26 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s26, CASE WHEN tbl1.ticket_number = r.p27 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s27, CASE WHEN tbl1.ticket_number = r.p28 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s28, CASE WHEN tbl1.ticket_number = r.p29 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s29, CASE WHEN tbl1.ticket_number = r.p30 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s30, CASE WHEN tbl1.ticket_number = r.p31 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s31, CASE WHEN tbl1.ticket_number = r.p32 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s32, CASE WHEN tbl1.ticket_number = r.p33 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s33, CASE WHEN tbl1.ticket_number = r.p34 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s34, CASE WHEN tbl1.ticket_number = r.p35 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s35, CASE WHEN tbl1.ticket_name = 'ab' AND tbl1.ticket_number = LEFT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=13) ELSE 0 END AS sab, CASE WHEN tbl1.ticket_name = 'bc' AND tbl1.ticket_number = RIGHT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=14) ELSE 0 END AS sbc, CASE WHEN tbl1.ticket_name = 'ac' AND tbl1.ticket_number = CONCAT(LEFT(r.p1, 1),RIGHT(r.p1, 1)) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=15) ELSE 0 END AS sac, CASE WHEN tbl1.ticket_name = 'a' AND tbl1.ticket_number = LEFT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=16) ELSE 0 END AS sa, CASE WHEN tbl1.ticket_name = 'b' AND tbl1.ticket_number = substring(r.p1, 2, 1) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=17) ELSE 0 END AS sb, CASE WHEN tbl1.ticket_name = 'c' AND tbl1.ticket_number = RIGHT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=18) ELSE 0 END AS sc, CASE WHEN tbl1.ticket_id = 2 AND LOCATE(LEFT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(RIGHT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(substring(tbl1.ticket_number, 2, 1), r.p1) > 0 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=8) ELSE 0 END AS sbx1 FROM (SELECT r.id AS resultid, p.id AS playid, p.code AS playcode, o.id AS order_id, o.bill_number, o.play_date, o.ticket_id, u.id AS uid, u.name AS uname, o.ticket_number, t.name AS ticket_name, o.ticket_count, $ratecol*o.ticket_count AS purchase FROM orders o LEFT JOIN tickets t ON t.id = o.ticket_id LEFT JOIN users u ON o.user_id = u.id LEFT JOIN plays p ON p.id = o.play_id LEFT JOIN results r ON r.play_id = o.play_id AND r.play_date = o.play_date WHERE IF(? = 'user', o.user_id = ?, 1) AND IF(? > 0, o.play_id = ?, 1) AND o.play_date BETWEEN ? AND ? AND IF(?=1, o.ticket_id IN(6,7,8), 1) AND IF(?=2, o.ticket_id IN(3,4,5), 1) AND IF(?=3, o.ticket_id IN(1,2), 1)) AS tbl1 LEFT JOIN schemes s ON s.ticket_id = tbl1.ticket_id LEFT JOIN results r ON r.id = tbl1.resultid GROUP BY tbl1.order_id) AS tbl2 HAVING won > 0) AS tbl3 GROUP BY id", [$request->json('role'), $request->json('user_id'), $request->json('play_id'), $request->json('play_id'), $request->json('from_date'), $request->json('to_date'), $request->json('option'), $request->json('option'), $request->json('option')]));
        return response()->json([
            'status' => true,
            'record' => $data,
            'total' => $data->sum('amount') + $data->sum('super'),
            'count' => $data->sum('ticket_count'),
            'message' => 'success',
        ], 200);
    }

    function getWinningDetailsBillReport(Request $request)
    {
        $ratecol = 't.user_rate';
        if ($request->json('role') == 'admin'):
            $ratecol = 't.admin_rate';
        endif;
        if ($request->json('role') == 'leader'):
            $ratecol = 't.leader_rate';
        endif;
        $data = collect(DB::select("SELECT tbl2.uid as id, tbl2.uname AS name, tbl2.bill_number, tbl2.ticket_name, tbl2.ticket_number, tbl2.ticket_count, tbl2.position, tbl2.purchase, (tbl2.p1 + tbl2.p2 + tbl2.p3 + tbl2.p4 + tbl2.p5 + tbl2.p6 + tbl2.p7 + tbl2.p8 + tbl2.p9 + tbl2.p10 + tbl2.p11 + tbl2.p12 + tbl2.p13 + tbl2.p14 + tbl2.p15 + tbl2.p16 + tbl2.p17 + tbl2.p18 + tbl2.p19 + tbl2.p20 + tbl2.p21 + tbl2.p22 + tbl2.p23 + tbl2.p24 + tbl2.p25 + tbl2.p26 + tbl2.p27 + tbl2.p28 + tbl2.p29 + tbl2.p30 + tbl2.p31 + tbl2.p32 + tbl2.p33 + tbl2.p34 + tbl2.p35 + tbl2.ab + tbl2.bc + tbl2.ac + tbl2.a + tbl2.b + tbl2.c + tbl2.bx1) AS won, (tbl2.s1 + tbl2.s2 + tbl2.s3 + tbl2.s4 + tbl2.s5 + tbl2.s6 + tbl2.s7 + tbl2.s8 + tbl2.s9 + tbl2.s10 + tbl2.s11 + tbl2.s12 + tbl2.s13 + tbl2.s14 + tbl2.s15 + tbl2.s16 + tbl2.s17 + tbl2.s18 + tbl2.s19 + tbl2.s20 + tbl2.s21 + tbl2.s22 + tbl2.s23 + tbl2.s24 + tbl2.s25 + tbl2.s26 + tbl2.p27 + tbl2.s28 + tbl2.s29 + tbl2.s30 + tbl2.s31 + tbl2.s32 + tbl2.s33 + tbl2.s34 + tbl2.s35 + tbl2.sab + tbl2.sbc + tbl2.sac + tbl2.sa + tbl2.sb + tbl2.sc + tbl2.sbx1) AS super FROM(SELECT tbl1.*, CASE WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id IN(1,2) OR tbl1.ticket_id IN(3,4,5,6,7,8) THEN '1' WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id IN(1,2) THEN '2' WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id IN(1) THEN '3' WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id IN(1) THEN '4' WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id IN(1) THEN '5' WHEN tbl1.ticket_id = 2 AND tbl1.ticket_number != r.p1 THEN 2 ELSE 6 END AS position, CASE WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=1) WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id = 2 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=7) ELSE 0 END AS p1, CASE WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=2) ELSE 0 END AS p2, CASE WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=3) ELSE 0 END AS p3, CASE WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=4) ELSE 0 END AS p4, CASE WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=5) ELSE 0 END AS p5, CASE WHEN tbl1.ticket_number = r.p6 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p6, CASE WHEN tbl1.ticket_number = r.p7 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p7, CASE WHEN tbl1.ticket_number = r.p8 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p8, CASE WHEN tbl1.ticket_number = r.p9 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p9, CASE WHEN tbl1.ticket_number = r.p10 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p10, CASE WHEN tbl1.ticket_number = r.p11 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p11, CASE WHEN tbl1.ticket_number = r.p12 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p12, CASE WHEN tbl1.ticket_number = r.p13 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p13, CASE WHEN tbl1.ticket_number = r.p14 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p14, CASE WHEN tbl1.ticket_number = r.p15 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p15, CASE WHEN tbl1.ticket_number = r.p16 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p16, CASE WHEN tbl1.ticket_number = r.p17 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p17, CASE WHEN tbl1.ticket_number = r.p18 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p18, CASE WHEN tbl1.ticket_number = r.p19 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p19, CASE WHEN tbl1.ticket_number = r.p20 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p20, CASE WHEN tbl1.ticket_number = r.p21 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p21, CASE WHEN tbl1.ticket_number = r.p22 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p22, CASE WHEN tbl1.ticket_number = r.p23 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p23, CASE WHEN tbl1.ticket_number = r.p24 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p24, CASE WHEN tbl1.ticket_number = r.p25 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p25, CASE WHEN tbl1.ticket_number = r.p26 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p26, CASE WHEN tbl1.ticket_number = r.p27 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p27, CASE WHEN tbl1.ticket_number = r.p28 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p28, CASE WHEN tbl1.ticket_number = r.p29 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p29, CASE WHEN tbl1.ticket_number = r.p30 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p30, CASE WHEN tbl1.ticket_number = r.p31 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p31, CASE WHEN tbl1.ticket_number = r.p32 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p32, CASE WHEN tbl1.ticket_number = r.p33 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p33, CASE WHEN tbl1.ticket_number = r.p34 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p34, CASE WHEN tbl1.ticket_number = r.p35 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=6) ELSE 0 END AS p35, CASE WHEN tbl1.ticket_name = 'ab' AND tbl1.ticket_number = LEFT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=13) ELSE 0 END AS ab, CASE WHEN tbl1.ticket_name = 'bc' AND tbl1.ticket_number = RIGHT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=14) ELSE 0 END AS bc, CASE WHEN tbl1.ticket_name = 'ac' AND tbl1.ticket_number = CONCAT(LEFT(r.p1, 1),RIGHT(r.p1, 1)) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=15) ELSE 0 END AS ac, CASE WHEN tbl1.ticket_name = 'a' AND tbl1.ticket_number = LEFT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=16) ELSE 0 END AS a, CASE WHEN tbl1.ticket_name = 'b' AND tbl1.ticket_number = substring(r.p1, 2, 1) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=17) ELSE 0 END AS b, CASE WHEN tbl1.ticket_name = 'c' AND tbl1.ticket_number = RIGHT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=18) ELSE 0 END AS c, CASE WHEN tbl1.ticket_id = 2 AND LOCATE(LEFT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(RIGHT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(substring(tbl1.ticket_number, 2, 1), r.p1) > 0 THEN tbl1.ticket_count * (SELECT IFNULL(amount, 0) FROM schemes WHERE id=8) ELSE 0 END AS bx1, CASE WHEN tbl1.ticket_number = r.p1 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=1) ELSE 0 END AS s1, CASE WHEN tbl1.ticket_number = r.p2 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=2) ELSE 0 END AS s2, CASE WHEN tbl1.ticket_number = r.p3 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=3) ELSE 0 END AS s3, CASE WHEN tbl1.ticket_number = r.p4 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=4) ELSE 0 END AS s4, CASE WHEN tbl1.ticket_number = r.p5 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=5) ELSE 0 END AS s5, CASE WHEN tbl1.ticket_number = r.p6 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s6, CASE WHEN tbl1.ticket_number = r.p7 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s7, CASE WHEN tbl1.ticket_number = r.p8 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s8, CASE WHEN tbl1.ticket_number = r.p9 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s9, CASE WHEN tbl1.ticket_number = r.p10 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s10, CASE WHEN tbl1.ticket_number = r.p11 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s11, CASE WHEN tbl1.ticket_number = r.p12 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s12, CASE WHEN tbl1.ticket_number = r.p13 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s13, CASE WHEN tbl1.ticket_number = r.p14 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s14, CASE WHEN tbl1.ticket_number = r.p15 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s15, CASE WHEN tbl1.ticket_number = r.p16 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s16, CASE WHEN tbl1.ticket_number = r.p17 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s17, CASE WHEN tbl1.ticket_number = r.p18 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s18, CASE WHEN tbl1.ticket_number = r.p19 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s19, CASE WHEN tbl1.ticket_number = r.p20 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s20, CASE WHEN tbl1.ticket_number = r.p21 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s21, CASE WHEN tbl1.ticket_number = r.p22 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s22, CASE WHEN tbl1.ticket_number = r.p23 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s23, CASE WHEN tbl1.ticket_number = r.p24 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s24, CASE WHEN tbl1.ticket_number = r.p25 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s25, CASE WHEN tbl1.ticket_number = r.p26 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s26, CASE WHEN tbl1.ticket_number = r.p27 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s27, CASE WHEN tbl1.ticket_number = r.p28 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s28, CASE WHEN tbl1.ticket_number = r.p29 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s29, CASE WHEN tbl1.ticket_number = r.p30 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s30, CASE WHEN tbl1.ticket_number = r.p31 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s31, CASE WHEN tbl1.ticket_number = r.p32 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s32, CASE WHEN tbl1.ticket_number = r.p33 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s33, CASE WHEN tbl1.ticket_number = r.p34 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s34, CASE WHEN tbl1.ticket_number = r.p35 AND tbl1.ticket_id = 1 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=6) ELSE 0 END AS s35, CASE WHEN tbl1.ticket_name = 'ab' AND tbl1.ticket_number = LEFT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=13) ELSE 0 END AS sab, CASE WHEN tbl1.ticket_name = 'bc' AND tbl1.ticket_number = RIGHT(r.p1, 2) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=14) ELSE 0 END AS sbc, CASE WHEN tbl1.ticket_name = 'ac' AND tbl1.ticket_number = CONCAT(LEFT(r.p1, 1),RIGHT(r.p1, 1)) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=15) ELSE 0 END AS sac, CASE WHEN tbl1.ticket_name = 'a' AND tbl1.ticket_number = LEFT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=16) ELSE 0 END AS sa, CASE WHEN tbl1.ticket_name = 'b' AND tbl1.ticket_number = substring(r.p1, 2, 1) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=17) ELSE 0 END AS sb, CASE WHEN tbl1.ticket_name = 'c' AND tbl1.ticket_number = RIGHT(r.p1, 1) THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=18) ELSE 0 END AS sc, CASE WHEN tbl1.ticket_id = 2 AND LOCATE(LEFT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(RIGHT(tbl1.ticket_number, 1), r.p1) > 0 AND LOCATE(substring(tbl1.ticket_number, 2, 1), r.p1) > 0 THEN tbl1.ticket_count * (SELECT IFNULL(super, 0) FROM schemes WHERE id=8) ELSE 0 END AS sbx1 FROM (SELECT r.id AS resultid, p.id AS playid, p.code AS playcode, o.id AS order_id, o.bill_number, o.play_date, o.ticket_id, u.id AS uid, u.name AS uname, o.ticket_number, t.name AS ticket_name, o.ticket_count, $ratecol*o.ticket_count AS purchase FROM orders o LEFT JOIN tickets t ON t.id = o.ticket_id LEFT JOIN users u ON o.user_id = u.id LEFT JOIN plays p ON p.id = o.play_id LEFT JOIN results r ON r.play_id = o.play_id AND r.play_date = o.play_date WHERE IF(? > 0, o.user_id = ?, 1) AND IF(? > 0, o.play_id = ?, 1) AND o.play_date BETWEEN ? AND ? AND IF(?=1, o.ticket_id IN(6,7,8), 1) AND IF(?=2, o.ticket_id IN(3,4,5), 1) AND IF(?=3, o.ticket_id IN(1,2), 1)) AS tbl1 LEFT JOIN schemes s ON s.ticket_id = tbl1.ticket_id LEFT JOIN results r ON r.id = tbl1.resultid GROUP BY tbl1.order_id) AS tbl2 HAVING won > 0", [$request->json('selectedUser'), $request->json('selectedUser'), $request->json('play_id'), $request->json('play_id'), $request->json('from_date'), $request->json('to_date'), $request->json('option'), $request->json('option'), $request->json('option')]));

        $user = User::where('id', $request->json('selectedUser'))->first();
        return response()->json([
            'status' => true,
            'record' => $data,
            'total' => $data->sum('won') + $data->sum('super'),
            'count' => $data->sum('ticket_count'),
            'amount' => $data->sum('won'),
            'super' => $data->sum('super'),
            'stockist' => User::where('id', $user->parent_id ?? 0)->first()?->name ?? 'Na',
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
