<?php

namespace App\Telegram\Profile\Cart;

use App\Helpers\TelegramError;
use App\Models\Cart;
use App\Models\User;
use App\Telegram\Helpers\Main;
use App\Traits\Loggable;
use BotMan\BotMan\BotMan;
use Throwable;

class ClearCart
{
    use Loggable;

    public function handle(BotMan $bot): void
    {
        try {
            $user = User::where('tg_user_id', $bot->getUser()->getId())->first();

            Cart::where('owner_id', $user->id)->delete();

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
