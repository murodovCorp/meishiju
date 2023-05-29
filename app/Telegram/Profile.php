<?php

namespace App\Telegram;

use App\Helpers\TelegramError;
use App\Telegram\Helpers\Main;
use App\Traits\Loggable;
use BotMan\BotMan\BotMan;
use Log;
use Throwable;

class Profile
{
    use Loggable;

    public function handle(BotMan $bot): void
    {
        try {
            $message = __('telegram.' . TelegramError::PROFILE, locale: Main::getLocale());
            $bot->reply($message, $this->getMenu());
        } catch (Throwable $e) {
            $this->error($e);
        }
    }

    public function getMenu(): array
    {
        $locale = Main::getLocale();

        $buttons = [
            [
                [
                    'text' => __('telegram.' . TelegramError::CHANGE_BIRTHDAY, locale: $locale),
                    'callback_data' => '/change_birthday',
                ],
                [
                    'text' => __('telegram.' . TelegramError::CHANGE_PASSWORD, locale: $locale),
                    'callback_data' => '/change_password',
                ],
            ],
            [
                [
                    'text' => __('telegram.' . TelegramError::CHANGE_LANGUAGE, locale: $locale),
                    'callback_data' => '/change_language',
                ],
                [
                    'text' => __('telegram.' . TelegramError::CHANGE_CURRENCY, locale: $locale),
                    'callback_data' => '/change_currency',
                ],
            ],
            [
                [
                    'text' => __('telegram.' . TelegramError::CHANGE_LOCATION, locale: $locale),
                    'request_location' => true,
                    'callback_data'    => '/change_location',
                ],
                [
                    'text' => __('telegram.' . TelegramError::BACK, locale: $locale),
                    'callback_data' => '/menu',
                ],
            ],
        ];

        return [
            'parse_mode'    => 'html',
            'reply_markup'  => json_encode([
                'inline_keyboard'  => $buttons,
                'keyboard'  => []
            ])
        ];
    }

    public function update(BotMan $bot): void
    {
        Log::error('update', [
            request()->all(),
            $bot->getMatches(),
            $bot->getBotMessages(),
            $bot->getConversationAnswer(),
        ]);

        try {
            $bot->reply(
                __('telegram.' . TelegramError::PROFILE_WAS_SUCCESSFULLY_UPDATED, locale: Main::getLocale())
            );
        } catch (Throwable $e) {
            $this->error($e);
        }
    }
}
