<?php

namespace App\Services\Line\Event;

use App\Models\LineTalks;
use LINE\LINEBot;
use DB;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use App\Services\Fauf\FaufBridge;

class RecieveTextService
{
    /**
     * @var LineBot
     */
    private $bot;

    /**
     * Follow constructor.
     * @param LineBot $bot
     */
    public function __construct(LineBot $bot)
    {
        $this->bot = $bot;
    }

    /**
     * 登録
     * @param TextMessage $event
     * @return string
     */
    public function execute(TextMessage $event)
    {
        $fb = new FaufBridge();
        $request['text'] = $event->getText();
        $responses = $fb->callAI($request);

        foreach ($responses as $response) {
            if (is_string($response)) {
                return $response;
            }
        }

        return $responses;
    }
}
