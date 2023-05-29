<?php
namespace App\Telegram\Middleware;

use App\Helpers\TelegramError;
use App\Models\User;
use App\Telegram\Helpers\Main;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\Middleware\Sending;

class CheckAuth implements Sending
{
    /**
     * Handle an outgoing message payload before/after it
     * hits the message service.
     *
     * @param mixed $payload
     * @param callable $next
     * @param BotMan $bot
     *
     * @return mixed
     */
    public function sending($payload, $next, BotMan $bot): mixed
    {
        $locale = Main::getLocale();

        $checkAuth = $this->isAuth(data_get($payload, 'chat_id'));

        if (!$checkAuth) {
            $payload['text']            =  __('telegram.' . TelegramError::NEED_AUTH, locale: $locale);
            $payload['parse_mode']      = 'html';
            $payload['reply_markup'] = json_encode([
                'keyboard' => [
                    [
                        [
                            'text'            => __('telegram.' . TelegramError::AUTH, locale: $locale),
                            'request_contact' => true,
                            'callback_data'   => 'authorize',
                        ]
                    ]
                ],
                'one_time_keyboard' => true,
                'resize_keyboard' => true,
            ]);
        }


        return $next($payload);
    }

    public function authMenu(BotMan $bot)
    {
        return $bot->reply((string)view('telegram.start'), [
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'keyboard' => [
                    [
                        [
                            'text'              => 'Авторизоваться',
                            'request_contact'   => true,
                            'callback_data'     => 'authorize',
                        ]
                    ]
                ],
                'one_time_keyboard' => false,
                'resize_keyboard' => true,
            ]),
        ]);

    }

    public function isAuth($id): bool
    {
        if (!$id) {
            return false;
        }

        return User::where('tg_user_id', $id)->exists();
    }
}
