<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangePaymentProcessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('payment_process', function (Blueprint $table) {

            $table->morphs('model');

            if (Schema::hasColumn('payment_process', 'order_id')) {
                $table->dropForeign('payment_process_order_id_foreign');
                $table->dropColumn('order_id');
            }

            if (Schema::hasColumn('payment_process', 'subscription_id')) {
                $table->dropForeign('payment_process_subscription_id_foreign');
                $table->dropColumn('subscription_id');
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

		Schema::table('payment_process', function (Blueprint $table) {
			$table->dropColumn('model');
		});

    }
}
