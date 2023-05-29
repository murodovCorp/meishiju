<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCreatedByInModelLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('model_logs', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });

        Schema::table('model_logs', function (Blueprint $table) {
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('model_logs', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });

        Schema::table('model_logs', function (Blueprint $table) {
            $table->dropForeign('model_logs_created_by_foreign');
            $table->dropColumn('created_by');
        });
    }
}
