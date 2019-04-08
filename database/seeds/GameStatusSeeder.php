<?php

use Illuminate\Database\Seeder;

class GameStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("game_status")->insert([
            "name" => "pending",  // 待機中（何もない状態)
        ]);
        DB::table("game_status")->insert([
            "name" => "recruting",  // 参加者募集中
        ]);
        DB::table("game_status")->insert([
            "name" => "decide-theme", // お題決め中
        ]);
        DB::table("game_status")->insert([
            "name" => "nowplaying",  // お話中
        ]);
        DB::table("game_status")->insert([
            "name" => "finish",  // 結果発表して終了
        ]);
    }
}
