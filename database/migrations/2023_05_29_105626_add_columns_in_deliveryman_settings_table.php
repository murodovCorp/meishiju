<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsInDeliverymanSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('deliveryman_settings', function (Blueprint $table) {
            $table->smallInteger('width')->nullable()->default(100);
            $table->smallInteger('height')->nullable()->default(100);
            $table->smallInteger('length')->nullable()->default(100);
            $table->integer('kg')->nullable()->default(100);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('deliveryman_settings', function (Blueprint $table) {
            $table->dropColumn('width');
            $table->dropColumn('height');
            $table->dropColumn('length');
            $table->dropColumn('kg');
        });
    }
}
