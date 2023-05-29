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

class ChangePasswordConversation extends Conversation
{

    public function askPassword(): bool
    {
        DeleteMessage::deleteMessage($this->getBot());

        $locale = Main::getLocale();

        $question = Question::create(__('telegram.' . TelegramError::ADD_PASSWORD, locale: $locale))
            ->fallback(__('telegram.' . TelegramError::ADD_PASSWORD, locale: $locale))
            ->callbackId('/change_password')
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
                return true;
            }

            $password = $answer->getText();

            $validator = Validator::make(['key' => $password], [
                'key' => 'string|min:6',
            ]);

            if ($validator->fails()) {
                $this->say(
                    __('telegram.' . TelegramError::ADD_PASSWORD, locale: $locale),
                    (new Profile)->getMenu()
                );
                return $this->askPassword();
            }

            User::where('tg_user_id', $this->getBot()->getUser()->getId())->update([
                'password' => bcrypt($password),
            ]);

            $this->say(
                __('telegram.' . TelegramError::PROFILE_WAS_SUCCESSFULLY_UPDATED, locale: $locale),
                (new Profile)->getMenu()
            );

            return true;
        });

        return true;
    }

    public function run()
    {
        $this->askPassword();
    }
}
