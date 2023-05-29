<?php

namespace App\Telegram\Profile;

use App\Helpers\TelegramError;
use App\Models\User;
use App\Telegram\Helpers\DeleteMessage;
use App\Telegram\Helpers\Main;
use App\Telegram\Profile;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Facades\Validator;
use Log;

class ChangeLocationConversation extends Conversation
{
    public function askLocation(): bool
    {
        DeleteMessage::deleteMessage($this->getBot());

        $locale = Main::getLocale();

        $question = Question::create(__('telegram.' . TelegramError::ADD_LOCATION, locale: $locale))
            ->fallback(__('telegram.' . TelegramError::ADD_LOCATION, locale: $locale))
            ->callbackId('/change_location')
            ->addButtons([
                Button::create(__('telegram.' . TelegramError::CANCEL, locale: $locale))
                    ->value(TelegramError::CANCEL)
            ]);

        $this->ask($question, function (Answer $answer) use ($locale) {

            if (
                $answer->getValue() === TelegramError::CANCEL ||
                $answer->getText() === __('telegram.' . TelegramError::MENU, locale: $locale)
            ) {
                $this->say(
                    __('telegram.' . TelegramError::CANCELED, locale: $locale),
                    (new Profile)->getMenu()
                );
            }

            return true;
        });

        $this->say(
            __('telegram.' . TelegramError::ADD_LOCATION, locale: $locale),
            [
                'parse_mode'    => 'html',
                'reply_markup'  => json_encode([
                    'keyboard'  => [
                        [
                            [
                                'text'             => __('telegram.' . TelegramError::ADD_LOCATION, locale: $locale),
                                'request_location' => true,
                                'callback_data'    => 'change_location',
                            ]
                        ]
                    ],
                    'one_time_keyboard' => true,
                    'resize_keyboard' => true,
                ])
            ]
        );

        return true;
    }

    public function run()
    {
        $this->askLocation();
    }
}
