<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Traits\Loggable;
use Illuminate\Database\Seeder;
use Throwable;

class CategoryStatusSeeder extends Seeder
{
    use Loggable;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach (Category::where('status', Category::PENDING)->get() as $category) {

            try {
                $category->update([
                    'status' => Category::PUBLISHED,
                ]);
            } catch (Throwable $e) {
                $this->error($e);
            }

        }

    }

}
