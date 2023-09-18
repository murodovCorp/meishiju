<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ShopCategory;
use App\Services\ModelLogService\ModelLogService;
use Exception;
use Illuminate\Support\Str;

class CategoryObserver
{
	/**
	 * Handle the Category "creating" event.
	 *
	 * @param Category $category
	 * @return void
	 * @throws Exception
	 */
	public function creating(Category $category): void
	{
		$category->uuid = Str::uuid();
	}

	/**
	 * Handle the Category "created" event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function created(Category $category): void
	{
		(new ModelLogService)->logging($category, $category->getAttributes(), 'created');
	}

	/**
	 * Handle the Category "updated" event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function updated(Category $category): void
	{
		(new ModelLogService)->logging($category, $category->getAttributes(), 'updated');
	}

	/**
	 * Handle the Category "deleted" event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function deleted(Category $category): void
	{

		if ($category->children) {
			foreach ($category->children as $child) {
				$this->relationDelete($child);
			}
		}

		$this->relationDelete($category);

		(new ModelLogService)->logging($category, $category->getAttributes(), 'deleted');
	}

	public function relationDelete($category): void
	{
		foreach (ShopCategory::where('category_id', $category->id)->get() as $shopCategory) {
			$shopCategory->delete();
		}

		foreach (Product::where('category_id', $category->id)->get() as $product) {
			$product->update([
				'category_id' => null
			]);
		}
	}

	/**
	 * Handle the Category "restored" event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function restored(Category $category): void
	{
		(new ModelLogService)->logging($category, $category->getAttributes(), 'restored');
	}
}
