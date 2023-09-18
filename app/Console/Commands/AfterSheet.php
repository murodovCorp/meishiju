<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\ParcelOrder;
use App\Models\Product;
use App\Models\Settings;
use App\Models\Shop;
use Http;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AfterSheet extends Command
{

    protected $signature = 'update:models:galleries';

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

        try {
            $result = DB::table('before_galleries')
                ->orderBy('parent', 'desc')
                ->get()
                ->chunk(500);

            foreach ($result as $images) {

                $this->downloadImages($images->toArray());

            }

        } catch (Throwable $e) {
            $this->error($e);
        }

        $memoryUsage = memory_get_usage() / 1024 / 1024;

        $this->info("total gallery mb $memoryUsage " . date('Y-m-d H:i:s'));

        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

//        Log::error("total gallery mb $memoryUsage " . date('Y-m-d H:i:s'));

    }

    public function downloadImages($images) {

        $isAws = Settings::adminSettings()->where('key', 'aws')->first();

        $galleries      = [];
        $deleteImages   = [];

        $mh = curl_multi_init();

        $handles = [];

        foreach ($images as $image) {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $image->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($mh, $ch);
            curl_multi_setopt($mh, CURLMOPT_PIPELINING, 0);

            $handles[] = [
                'id'         => $image->id,
                'url'        => $image->url,
                'model_id'   => $image->model_id,
                'model_type' => $image->model_type,
                'parent'     => $image->parent,
                'ch'         => $ch
            ];

        }

        $running = 0;

        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

//        $handles = collect($handles)->groupBy(['model_id', 'model_type']);

        foreach ($handles as $data) {

            if (!isset($data['ch'])) {
                continue;
            }

            $handle  = $data['ch'];

            $info    = curl_getinfo($handle);

            if ($info['http_code'] == 200) {

                $fileName = basename($info['url']);

                $type = match ($data['model_type']) {
                    Category::class     => 'categories',
                    Brand::class        => 'brands',
                    Product::class      => 'products',
                    Shop::class         => 'shops',
                    Order::class        => 'orders',
                    ParcelOrder::class  => 'parcel-orders',
                };

                $name = "$type/" . $data['model_id'] . time() . '.' . substr(strrchr($fileName, '.'), 1);
                $url  = "$name";

                Storage::put($url, file_get_contents($info['url']), [
                    'disk' => data_get($isAws, 'value') ? 's3' : 'public'
                ]);

                $name = config('app.img_host') . str_replace('public/images/', '', $url);

                $galleries[] = [
                    'title'         => $url,
                    'path'          => $name,
                    'type'          => $type,
                    'loadable_type' => $data['model_type'],
                    'loadable_id'   => $data['model_id'],
                ];

                $any = DB::table('before_galleries')
                    ->where('model_id',   $data['model_id'])
                    ->where('model_type', $data['model_type'])
                    ->where('parent', 1)
                    ->exists();

                if (data_get($data, 'parent') || !$any) {

                    $key = 'img';

                    if ($type === 'shops') {
                        $key = 'logo_img';
                    }

                    DB::table(str_replace('-', '_', $type))
                        ->where('id', $data['model_id'])
                        ->update([
                            $key => $name,
                        ]);

                }

                $deleteImages[] = $data['id'];
            }

            curl_multi_remove_handle($mh, $handle);

        }

        curl_multi_close($mh);

        DB::table('galleries')->insert($galleries);

        DB::table('before_galleries')
            ->whereIn('id', $deleteImages)
            ->delete();
//        $this->info("mb:" . memory_get_usage(true) / (1024 * 1024));
    }


}
