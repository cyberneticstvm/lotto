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
        $role = $request['role'];
        $uid = $request['user_id'];
        if ($role != 'leader'):
            $uid = User::where('id', $uid)->first()->parent_id;
        endif;
        $billnumber = Order::max('bill_number') + 1;
        $data = [];
        foreach ($items as $key => $item):
            $data[] = [
                'bill_number' => $billnumber ?? 1,
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
            return $q->where('user_id', $request->json('user_id'));
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
        $data = Order::selectRaw("SUM(orders.ticket_count) AS ticket_count, SUM($ratecol) * SUM(orders.ticket_count) AS total")->whereBetween('play_date', [$request->json('from_date'), $request->json('to_date')])->when($request->json('play_id') > 0, function ($q) use ($request) {
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
        })->groupBy('ticket_name')->get();
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
        $record = collect(DB::select("SELECT tbl1.id, tbl1.name, SUM(tbl1.ticket_count) AS ticket_count, SUM(tbl1.total) AS total FROM (SELECT u.id, u.name, o.ticket_name, SUM(o.ticket_count) AS ticket_count, SUM($ratecol) * SUM(o.ticket_count) AS total FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.play_date BETWEEN ? AND ? AND IF(? > 0, o.play_id = ?, 1) AND IF(? > 0, o.ticket_id = ?, 1) AND IF (? != '', o.ticket_number = ?, 1) AND IF(? != '', o.bill_number = ?, 1) AND IF(? = 'leader', o.parent_id = ?, 1) AND IF(? = 'user', o.user_id = ?, 1) AND IF(? > 0 , o.user_id = ?, 1) GROUP BY id, `name`, ticket_name) AS tbl1 GROUP BY id, `name`", [$request->json('from_date'), $request->json('to_date'), $request->json('play_id'), $request->json('play_id'), $request->json('ticket_id'), $request->json('ticket_id'), $request->json('ticket_number'), $request->json('ticket_number'), $request->json('bill_number'), $request->json('bill_number'), $request->json('role'), $request->json('user_id'), $request->json('role'), $request->json('user_id'), $request->json('salesUser'), $request->json('user_id')]));

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
        $data = collect(DB::select("SELECT tbl1.id, tbl1.name, tbl1.bill_number, tbl1.play_date, SUM(tbl1.ticket_count) AS ticket_count, SUM(tbl1.total) AS total FROM (SELECT u.id, u.name, o.bill_number, o.ticket_name, DATE_FORMAT(o.play_date, '%d-%b-%Y') AS play_date, SUM(o.ticket_count) AS ticket_count, SUM($ratecol) * SUM(o.ticket_count) AS total FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.play_date BETWEEN ? AND ? AND IF(? > 0, o.play_id = ?, 1) AND IF(? > 0, o.ticket_id = ?, 1) AND IF (? != '', o.ticket_number = ?, 1) AND IF(? != '', o.bill_number = ?, 1) AND o.user_id = ? GROUP BY id, `name`, ticket_name) AS tbl1 GROUP BY bill_number", [$request->json('from_date'), $request->json('to_date'), $request->json('play_id'), $request->json('play_id'), $request->json('ticket_id'), $request->json('ticket_id'), $request->json('ticket_number'), $request->json('ticket_number'), $request->json('bill_number'), $request->json('bill_number'), $request->json('selectedUser')]));


        /*$data = Order::leftJoin('users as u', 'orders.user_id', 'u.id')->selectRaw("u.id, u.name, orders.bill_number, orders.play_date, SUM(orders.ticket_count) AS ticket_count, $ratecol * orders.ticket_count AS total")->whereBetween('orders.play_date', [$request->json('from_date'), $request->json('to_date')])->when($request->json('play_id') > 0, function ($q) use ($request) {
            return $q->where('orders.play_id', $request->json('play_id'));
        })->when($request->json('ticket_id') > 0, function ($q) use ($request) {
            return $q->where('orders.ticket_id', $request->json('ticket_id'));
        })->when($request->json('ticket_number') != null, function ($q) use ($request) {
            return $q->where('orders.ticket_number', $request->json('ticket_number'));
        })->when($request->json('bill_number') != null, function ($q) use ($request) {
            return $q->where('orders.bill_number', $request->json('bill_number'));
        })->where('orders.user_id', $request->json('selectedUser'))->groupBy('id', 'name', 'bill_number', 'play_date')->get();*/
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
        $data = Order::where('bill_number', $request->json('bill_number'))->selectRaw("id, play_date, ticket_number, play_code, ticket_count, $ratecol * ticket_count as price")->get();
        return response()->json([
            'status' => true,
            'record' => $data,
            'message' => 'success',
        ], 200);
    }

    function deleteBill(Request $request)
    {
        Order::where('bill_number', $request->json('bill_number'))->delete();
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
        $data = collect(DB::select("SELECT tbl2.name, tbl2.purchase, tbl2.won, tbl2.purchase-tbl2.won AS balance FROM (SELECT tbl1.id, tbl1.name, IFNULL(SUM(tbl1.purchase), 0) AS purchase, SUM(tbl1.p1 + tbl1.p2 + tbl1.p3 + tbl1.p4 + tbl1.p5 + tbl1.p6 + tbl1.p7 + tbl1.p8 + tbl1.p9 + tbl1.p10 + tbl1.p11 + tbl1.p12 + tbl1.p13 + tbl1.p14 + tbl1.p15 + tbl1.p16 + tbl1.p17 + tbl1.p18 + tbl1.p19 + tbl1.p20 + tbl1.p21 + tbl1.p22 + tbl1.p23 + tbl1.p24 + tbl1.p25 + tbl1.p26 + tbl1.p28 + tbl1.p29 + tbl1.p30 + tbl1.p31 + tbl1.p32 + tbl1.p33 + tbl1.p34 + tbl1.p35 + tbl1.ab + tbl1.bc + tbl1.ac + tbl1.a + tbl1.b + tbl1.c) AS won FROM (SELECT u.id, u.name, o.ticket_name, o.ticket_number, $ratecol, o.ticket_count, s.amount, s.super, o.ticket_count * $ratecol AS purchase, CASE WHEN o.ticket_number = r.p1 THEN o.ticket_count*s.amount ELSE 0 END AS p1, CASE WHEN o.ticket_number = r.p2 THEN o.ticket_count*s.amount ELSE 0 END AS p2, CASE WHEN o.ticket_number = r.p3 THEN o.ticket_count*s.amount ELSE 0 END AS p3, CASE WHEN o.ticket_number = r.p4 THEN o.ticket_count*s.amount ELSE 0 END AS p4, CASE WHEN o.ticket_number = r.p5 THEN o.ticket_count*s.amount ELSE 0 END AS p5, CASE WHEN o.ticket_number = r.p6 THEN o.ticket_count*s.amount ELSE 0 END AS p6, CASE WHEN o.ticket_number = r.p7 THEN o.ticket_count*s.amount ELSE 0 END AS p7, CASE WHEN o.ticket_number = r.p8 THEN o.ticket_count*s.amount ELSE 0 END AS p8, CASE WHEN o.ticket_number = r.p9 THEN o.ticket_count*s.amount ELSE 0 END AS p9, CASE WHEN o.ticket_number = r.p10 THEN o.ticket_count*s.amount ELSE 0 END AS p10, CASE WHEN o.ticket_number = r.p11 THEN o.ticket_count*s.amount ELSE 0 END AS p11, CASE WHEN o.ticket_number = r.p12 THEN o.ticket_count*s.amount ELSE 0 END AS p12, CASE WHEN o.ticket_number = r.p13 THEN o.ticket_count*s.amount ELSE 0 END AS p13, CASE WHEN o.ticket_number = r.p14 THEN o.ticket_count*s.amount ELSE 0 END AS p14, CASE WHEN o.ticket_number = r.p15 THEN o.ticket_count*s.amount ELSE 0 END AS p15, CASE WHEN o.ticket_number = r.p16 THEN o.ticket_count*s.amount ELSE 0 END AS p16, CASE WHEN o.ticket_number = r.p17 THEN o.ticket_count*s.amount ELSE 0 END AS p17, CASE WHEN o.ticket_number = r.p18 THEN o.ticket_count*s.amount ELSE 0 END AS p18, CASE WHEN o.ticket_number = r.p19 THEN o.ticket_count*s.amount ELSE 0 END AS p19, CASE WHEN o.ticket_number = r.p20 THEN o.ticket_count*s.amount ELSE 0 END AS p20, CASE WHEN o.ticket_number = r.p21 THEN o.ticket_count*s.amount ELSE 0 END AS p21, CASE WHEN o.ticket_number = r.p22 THEN o.ticket_count*s.amount ELSE 0 END AS p22, CASE WHEN o.ticket_number = r.p23 THEN o.ticket_count*s.amount ELSE 0 END AS p23, CASE WHEN o.ticket_number = r.p24 THEN o.ticket_count*s.amount ELSE 0 END AS p24, CASE WHEN o.ticket_number = r.p25 THEN o.ticket_count*s.amount ELSE 0 END AS p25, CASE WHEN o.ticket_number = r.p26 THEN o.ticket_count*s.amount ELSE 0 END AS p26, CASE WHEN o.ticket_number = r.p27 THEN o.ticket_count*s.amount ELSE 0 END AS p27, CASE WHEN o.ticket_number = r.p28 THEN o.ticket_count*s.amount ELSE 0 END AS p28, CASE WHEN o.ticket_number = r.p29 THEN o.ticket_count*s.amount ELSE 0 END AS p29, CASE WHEN o.ticket_number = r.p30 THEN o.ticket_count*s.amount ELSE 0 END AS p30, CASE WHEN o.ticket_number = r.p31 THEN o.ticket_count*s.amount ELSE 0 END AS p31, CASE WHEN o.ticket_number = r.p32 THEN o.ticket_count*s.amount ELSE 0 END AS p32, CASE WHEN o.ticket_number = r.p33 THEN o.ticket_count*s.amount ELSE 0 END AS p33, CASE WHEN o.ticket_number = r.p34 THEN o.ticket_count*s.amount ELSE 0 END AS p34, CASE WHEN o.ticket_number = r.p35 THEN o.ticket_count*s.amount ELSE 0 END AS p35, CASE WHEN o.ticket_name = 'ab' AND o.ticket_number = LEFT(r.p1, 2) THEN o.ticket_count * s.amount ELSE 0 END AS ab, CASE WHEN o.ticket_name = 'bc' AND o.ticket_number = RIGHT(r.p1, 2) THEN o.ticket_count * s.amount ELSE 0 END AS bc, CASE WHEN o.ticket_name = 'ac' AND o.ticket_number = CONCAT(LEFT(r.p1, 1),RIGHT(r.p1, 1)) THEN o.ticket_count * s.amount ELSE 0 END AS ac, CASE WHEN o.ticket_name = 'a' AND o.ticket_number = LEFT(r.p1, 1) THEN o.ticket_count * s.amount ELSE 0 END AS a, CASE WHEN o.ticket_name = 'b' AND o.ticket_number = substring(r.p1, 2, 1) THEN o.ticket_count * s.amount ELSE 0 END AS b, CASE WHEN o.ticket_name = 'c' AND o.ticket_number = RIGHT(r.p1, 1) THEN o.ticket_count * s.amount ELSE 0 END AS c FROM users u LEFT JOIN orders o on o.user_id = u.id LEFT JOIN results r ON o.play_id = r.play_id AND o.play_date = r.play_date LEFT JOIN schemes s ON s.ticket_id = o.ticket_id WHERE u.role = 'user' GROUP BY id, name, ticket_name) AS tbl1 GROUP BY id, name) AS tbl2;"));

        return response()->json([
            'status' => true,
            'record' => $data,
            'purchase' => $data->sum('purchase'),
            'winning' => $data->sum('won'),
            'balance' => $data->sum('balance'),
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
