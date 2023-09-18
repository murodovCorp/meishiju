<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParcelOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('parcel_orders', function (Blueprint $table) {
            $table->id()->from(1000);
            $table->foreignId('user_id')->nullable();
            $table->double('total_price', 20)
                ->nullable()
                ->default(0)
                ->comment('Сумма с учётом всех налогов и скидок');

            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();

            $table->float('rate')->nullable()->default(1);
            $table->string('note', 191)->nullable();

            $table->double('tax')->nullable()->default(1);
            $table->string('status')->nullable()->default('new');

            $table->json('address_from')->nullable();
            $table->string('phone_from')->nullable();
            $table->string('username_from')->nullable();

            $table->json('address_to')->nullable();
            $table->string('phone_to')->nullable();
            $table->string('username_to')->nullable();

            $table->double('delivery_fee', 20)->nullable()->default(0);
            $table->double('km')->nullable()->default(0);
            $table->foreignId('deliveryman_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->date('delivery_date')->nullable();
            $table->string('delivery_time')->nullable();

            $table->boolean('current')->nullable()->default(false);
            $table->string('img')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('parcel_order');
    }
}
