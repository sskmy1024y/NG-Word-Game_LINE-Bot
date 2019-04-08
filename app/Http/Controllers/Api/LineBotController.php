<?php

namespace App\Http\Controllers\Api;

use App\Services\Line\Event\RecieveLocationService;
use App\Services\Line\Event\RecieveTextService;
use App\Services\Line\Event\RecieveImageService;
use App\Services\Line\Event\RecieveStickerService;
use App\Services\Line\Event\FollowService;
use App\Services\Line\Event\JoinService;
use App\Services\NGWordGame\GameSession;

use App\Services\Line\MessageBuilder\FlexMessage;

use App\Models\LineEventLogs;
use Illuminate\Http\Request;
use LINE\LINEBot;
use DB;

class LineBotController
{
    /**
     * callback from LINE Message API(webhook)
     * @param Request $request
     * @throws LINEBot\Exception\InvalidSignatureException
     */
    public function callback(Request $request)
    {

        /** @var LINEBot $bot */
        $bot = app('line-bot');

        $signature = $_SERVER['HTTP_'.LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];
        if (!LINEBot\SignatureValidator::validateSignature($request->getContent(), env('LINE_CHANNEL_SECRET'), $signature)) {
            logger()->info('recieved from difference line-server');
            abort(400);
        }

        $events = $bot->parseEventRequest($request->getContent(), $signature);

        /** @var LINEBot\Event\BaseEvent $event */
        foreach ($events as $event) {
            $reply_token = $event->getReplyToken();
            $reply_message = 'その操作はサポートしてません。.[' . get_class($event) . '][' . $event->getType() . ']';

            try {
                DB::beginTransaction();

                $event_log = new LineEventLogs();
                $event_log->line_id = $event->getEventSourceId();
                $event_log->event_type = $event->getType();

                $game_session = new GameSession($bot);

                switch (true) {
                    //友達登録＆ブロック解除
                    case $event instanceof LINEBot\Event\FollowEvent:
                        $service = new FollowService($bot);
                        $reply_message = $service->execute($event)
                            ? '友達登録されたからLINE ID引っこ抜いたわー'
                            : '友達登録されたけど、登録処理に失敗したから、何もしないよ';

                        break;

                        //メッセージの受信
                        case $event instanceof LINEBot\Event\MessageEvent\TextMessage:
                            $service = new RecieveTextService($bot);
                            $responses = $service->execute($event);
                            $reply_message = null;

                            // Watsonからのレスポンスによって処理を分岐
                            foreach ($responses as $response) {
                                switch ($response['type']) {
                                    case 'option':
                                        foreach ($response['body'] as $optkey => $option) {
                                            if ($optkey == 'system_call') {
                                                switch ($option) {
                                                    case "preparegame":
                                                        $event_log->contents = '[system_call] game wakeup';
                                                        $reply_message = $game_session->prepareGame($event);
                                                        break;
                                                    case "joingame":
                                                        $event_log->contents = '[system_call] user join to game';
                                                        $reply_message = $game_session->joinGame($event);
                                                        break;
                                                    case "restgame":
                                                        $event_log->contents = '[system_call] user rest to game';
                                                        $reply_message = $game_session->restGame($event);
                                                        break;
                                                    case "joined_user_check":
                                                        if ($game_session->isKeywordAllDecided($event)) {
                                                            $reply_message = $game_session->gameStart($event);
                                                        }
                                                        break;
                                                    case "endgame":
                                                        $event_log->contents = '[system_call] game end';
                                                        $reply_message = $game_session->endGame($event);
                                                        break;
                                                }
                                            } elseif ($optkey == 'check_word') {
                                                if ($game_session->checkOwnWord($event, $option)) {
                                                    $reply_message = $game_session->gameResult($event);
                                                }
                                            }
                                        }
                                        break;
                                }
                            }
                            break;

                    //位置情報の受信
                    case $event instanceof LINEBot\Event\MessageEvent\LocationMessage:
                        $service = new RecieveLocationService($bot);
                        $reply_message = $event_log->contents = $service->execute($event);
                        break;

                    //選択肢とか選んだ時に受信するイベント
                    case $event instanceof LINEBot\Event\PostbackEvent:
                        // パラメタを正規化
                        $tmp_data = $event->getPostbackData();
                        $tmp_params = explode('&', $tmp_data);
                        $params = [];
                        foreach ($tmp_params as $key) {
                            $split = explode('=', $key);
                            $params[$split[0]] = $split[1];
                        }

                        if ($params['action']) {
                            switch ($params['action']) {
                                case 'gamestart':
                                    $reply_message = $game_session->settingTheme($event);
                                    break;
                            }
                        }
                        break;

                    //ブロック
                    case $event instanceof LINEBot\Event\UnfollowEvent:
                        break;
                    
                    case $event instanceof LINEBot\Event\MessageEvent\ImageMessage:
                        $reply_message = $event_log->contents = '画像';
                        break;

                    case $event instanceof LINEBot\Event\MessageEvent\StickerMessage:
                        $reply_message = $event_log->contents = 'スタンプ';
                        break;
                    
                    // グループに追加された時
                    case $event instanceof LINEBot\Event\MemberJoinEvent:
                    case $event instanceof LINEBot\Event\JoinEvent:
                        //JoinServiceクラスでDBに書き込み
                        $service = new JoinService($bot);
                        $reply_message = $service->execute($event)
                            ? 'グループに追加してくれてありがとう〜！このゲームは「NGワードゲーム」。自分のNGワードを言わないように気をつけながら、相手のNGワードを言わせよう！'
                            : 'なんか登録できんかった';
                        break;

                    default:
                        $body = $event->getEventBody();
                        logger()->warning('Unknown event. ['. get_class($event) . ']', compact('body'));
                }

                // $event_log->save();
                DB::commit();
            } catch (Exception $e) {
                logger()->error($e);
                DB::rollBack();
            }
            
            if (is_string($reply_message)) {
                $bot->replyText($reply_token, $reply_message);
            } elseif ($reply_message instanceof LINEBot\MessageBuilder) {
                $bot->replyMessage($reply_token, $reply_message);
            }
        }
    }
}
