<?php
namespace App\Telegram;

use App\Contracts\Telegram\Dialogable;
use App\Helpers\TelegramError;
use App\Telegram\Helpers\Main;
use App\Traits\Loggable;
use BotMan\BotMan\BotMan;
use DB;
use Exception;
use Str;
use Throwable;

class Authorize implements Dialogable
{
    use Loggable;

    /**
     * @throws Exception
     */
    public function handle(BotMan $bot): void
    {
        try {
            $locale   = Main::getLocale();
            $uuid     = Str::uuid();
            $phone    = str_replace('+', '', $bot->getMessage()->getContact()->getPhoneNumber());

            DB::table('users')->updateOrInsert([
                'phone'             => $phone,
            ], [
                'deleted_at'        => null,
                'uuid'              => $uuid,
                'phone'             => $phone,
                'firstname'         => $bot->getUser()->getFirstName(),
                'lastname'          => $bot->getUser()->getLastName(),
                'birthday'          => '2000-01-01',
                'gender'            => 'male',
                'phone_verified_at' => now(),
                'active'            => true,
                'password'          => bcrypt(substr($uuid, -8)),
                'tg_user_id'        => $bot->getUser()->getId(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $buttons = Main::getMenu();

            $buttons['keyboard'] = [
                [
                    [
                        'text'            => __('telegram.' . TelegramError::MENU, locale: $locale),
                        'callback_data'   => 'menu',
                    ]
                ]
            ];

            $bot->reply(__('telegram.' . TelegramError::REGISTER, [
                'username' => $bot->getUser()->getFirstName() . ' ' . $bot->getUser()->getLastName(),
                'password' => substr($uuid, -8),
                'login'    => $phone
            ], $locale), [
                'parse_mode'   => 'html',
                'reply_markup' => json_encode($buttons)
            ]);
        } catch (Throwable $e) {
            $this->error($e);
        }
    }
}
