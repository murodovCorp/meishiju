<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParcelOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('parcel_options', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('parcel_option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_option_id')
                ->constrained('parcel_options')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title', 191);
            $table->unique(['parcel_option_id', 'locale']);
            $table->softDeletes();
        });

        Schema::create('parcel_setting_options', function (Blueprint $table) {
            $table->foreignId('parcel_option_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('parcel_order_setting_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('parcel_options');
    }
}
