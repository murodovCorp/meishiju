<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoteColumnInOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->double('service_fee')->nullable();
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->string('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
		Schema::table('orders', function (Blueprint $table) {
			$table->dropColumn('service_fee');
		});

		Schema::table('order_details', function (Blueprint $table) {
			$table->dropColumn('note');
		});
    }
}
