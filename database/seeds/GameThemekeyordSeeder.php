<?php

use Illuminate\Database\Seeder;

class GameThemeKeywordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("game_theme_keywords")->insert([
            "word" => "お腹すいた",
        ]);
        DB::table("game_theme_keywords")->insert([
            "word" => "よみうりランド",
        ]);
        DB::table("game_theme_keywords")->insert([
            "word" => "ヤバい",
        ]);
        DB::table("game_theme_keywords")->insert([
            "word" => "ウケる",
        ]);
        DB::table("game_theme_keywords")->insert([
            "word" => "ディズニー",
        ]);
        DB::table("game_theme_keywords")->insert([
            "word" => "天才",
        ]);
        DB::table("game_theme_keywords")->insert([
            "word" => "マジ",
        ]);


        DB::table("game_theme_keywords")->insert([
            "word" => "まあね",
        ]);
        DB::table("game_theme_keywords")->insert([
            "word" => "東京タワー",
        ]);
        DB::table("game_theme_keywords")->insert([
            "word" => "チェケラ",
        ]);
        DB::table("game_theme_keywords")->insert([
            "word" => "LINE",
        ]);
        DB::table("game_theme_keywords")->insert([
            "word" => "Twitter",
        ]);
        DB::table("game_theme_keywords")->insert([
            "word" => "多分",
        ]);
        DB::table("game_theme_keywords")->insert([
            "word" => "ゲーム",
        ]);
    }
}
