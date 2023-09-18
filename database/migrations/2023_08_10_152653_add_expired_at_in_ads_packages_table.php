<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpiredAtInAdsPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
	{
		if (!Schema::hasColumn('shop_ads_packages', 'position_page')) {

			Schema::table('shop_ads_packages', function (Blueprint $table) {
				$table->integer('position_page')->nullable()->default(2147483647);
			});

		}

		if (!Schema::hasColumn('shop_ads_packages', 'expired_at')) {

			Schema::table('shop_ads_packages', function (Blueprint $table) {
				$table->dateTime('expired_at')->nullable();
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
        Schema::table('ads_packages', function (Blueprint $table) {
            //
        });
    }
}
