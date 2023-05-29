<?php

namespace App\Telegram;

use App\Helpers\TelegramError;
use App\Telegram\Helpers\Main;
use App\Traits\Loggable;
use BotMan\BotMan\BotMan;
use Throwable;

class Menu
{
    use Loggable;

    public function handle(BotMan $bot): void
    {
        try {
            $message = __('telegram.' . TelegramError::MENU, locale: Main::getLocale());
            $bot->reply($message, [
                'parse_mode'    => 'html',
                'reply_markup'  => json_encode(Main::getMenu()),
            ]);
        } catch (Throwable $e) {
            $this->error($e);
        }
    }

}
