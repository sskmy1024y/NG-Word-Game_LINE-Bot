<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameJoinedUsers;
use Illuminate\Support\Facades\Log;
use App\Models\LineFriend;

class LIFFController extends Controller
{
    public function index(Request $request)
    {
        $gameSessionID = $request->session_id;
        return view("LIFF/index", compact('gameSessionID'));
    }

    public function callback(Request $request)
    {
        $response = array();
        switch ($request->method) {
            case 'getDecider':
                $id = LineFriend::where('line_id', $request->userID)->first()->id;
                $response = GameJoinedUsers::where('game_id', $request->sessionID)->where('user_id', $id)->first();
                break;
        }
        
        Log::debug($response);
        
        return response()->json($id);
    }
}
