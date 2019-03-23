<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Watson\CallWatsonAssistant;

class WatsonCallController extends Controller
{
    /**
     * セッションを初期化し、初期画面を表示する。
     * @param Request $request
     * @return response 入力初期画面
     */
    public function index(Request $request)
    {
        $request->session()->flush();
        return view("layouts/index");
    }
    /**
     * WatsonAssistantを呼び出してjson形式で返却する。
     * 継続した会話を実現する為、contextはセッションに保管しておく
     *
     * @param Request リクエストデータ
     * @param CallWatsonAssistant WatsonAssistant呼び出しモジュール
     * @return json Watson Assistantからの受信データ
     */
    public function talkToWatson(Request $request, CallWatsonAssistant $CWA)
    {
        $response      = $CWA->call($request->spokenword, session('context')?session('context'):[]);
        $responseArray = json_decode($response, true);
        $request->session()->put('context', $responseArray['context']);
        return $response;
    }
}
