<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Language;
use App\Traits\Loggable;
use Illuminate\Database\Seeder;
use Throwable;

class CategorySeeder extends Seeder
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

        foreach (Category::TYPES as $key => $value) {

            try {
                $category = Category::create([
                    'keywords' => $key,
                    'type' => $value,
                ]);

                CategoryTranslation::updateOrCreate([
                    'category_id' => $category->id,
                    'locale'      => data_get($locale, 'locale', 'en'),
                ], [
                    'title'       => $key
                ]);
            } catch (Throwable $e) {
                $this->error($e);
            }

        }

    }

}
