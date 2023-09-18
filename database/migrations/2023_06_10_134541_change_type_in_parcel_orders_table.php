<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeTypeInParcelOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('parcel_orders', function (Blueprint $table) {
            $table->foreignId('type_id')->constrained('parcel_order_settings')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('parcel_orders', function (Blueprint $table) {
            $table->dropForeign('parcel_orders_type_id_foreign');
            $table->dropColumn('type_id');
        });
    }
}
