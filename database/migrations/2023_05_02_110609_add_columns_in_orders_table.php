<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsInOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::dropIfExists('internal_order_coupons');
        Schema::dropIfExists('internal_order_details');
        Schema::dropIfExists('internal_orders');

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('waiter_id')->nullable()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('cook_id')->nullable()->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger('table_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('user_booking_id')->nullable();
            $table->foreignId('user_id')->nullable()->change()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->double('waiter_fee', 20)->default(0);
        });

        Schema::table('shops', function (Blueprint $table) {
           $table->smallInteger('service_fee')->nullable()->default(0);
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
            //
        });
    }
}
