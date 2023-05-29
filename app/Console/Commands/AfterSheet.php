<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AfterSheet extends Command
{

    protected $signature = 'update:products:galleries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Product galleries update';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Команда для загрузки картинок запущена');

        $result = DB::table('before_galleries')
            ->distinct('product_id')
            ->orderBy('id', 'desc')
            ->get()
            ->unique('product_id')
            ->chunk(50);
//            ->chunkById(50, function ($images, $key) {
//                $this->info("внутри чанка $key " . date('Y-m-d H:i:s'));
//                $this->downloadImages($images);
//            });

        foreach ($result as $key => $images) {

            $this->info("внутри чанка $key " . date('Y-m-d H:i:s'));
            $this->downloadImages($images);

        }

        $memoryUsage = memory_get_usage() / 1024 / 1024;

        $this->info("total gallery mb $memoryUsage " . date('Y-m-d H:i:s'));

//        Log::error("total gallery mb $memoryUsage " . date('Y-m-d H:i:s'));

    }

    public function downloadImages($images) {

        $galleries      = [];
        $deleteImages   = [];

        $mh = curl_multi_init();

        $handles = array();

        foreach ($images as $image) {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $image->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
//            curl_setopt($ch, CURLOPT_CAINFO, 'D:/cacert-2023-01-10.pem');
            curl_multi_add_handle($mh, $ch);

            $handles[] = [
                'id'         => $image->id,
                'url'        => $image->url,
                'product_id' => $image->product_id,
                'ch'         => $ch
            ];

        }

        $running = 0;

        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        $handles = collect($handles)->groupBy('product_id');

        foreach ($handles as $handle) {

            foreach ($handle as $key => $data) {

                $handle = $data['ch'];

                $content = curl_multi_getcontent($handle);
                $info    = curl_getinfo($handle);

                if ($info['http_code'] == 200) {

                    $fileName = basename($info['url']);


                    $name = 'products/' . $data['product_id'] . time() . '.' . substr(strrchr($fileName, '.'), 1);

                    $url = "public/images/$name";

                    Storage::disk('do')->put($url, $content, 'public');

                    $memoryUsage = memory_get_usage() / 1024 / 1024;

                    $this->info("gallery mb $memoryUsage " . date('Y-m-d H:i:s'));

//                    Log::error("gallery mb $memoryUsage " . date('Y-m-d H:i:s'));

                    $galleries[] = [
                        'title'         => $url,
                        'path'          => $name,
                        'type'          => 'products',
                        'loadable_type' => 'App\Models\Product',
                        'loadable_id'   => $data['product_id'],
                    ];

                    if ($key == 0) {
                        DB::table('products')
                            ->where('id', $data['product_id'])
                            ->update([
                                'img' => $name,
                            ]);
                    }

                    $deleteImages[] = $data['id'];
                }

                curl_multi_remove_handle($mh, $handle);
            }

        }

        curl_multi_close($mh);

        DB::table('galleries')->insert($galleries);

        DB::table('before_galleries')
            ->whereIn('id', $deleteImages)
            ->delete();

        $memoryUsage = memory_get_usage() / 1024 / 1024;

        $this->info("gallery chunk mb $memoryUsage " . date('Y-m-d H:i:s'));

//        Log::error("gallery mb $memoryUsage " . date('Y-m-d H:i:s'));

    }


}
