<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('model_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->json('data')->nullable();
            $table->timestamp('created_at');
            $table->unsignedBigInteger('created_by');
            $table->string('type')->default('log');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('model_logs');
    }
}
