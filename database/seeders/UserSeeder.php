<?php

namespace Database\Seeders;

use App\Models\DeliveryManSetting;
use App\Models\Language;
use App\Models\Shop;
use App\Models\ShopTag;
use App\Models\ShopTranslation;
use App\Models\User;
use App\Traits\Loggable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Throwable;

class UserSeeder extends Seeder
{
    use Loggable;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $users = [
            [
                'id' => 101,
                'uuid' => Str::uuid(),
                'firstname' => 'Admin',
                'lastname' => 'Admin',
                'email' => 'admin@githubit.com',
                'phone' => '998911902494',
                'birthday' => '1991-08-10',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('githubit'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 102,
                'uuid' => Str::uuid(),
                'firstname' => 'User',
                'lastname' => 'User',
                'email' => 'user@gmail.com',
                'phone' => '998911902595',
                'birthday' => '1993-12-30',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('user123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 103,
                'uuid' => Str::uuid(),
                'firstname' => 'Seller',
                'lastname' => 'Seller',
                'email' => 'sellers@githubit.com',
                'phone' => '998911902696',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('seller'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 104,
                'uuid' => Str::uuid(),
                'firstname' => 'Manager',
                'lastname' => 'Manager',
                'email' => 'manager@githubit.com',
                'phone' => '998911902616',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('manager'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 105,
                'uuid' => Str::uuid(),
                'firstname' => 'Moderator',
                'lastname' => 'Moderator',
                'email' => 'moderator@githubit.com',
                'phone' => '998911902116',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('moderator'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 106,
                'uuid' => Str::uuid(),
                'firstname' => 'Delivery',
                'lastname' => 'Delivery',
                'email' => 'delivery@githubit.com',
                'phone' => '998911912116',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('delivery'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 107,
                'uuid' => Str::uuid(),
                'firstname' => 'Waiter',
                'lastname' => 'Waiter',
                'email' => 'waiter@githubit.com',
                'phone' => '9989119121245',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('waiter'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 108,
                'uuid' => Str::uuid(),
                'firstname' => 'Cook',
                'lastname' => 'Cook',
                'email' => 'cook@githubit.com',
                'phone' => '9989119121241',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('cook'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 109,
                'uuid' => Str::uuid(),
                'firstname' => 'user',
                'lastname' => 'user',
                'email' => 'user@githubit.com',
                'phone' => '9989119121242',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('githubit'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            try {
                User::updateOrInsert(['email' => data_get($user, 'email')], $user);
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

		try {
			User::find(101)->syncRoles('admin');

			User::find(102)->syncRoles('user');

			User::find(109)->syncRoles('user');

			User::find(103)->syncRoles('seller');

			User::find(104)->syncRoles('manager');
		} catch (Throwable $e) {
			$this->error($e);
			$this->command->error('error in syncRoles: ' . $e->getMessage());
		}

		try {

			try {
				$deliveryman = User::find(106);

				$deliveryman->deliveryManSetting()->updateOrCreate([
					'user_id' => $deliveryman->id
				], [
					'type_of_technique' => DeliveryManSetting::BENZINE,
					'brand'             => 'BMW',
					'model'             => 'M5 F90 competition',
					'number'            => 'M111MM',
					'color'             => '#000',
					'online'            => 1,
					'location'          => [
						'latitude'  => 36.966428,
						'longitude' => -95.844032,
					],
				]);

				$deliveryman->syncRoles('deliveryman');

			} catch (Throwable $e) {
				$this->error($e);
				$this->command->error('error in $deliveryman:' . $e->getMessage());
			}

			$shop = Shop::updateOrCreate([
				'user_id'           => 103,
			],[
				'uuid'              => Str::uuid(),
				'location'          => [
					'latitude'          => -69.3453324,
					'longitude'         => 69.3453324,
				],
				'phone'             => '+1234567',
				'show_type'         => 1,
				'open'              => 1,
				'background_img'    => 'url.webp',
				'logo_img'          => 'url.webp',
				'status'            => 'approved',
				'status_note'       => 'approved',
				'delivery_time'     => [
					'from'              => '10',
					'to'                => '90',
					'type'              => 'minute',
				],
			]);

			try {

				User::where('email', 'cook@githubit.com')->first()->syncRoles('cook');

				$waiter = User::where('email', 'waiter@githubit.com')->first();
				$waiter->syncRoles('waiter');
				$waiter->invitations()->withTrashed()->updateOrCreate([
					'shop_id' => $shop->id,
				], [
					'role'       => 'waiter',
					'status'     => 1,
					'deleted_at' => null
				]);
			} catch (Throwable $e) {
				$this->error($e);
				$this->command->error('error in deliveryman,waiter:' . $e->getMessage());
			}

			try {
				$moderator = User::find(105)->syncRoles('moderator');

				$moderator->invitations()->withTrashed()->updateOrCreate([
					'shop_id' => $shop->id,
				], [
					'role'       => 'seller',
					'status'     => 1,
					'deleted_at' => null,
				]);
			} catch (Throwable $e) {
				$this->error($e);
				$this->command->error('error in invitations:' . $e->getMessage());
			}

			try {
				ShopTranslation::updateOrCreate([
					'shop_id'       => $shop->id,
					'locale'        => data_get(Language::languagesList()->first(), 'locale', 'en'),
				],[
					'description'   => 'shop desc',
					'title'         => 'shop title',
					'address'       => 'address',
				]);

				$shop->tags()->sync(ShopTag::pluck('id')->toArray());
			} catch (Throwable $e) {
				$this->error($e);
				$this->command->error('error in shop translation:' . $e->getMessage());
			}

		} catch (Throwable $e) {
			$this->error($e);
			$this->command->error('error in shop:' . $e->getMessage());
		}


    }

}
