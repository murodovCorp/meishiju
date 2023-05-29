<?php

namespace Database\Seeders;

use App\Models\ShopTagTranslation;
use App\Models\Language;
use App\Models\ShopTag;
use App\Traits\Loggable;
use Illuminate\Database\Seeder;
use Throwable;

class ShopTagSeeder extends Seeder
{
    use Loggable;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $locale = Language::languagesList()->first();

        if (!ShopTag::first()?->id) {

            try {
                $shopTag = ShopTag::create();

                ShopTagTranslation::create([
                    'shop_tag_id' => $shopTag->id,
                    'locale'      => data_get($locale, 'locale', 'en'),
                    'title'       => 'Halal'
                ]);
            } catch (Throwable $e) {
                $this->error($e);
            }

        }

    }
}
