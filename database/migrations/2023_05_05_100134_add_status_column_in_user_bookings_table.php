<?php

use App\Models\Booking\UserBooking;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusColumnInUserBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('user_bookings', function (Blueprint $table) {
            $table->string('status')->default(UserBooking::NEW);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('user_bookings', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
