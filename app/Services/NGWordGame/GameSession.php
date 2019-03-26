<?php
namespace App\Services\NGWordGame;

use App\Models\LineFriend;
use App\Models\LineGroup;
use App\Models\Game;
use App\Models\GameJoinedUsers;


use LINE\LINEBot\Event\BaseEvent;
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\BubbleContainerBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\BoxComponentBuilder;
use LINE\LINEBot\Constant\Flex\ComponentLayout;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\TextComponentBuilder;
use LINE\LINEBot\Constant\Flex\ComponentFontWeight;
use LINE\LINEBot\Constant\Flex\ComponentFontSize;
use LINE\LINEBot\Constant\Flex\ComponentSpacing;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\ButtonComponentBuilder;
use LINE\LINEBot\Constant\Flex\ComponentButtonStyle;
use LINE\LINEBot\Constant\Flex\ComponentButtonHeight;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\SpacerComponentBuilder;
use LINE\LINEBot\Constant\Flex\ComponentSpaceSize;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;

use DB;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot;
use App\Services\Line\Event\FollowService;

class GameSession
{
    /**
     * クラス変数
     */
    private $bot;
    private $session;
    private $group_id;
    
    /**
     * すでにセッション開始していれば引き継ぐ
     */
    public function __construct(LINEBot $bot)
    {
        $this->bot = $bot;
    }

    public function continueSession($event)
    {
        // 登録していないユーザなら情報を取得して保存
        if (LineFriend::where('line_id', $event->getUserId())->first() == null) {
            $service = new FollowService($this->bot);
            $service->execute($event);
        }

        if ($event->isUserEvent()) {
            $group_data = LineFriend::where('line_id', $event->getUserId())->first();
        } else {
            $group_data = LineGroup::where('line_group_id', $event->getEventSourceId())->first();
        }

        if (isset($group_data)) {
            $this->group_id = $group_data->id;
        } else {
            return false;
        }

        $session = Game::where('group_id', $this->group_id)->latest()->first();
        if (isset($session) && $session->is_enable) {
            $this->session = $session;
        }
    }

    /**
     * セッション開始
     * 参加者の募集
     */
    public function prepareGame(BaseEvent $event)
    {
        $this->continueSession($event);
        try {
            DB::beginTransaction();
            $this->session = Game::create([
                'group_id' => $this->group_id,
                'is_enable' => true
            ]);
            DB::commit();
        
            return FlexMessageBuilder::builder()
                ->setAltText('参加者を募集します')
                ->setContents(
                    BubbleContainerBuilder::builder()
                        ->setBody(
                            BoxComponentBuilder::builder()
                            ->setLayout(ComponentLayout::VERTICAL)
                            ->setContents([
                                TextComponentBuilder::builder()
                                    ->setText('ゲーム参加者の募集')
                                    ->setWeight(ComponentFontWeight::BOLD)
                                    ->setSize(ComponentFontSize::XL)
                                ])
                            )
                        -> setFooter(
                            BoxComponentBuilder::builder()
                                ->setLayout(ComponentLayout::VERTICAL)
                                ->setSpacing(ComponentSpacing::SM)
                                ->setFlex(0)
                                ->setContents([
                                    ButtonComponentBuilder::builder()
                                    ->setStyle(ComponentButtonStyle::PRIMARY)
                                    ->setHeight(ComponentButtonHeight::MD)
                                    ->setAction(new MessageTemplateActionBuilder('参加します', '参加します')),
                                    ButtonComponentBuilder::builder()
                                    ->setStyle(ComponentButtonStyle::SECONDARY)
                                    ->setHeight(ComponentButtonHeight::MD)
                                    ->setAction(new MessageTemplateActionBuilder('見学します', '見学します')),
                                    ButtonComponentBuilder::builder()
                                    ->setStyle(ComponentButtonStyle::PRIMARY)
                                    ->setHeight(ComponentButtonHeight::MD)
                                    ->setAction(new PostbackTemplateActionBuilder('募集を締め切る', 'action=gamestart&sessionid='.$this->session->id)),
                                    new SpacerComponentBuilder(ComponentSpaceSize::SM)
                                ])
                            
                             
                        )
                    );
        } catch (Exception $e) {
            logger()->error($e);
            DB::rollBack();
            return false;
        }
    }

    /**
     * ユーザをゲームに登録
     */
    public function joinGame($event)
    {
        $this->continueSession($event);
        try {
            DB::beginTransaction();

            $user_id = LineFriend::where('line_id', $event->getUserId())->first();
            logger()->info($user_id);
            if (GameJoinedUsers::where('game_id', $this->session->id)->where('user_id', $user_id->id)->first()) {
                DB::rollBack();
                return $user_id->display_name.'さんは参加受付済です';
            } else {
                $this->session = GameJoinedUsers::create([
                    'user_id' => $user_id->id,
                    'game_id' => $this->session->id,
                    'keyword' => '',
                    'is_joined' => true
                ]);
                DB::commit();
                return $user_id->display_name.'さんの参加を受け付けました';
            }
        } catch (Exception $e) {
            logger()->error($e);
            DB::rollBack();
            return false;
        }
    }

    /**
     * お題の設定
     */
    public function settingTheme($event)
    {
        $this->continueSession($event);
        // お題設定担当を割り振り
        try {
            $joinedUsers = GameJoinedUsers::where('game_id', $this->session->id)->get();

            // シャッフル
            $users_id = array();
            foreach ($joinedUsers as $joinedUser) {
                array_push($users_id, $joinedUser->id);
            }
            if (count($users_id) > 1) {
                $users_id = self::array_shuffle($users_id);
            }

            DB::beginTransaction();
            foreach ($joinedUsers as $index => $joinedUser) {
                $joinedUser->update(['keyword_decide_user_id' => $users_id[$index]]);
            }
            DB::commit();
        } catch (Exception $e) {
            logger()->error($e);
            DB::rollBack();
            return false;
        }

        return FlexMessageBuilder::builder()
                ->setAltText('お題を設定します')
                ->setContents(
                    BubbleContainerBuilder::builder()
                        ->setBody(
                            BoxComponentBuilder::builder()
                            ->setLayout(ComponentLayout::VERTICAL)
                            ->setContents([
                                TextComponentBuilder::builder()
                                    ->setText('相手のお題を決める')
                                    ->setSize(ComponentFontSize::SM)
                                ])
                            )
                        -> setFooter(
                            BoxComponentBuilder::builder()
                                ->setLayout(ComponentLayout::VERTICAL)
                                ->setSpacing(ComponentSpacing::SM)
                                ->setFlex(0)
                                ->setContents([
                                    ButtonComponentBuilder::builder()
                                    ->setStyle(ComponentButtonStyle::PRIMARY)
                                    ->setHeight(ComponentButtonHeight::MD)
                                    ->setAction(new UriTemplateActionBuilder('お題を決める', 'line://app/1557900408-rL05MMWy?session_id='.$this->session->id))
                                ])
                        )
                );
    }

    /**
     *
     */
    public function gameStart()
    {
    }

    /**
     * ゲーム終了
     */
    public function endGame($event)
    {
        $this->continueSession($event);
        try {
            DB::beginTransaction();
            $this->session = Game::find($this->session->id)
                ->update(['is_enable' => false]);
            DB::commit();
            return 'ゲームを終了しました';
        } catch (Exception $e) {
            logger()->error($e);
            DB::rollBack();
            return false;
        }
    }

    /**
     * 重複のないシャッフルをする関数
     */
    private static function array_shuffle($array)
    {
        $flag = true;
        $keys = array_keys($array);
        while ($flag) {
            $result = array();
            shuffle($keys);
            foreach ($keys as $key=>$value) {
                if ($key === $value) {
                    $flag = true;
                    break;
                }
                $result[] = $array[$value];
                $flag = false;
            }
        }
        return $result;
    }
}
