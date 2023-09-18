<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Language;
use Artisan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class CategoryImport extends BaseImport implements ToCollection, WithHeadingRow
{
	use Importable;

	public function __construct(private string $language, private ?int $shopId = null) {}

	/**
	 * @param Collection $collection
	 * @return void
	 */
	public function collection(Collection $collection): void
	{
		$language = Language::where('default', 1)->first();

		foreach ($collection as $row) {

			$type = data_get($row, 'type');

			if (!data_get($row, 'img_urls', '')) {
				continue;
			}

			try {

				DB::transaction(function () use ($row, $language, $type) {

					$category = Category::withTrashed()->updateOrCreate([
						'keywords'  => data_get($row, 'keywords', ''),
						'type'      => empty($type) ? Category::MAIN : data_get(Category::TYPES, $type, Category::MAIN),
						'parent_id' => data_get($row, 'parent_id') > 0 ? data_get($row, 'parent_id') : 0,
						'shop_id'   => $this->shopId,
					], [
						'active'     => data_get($row, 'active') === 'active' ? 1 : 0,
						'deleted_at' => null
					]);

					$category->translation()->withTrashed()->updateOrInsert([
						'category_id' => $category->id,
						'locale'      => $this->language ?? $language,
					], [
						'title'       => data_get($row, 'title', ''),
						'description' => data_get($row, 'description', ''),
						'deleted_at' => null
					]);

					$this->downloadImages($category, data_get($row, 'img_urls', ''));

					return true;
				});

			} catch (Throwable $e) {
				$this->error($e);
			}

		}

		Artisan::call('update:models:galleries');
	}

	public function headingRow(): int
	{
		return 1;
	}

	public function batchSize(): int
	{
		return 200;
	}

	public function chunkSize(): int
	{
		return 200;
	}
}
