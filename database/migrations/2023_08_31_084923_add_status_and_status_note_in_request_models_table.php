<?php

use App\Models\RequestModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusAndStatusNoteInRequestModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
	{
        Schema::table('request_models', function (Blueprint $table) {
            $table->string('status')->default(RequestModel::STATUS_PENDING);
			$table->string('status_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
	{
        Schema::table('request_models', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('status_note');
        });
    }
}
