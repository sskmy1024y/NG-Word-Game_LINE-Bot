<?php

namespace App\Services\Line\Event;

use LINE\LINEBot;
use DB;
use LINE\LINEBot\Event\MessageEvent\StickerMessage;

class RecieveStickerService
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
     *
     */
    public function execute(StickerMessage $event)
    {
        // エコーだけ
        return $event->get();
    }
}
