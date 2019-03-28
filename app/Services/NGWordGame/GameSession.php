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
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\ImageComponentBuilder;

class GameSession
{
    /**
     * クラス変数
     */
    private $bot;
    private $session;
    private $group_id;
    
    
    public function __construct(LINEBot $bot)
    {
        $this->bot = $bot;
    }

    /**
     * すでにセッション開始していれば引き継ぐ
     */
    public function continueSession($event)
    {
        // // とりあえずユーザ情報を確認して、登録なければ登録
        if (LineFriend::where('line_id', $event->getUserId())->first() == null) {
            $service = new FollowService($this->bot);
            $service->pushProfile($event, $event->getUserId());
        }

        if ($event->isUserEvent()) {
            $group_data = LineFriend::where('line_id', $event->getUserId())->first();
        } else {
            $group_data = LineGroup::where('line_group_id', $event->getEventSourceId())->first();
        }
        
        if (isset($group_data)) {
            $this->group_id = $group_data->id;
        } else {
            return null;
        }

        $this->session = Game::where('group_id', $this->group_id)->latest()->first();
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
                'status_id' => 2
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
     * 参加の締め切り、お題の設定
     * お題の設定
     */
    public function settingTheme($event)
    {
        $this->continueSession($event);
        if ($this->session->status->name != 'recruting') {
            return false;
        }
        // お題設定担当を割り振り
        try {
            $joinedUsers = GameJoinedUsers::where('game_id', $this->session->id)->get();

            // シャッフル
            $usersId = array();
            foreach ($joinedUsers as $joinedUser) {
                array_push($usersId, $joinedUser->getUserData->id);
            }
            if (count($usersId) > 1) {
                $usersId = self::array_shuffle($usersId);
            }

            DB::beginTransaction();
            // ステータス更新
            $this->session->update([
                'status_id' => 3    // お題設定中
            ]);
            foreach ($joinedUsers as $index => $joinedUser) {
                $joinedUser->update(['keyword_decide_user_id' => $usersId[$index]]);
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
                                    ->setAction(new UriTemplateActionBuilder('お題を決める', 'line://app/1557900408-rL05MMWy?method=decide&session_id='.$this->session->id))
                                ])
                        )
                );
    }

    /**
     * 参加者のキーワードが全部決定しているかどうか
     */
    public function isKeywordAllDecided($event)
    {
        $this->continueSession($event);
        $joinedUsers = GameJoinedUsers::where('game_id', $this->session->id)->get();

        $result = true;
        foreach ($joinedUsers as $joinedUser) {
            if (empty($joinedUser->keyword_id)) {
                $result = false;
                break;
            }
        }
        return $result;
    }

    /**
     *
     */
    public function gameStart($event)
    {
        $this->continueSession($event);
        // お題設定担当を割り振り
        try {
            DB::beginTransaction();
            // ステータス更新
            $this->session->update([
                'status_id' => 4    // ゲーム開始
            ]);
            DB::commit();
        } catch (Exception $e) {
            logger()->error($e);
            DB::rollBack();
            return false;
        }

        return FlexMessageBuilder::builder()
                ->setAltText('全員のお題が決まりました！ゲームスタートです')
                ->setContents(
                    BubbleContainerBuilder::builder()
                        ->setBody(
                            BoxComponentBuilder::builder()
                            ->setLayout(ComponentLayout::VERTICAL)
                            ->setContents([
                                TextComponentBuilder::builder()
                                    ->setText('全員のお題が決まりました！ゲームスタートです')
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
                                    ->setAction(new UriTemplateActionBuilder('他の人のお題を見る', 'line://app/1557900408-rL05MMWy?method=showkeyword&session_id='.$this->session->id))
                                ])
                        )
                );
    }

    public function checkOwnWord($event, $word)
    {
        $this->continueSession($event);
        if ($this->session->status->name != 'nowplaying') {
            return false;
        }
        $user_id = LineFriend::where('line_id', $event->getUserId())->first();
        $mydata = GameJoinedUsers::where('game_id', $this->session->id)->where('user_id', $user_id->id)->first();
        return strcmp($mydata->keyword->word, $word) == 0;
    }

    public function gameResult($event)
    {
        $this->continueSession($event);
        try {
            $user = LineFriend::where('line_id', $event->getUserId())->first();
            $keyword = GameJoinedUsers::where('game_id', $this->session->id)->where('user_id', $user->id)->first()->keyword->word;

            DB::beginTransaction();
            $this->session->update(['status_id' => 1]);
            DB::commit();

            return FlexMessageBuilder::builder()
                ->setAltText($user.'さんの負けです!')
                ->setContents(
                    BubbleContainerBuilder::builder()
                        ->setHeader(
                            BoxComponentBuilder::builder()
                            ->setLayout(ComponentLayout::VERTICAL)
                            ->setContents([
                                TextComponentBuilder::builder()
                                    ->setText($user->display_name.'さん、ドーンだYO！')
                                    ->setSize(ComponentFontSize::LG)
                                    ->setColor('#ff0000')
                                ])
                            )
                        ->setBody(
                            BoxComponentBuilder::builder()
                            ->setLayout(ComponentLayout::VERTICAL)
                            ->setContents([
                                TextComponentBuilder::builder()
                                    ->setText('お題：'.$keyword)
                                    ->setSize(ComponentFontSize::MD)
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
                                    ->setAction(new UriTemplateActionBuilder('総合発表', 'line://app/1557900408-rL05MMWy?method=result&session_id='.$this->session->id))
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
     * ゲーム終了
     */
    public function endGame($event)
    {
        $this->continueSession($event);
        try {
            DB::beginTransaction();
            $this->session->update(['status_id' => 1]);
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
        if (count($array) > 0) {
            $head = $array[0];
            array_shift($array);
            array_push($array, $head);
            return $array;
        } else {
            return $array;
        }
    }
}
