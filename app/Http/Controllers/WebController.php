<?php

namespace App\Http\Controllers;

use App\Models\Play;
use App\Models\Result;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebController extends Controller
{
    public function authenticate(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);
        try {
            $request['email'] = $request['email'] . '@modernstone.com';
            $credentials = $request->only('email', 'password');
            if (Auth::attempt($credentials)) {
                return redirect()->route('dashboard')
                    ->with("success", 'User logged in successfully');
            }
            return redirect()->back()->with("error", "Invalid Credentials")->withInput($request->all());
        } catch (Exception $e) {
            return redirect()->back()->with("error", $e->getMessage())->withInput($request->all());
        }
    }

    public function dashboard()
    {
        $plays = Play::all();
        $results = Result::whereDate('play_date', Carbon::today())->get();
        return view('dashboard', compact('plays', 'results'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'play_id' => 'required',
            'play_date' => 'required|date',
        ]);
        $input = $request->all();
        try {
            Result::create($input);
        } catch (Exception $e) {
            return redirect()->back()->with("error", $e->getMessage())->withInput($request->all());
        }
        return redirect()->route('dashboard')->with("success", "Result updated successfully");
    }

    public function edit(String $id)
    {
        $result = Result::findOrFail($id);
        $plays = Play::all();
        return view('edit-result', compact('result', 'plays'));
    }

    public function update(Request $request, String $id)
    {
        $request->validate([
            'play_id' => 'required',
            'play_date' => 'required|date',
        ]);
        $input = $request->all();
        Result::findOrFail($id)->update($input);
        return redirect()->route('dashboard')->with("success", "Result updated successfully");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with("success", "User logged out successfully");
    }
}
