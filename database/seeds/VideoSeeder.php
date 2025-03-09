<?php

use think\migration\Seeder;

class VideoSeeder extends Seeder
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run(): void
    {
        for ($i = 0; $i < 20000; $i++) {
            \app\admin\model\Videos::create([
                'video_category_ids'=>rand(1,20),
                'name'=>\think\helper\Str::random(10),
                'image'=>'http://www.movie.com/storage/default/20250302/16b78da808b59a6eac0d88bb27b0a4128a7df2914.jpg',
                'url'=>'https://bitdash-a.akamaihd.net/content/sintel/hls/playlist.m3u8',
                'duration'=>rand(1,100),
                'total_purchases'=>rand(1,100000),
                'create_time'=>time(),
                'update_time'=>time(),

            ]);
        }

    }
}