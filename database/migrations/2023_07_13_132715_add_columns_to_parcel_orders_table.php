<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToParcelOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('parcel_orders', function (Blueprint $table) {
            $table->string('qr_value')->nullable();
            $table->boolean('notify')->default(0);
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
			$table->dropColumn('qr_value');
			$table->dropColumn('notify');
        });
    }
}
