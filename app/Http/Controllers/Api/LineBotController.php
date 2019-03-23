<?php

namespace App\Http\Controllers\Api;

use App\Services\Line\Event\RecieveLocationService;
use App\Services\Line\Event\RecieveTextService;
use App\Services\Line\Event\RecieveImageService;
use App\Services\Line\Event\RecieveStickerService;
use App\Services\Line\Event\FollowService;
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
                $event_log->line_id = $event->getUserId();
                $event_log->event_type = $event->getType();

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
                        $reply_message = $event_log->contents = $service->execute($event);
                        break;

                    //位置情報の受信
                    case $event instanceof LINEBot\Event\MessageEvent\LocationMessage:
                        $service = new RecieveLocationService($bot);
                        $reply_message = $event_log->contents = $service->execute($event);
                        break;

                    //選択肢とか選んだ時に受信するイベント
                    case $event instanceof LINEBot\Event\PostbackEvent:
                        break;

                    //ブロック
                    case $event instanceof LINEBot\Event\UnfollowEvent:
                        break;
                    
                    case $event instanceof LINEBot\Event\MessageEvent\ImageMessage:
                        $reply_message = $event_log->contents = '画像';
                        break;

                    case $event instanceof LINEBot\Event\MessageEvent\StickerMessage:
                        $reply_message =$event_log->contents = 'スタンプ';
                        break;

                    default:
                        $body = $event->getEventBody();
                        logger()->warning('Unknown event. ['. get_class($event) . ']', compact('body'));
                }

                $event_log->save();
                DB::commit();
            } catch (Exception $e) {
                logger()->error($e);
                DB::rollBack();
            }
            
            $bot->replyText($reply_token, $reply_message);
        }
    }
}
