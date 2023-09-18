<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChangeEmailSeeder extends Seeder
{
    /**
	 * Don`t add this seeder in DatabaseSeeder(run)
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
	{
		foreach (User::get() as $user) {
			$user->update([
				'email' => $user->id . Str::substr($user->email, 0, Str::length($user->id)) . '******',
				'phone' => $user->id . Str::substr($user->phone, 0, Str::length($user->id)) . 000000,
				'firebase_token' => null,
				'location' => null,
				'ip_address' => null,
				'birthday' => '2000-09-09'
			]);
		}
    }
}
