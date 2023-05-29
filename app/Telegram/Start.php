<?php

namespace App\Telegram;

use App\Helpers\TelegramError;
use App\Models\User;
use App\Telegram\Helpers\Main;
use App\Traits\Loggable;
use BotMan\BotMan\BotMan;
use Throwable;

class Start
{
    use Loggable;

    public function handle(BotMan $bot): void
    {
        try {
            $message = __('telegram.' . TelegramError::HELLO, locale: Main::getLocale());
            $bot->reply($message, $this->getMenu($bot));
        } catch (Throwable $e) {
            $this->error($e);
        }
    }

    public function getMenu(BotMan $bot): array
    {
        $doesntExist = User::where('tg_user_id', $bot->getUser()->getId())->doesntExist();
        $locale      = Main::getLocale();

        $buttons = [
            'keyboard' => [
                [
                    [
                        'text'            => __('telegram.' . TelegramError::MENU, locale: $locale),
                        'callback_data'   => 'menu',
                    ],
                ]
            ]
        ];

        if ($doesntExist) {
            $buttons['keyboard'][0][1] = [
                'text'            => __('telegram.' . TelegramError::NEED_AUTH, locale: $locale),
                'request_contact' => true,
                'callback_data'   => 'authorize',
            ];
        }

        return [
            'parse_mode'    => 'html',
            'reply_markup'  => json_encode($buttons),
        ];
    }
}
