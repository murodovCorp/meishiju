<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnInAdsPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('ads_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('ads_packages', 'banner_id')) {
                $table->foreignId('banner_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
//        Schema::table('ads_packages', function (Blueprint $table) {
//            $table->dropForeign('ads_packages_banner_id_foreign');
//            $table->dropColumn('banner_id');
//        });
    }
}
