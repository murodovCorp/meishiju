<?php

use App\Models\ShopAdsPackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnInShopAdsPackages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
	{

		if (!Schema::hasColumn('shop_ads_packages', 'status')) {

			Schema::table('shop_ads_packages', function (Blueprint $table) {
				$table->string('status')->default(ShopAdsPackage::NEW);
        	});

		}

	}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
	{
        Schema::table('shop_ads_packages', function (Blueprint $table) {
            //
        });
    }
}
