<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveSoftDeletesFromParcel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasColumn('parcel_orders', 'deleted_at')) {
            Schema::table('parcel_orders', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('parcel_order_settings', 'deleted_at')) {
            Schema::table('parcel_order_settings', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('parcel_options', 'deleted_at')) {
            Schema::table('parcel_options', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('parcel_option_translations', 'deleted_at')) {
            Schema::table('parcel_option_translations', function (Blueprint $table) {
                $table->dropSoftDeletes();
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
        Schema::table('parcel', function (Blueprint $table) {
            //
        });
    }
}
