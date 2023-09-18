<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteTypeInParcelOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
	{
        if (Schema::hasColumn('parcel_orders', 'type')) {
			Schema::table('parcel_orders', function (Blueprint $table) {
				$table->dropColumn('type');
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
        Schema::table('parcel_orders', function (Blueprint $table) {
            //
        });
    }
}
