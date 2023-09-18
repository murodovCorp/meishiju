<?php

use App\Models\ShopAdsPackage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdsPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('ads_packages', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(false)->comment('Активный');
            $table->string('time_type')->default('day')->comment('Тип времени рекламы: минут,час,день,недель,месяц,год');
            $table->smallInteger('time')->comment('Время');
            $table->double('price')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('ads_package_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ads_package_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title', 191);
            $table->softDeletes();
            $table->unique(['ads_package_id', 'locale']);
        });

        Schema::create('shop_ads_packages', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(false);
            $table->string('status')->default(ShopAdsPackage::NEW);
            $table->integer('position_page')->nullable()->default(2147483647);
            $table->foreignId('ads_package_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('banner_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->dateTime('expired_at')->nullable();
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
        Schema::dropIfExists('shop_ads_packages');
        Schema::dropIfExists('ads_package_translation');
        Schema::dropIfExists('ads_packages');
    }
}
