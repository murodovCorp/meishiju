<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsForProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('vegetarian')->default(false);
            $table->string('kcal', 10)->nullable()->default(0);
            $table->string('carbs', 10)->nullable()->default(0);
            $table->string('protein', 10)->nullable()->default(0);
            $table->string('fats', 10)->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('vegetarian');
            $table->dropColumn('kcal');
            $table->dropColumn('carbs');
            $table->dropColumn('protein');
            $table->dropColumn('fats');
        });
    }
}
