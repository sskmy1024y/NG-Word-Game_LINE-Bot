<?php

namespace App\Services\Line\Event;

use App\Models\LineGroup;
use LINE\LINEBot;
use LINE\LINEBot\Event\JoinEvent;
use DB;

class JoinService
{
    /**
     * @var LINEBot
     */
    private $bot;

    /**
     * Follow constructor.
     * @param LINEBot $bot
     */
    public function __construct(LINEBot $bot)
    {
        $this->bot = $bot;
    }

    /**
     * グループ登録
     * @param JoinEvent $event
     * @return bool
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function execute(JoinEvent $event)
    {
        try {
            DB::beginTransaction();

            if ($event->isGroupEvent()) {
                $line_id = $event->getGroupId();
            } elseif ($event->isRoomEvent()) {
                $line_id = $event->getRoomId();
            } else {
                throw new Error();
            }

            $line_friend = new LineGroup();
            $input = [
                'line_group_id' => $line_id,
                'display_name' => '',
            ];

            $line_friend->fill($input)->save();

            DB::commit();

            return true;
        } catch (Exception $e) {
            logger()->error($e);
            DB::rollBack();
            return false;
        }
    }
}
