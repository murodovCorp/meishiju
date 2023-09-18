<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveRequiredSkuColumnInStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
	{
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn('sku');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->string('sku')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
	{
        Schema::table('stocks', function (Blueprint $table) {
            //
        });
    }
}
