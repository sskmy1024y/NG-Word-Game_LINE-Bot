<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameJoinedUsers;
use Illuminate\Support\Facades\Log;
use App\Models\LineFriend;
use App\Models\GameThemeKeyword;
use DB;
use App\Models\Game;

class LIFFController extends Controller
{
    public function index(Request $request)
    {
        $gameSessionID = $request->session_id;
        $view = "LIFF/index";
        switch ($request->method) {
            case 'decide':
                $view = "LIFF/decide";
                break;
            case 'showkeyword':
                $view = "LIFF/showkeyword";
                break;
            case 'result':
                $view = "LIFF/result";
                break;
        }
        return view($view, compact('gameSessionID'));
    }

    public function callback(Request $request)
    {
        $response = [];
        try {
            DB::beginTransaction();
            switch ($request->method) {
                case 'getDecider':
                    if (Game::find($request->sessionID)->status->name != 'decide-theme') {
                        break;
                    }
                    //相手のIDを取得
                    $id = LineFriend::where('line_id', $request->userID)->first()->id;
                    $response["decide_user_name"] = GameJoinedUsers::where('game_id', $request->sessionID)->where('user_id', $id)->first()->getDecideUserData->display_name;
                    logger()->info($id);
                    logger()->info($response["decide_user_name"]);
                    // ランダムにワードを取得
                    $words = GameThemeKeyword::inRandomOrder()->limit(3)->get();
                    $response['candidacy_keywords'] = [];
                    foreach ($words as $word) {
                        array_push($response['candidacy_keywords'], ['key' => $word->id, 'value' => $word->word]);
                    }
                    $response['success'] = true;
                    break;
                case 'selectedWord':
                    if (Game::find($request->sessionID)->status->name != 'decide-theme') {
                        break;
                    }
                    $id =  LineFriend::where('line_id', $request->userID)->first()->id;
                    // 相手のユーザーID
                    $oppoId = GameJoinedUsers::where('game_id', $request->sessionID)->where('user_id', $id)->first()->getDecideUserData->id;
                    GameJoinedUsers::where('game_id', $request->sessionID)->where('user_id', $oppoId)->first()->update(['keyword_id' => $request->keywordID]);
                    logger()->info(GameJoinedUsers::where('game_id', $request->sessionID)->where('user_id', $oppoId)->first()->getDecideUserData);
                    $response['success'] = true;
                    break;
                case 'getEveryKeywords':
                    $id = LineFriend::where('line_id', $request->userID)->first()->id;
                    $users = GameJoinedUsers::where('game_id', $request->sessionID)->whereNotIn('user_id', [$id])->get();
                    logger()->info($id);
                    logger()->info($users);

                    if (count($users) > 0) {
                        $response['users'] = [];
                        foreach ($users as $user) {
                            array_push($response['users'], [
                            'name' => $user->getUserData->display_name,
                            'keyword' => $user->keyword->word
                        ]);
                        }
                    } else {
                        $response['keyword'] = 'SINGLE_PLAYER';
                    }
                    break;
                case 'result':
                    $users = GameJoinedUsers::where('game_id', $request->sessionID)->get();
                    $response['users'] = [];
                    foreach ($users as $user) {
                        array_push($response['users'], [
                            'name' => $user->getUserData->display_name,
                            'keyword' => $user->keyword->word
                        ]);
                    }
                    break;
            }
            DB::commit();
        } catch (Exeption $e) {
            logger()->info($e);
            DB::rollBack();
            $response = ['error' => 'error'];
        }
        
        return response()->json($response);
    }
}
