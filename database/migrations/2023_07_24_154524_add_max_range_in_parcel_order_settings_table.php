<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaxRangeInParcelOrderSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('parcel_order_settings', function (Blueprint $table) {
            $table->smallInteger('max_range')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('parcel_order_settings', function (Blueprint $table) {
            $table->dropColumn('max_range');
        });
    }
}
