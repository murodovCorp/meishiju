<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParcelOrderSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('parcel_order_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('img')->nullable();
            $table->smallInteger('min_width')->default(0);
            $table->smallInteger('min_height')->default(0);
            $table->smallInteger('min_length')->default(0);
            $table->smallInteger('max_width')->default(0);
            $table->smallInteger('max_height')->default(0);
            $table->smallInteger('max_length')->default(0);
            $table->integer('min_g')->default(100);
            $table->integer('max_g')->default(100);
            $table->double('price')->default(0);
            $table->double('price_per_km')->default(0);
            $table->boolean('special')->default(false);
            $table->double('special_price')->nullable()->default(0);
            $table->double('special_price_per_km')->nullable()->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('parcel_order_settings');
    }
}
