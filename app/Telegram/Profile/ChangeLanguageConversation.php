<?php

namespace App\Telegram\Profile;

use App\Helpers\TelegramError;
use App\Models\Language;
use App\Models\User;
use App\Telegram\Helpers\DeleteMessage;
use App\Telegram\Helpers\Main;
use App\Telegram\Profile;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ChangeLanguageConversation extends Conversation
{
    public function askLanguage(): bool
    {
        DeleteMessage::deleteMessage($this->getBot());

        $locale = Main::getLocale();

        $buttons = $this->collectButtons(Language::languagesList()->where('active', 1)->toArray());

        $question = Question::create(__('telegram.' . TelegramError::ADD_LANGUAGE, locale: $locale))
            ->fallback(__('telegram.' . TelegramError::ADD_LANGUAGE, locale: $locale))
            ->callbackId('/change_language')
            ->addButtons($buttons);

        $this->ask($question, function (Answer $answer) use ($locale) {

            if (!$answer->isInteractiveMessageReply()) {
                $this->askLanguage();
                return true;
            }

            $value = $answer->getValue();

            if ($value == TelegramError::CANCEL) {
                $this->say(
                    __('telegram.' . TelegramError::CANCELED, locale: $locale),
                    (new Profile)->getMenu()
                );
                return true;
            }

            $key = $answer->getText();

            $validator = Validator::make(['key' => $key], [
                'key' => Rule::exists('languages', 'id')->whereNull('deleted_at'),
            ]);

            if ($validator->fails()) {
                $this->say(__('telegram.' . TelegramError::ADD_LANGUAGE, locale: $locale));
                return $this->askLanguage();
            }

            User::where('tg_user_id', $this->getBot()->getUser()->getId())->update([
                'language_id' => $key,
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
        $this->askLanguage();
    }

    public function collectButtons(array $data): array
    {
        $buttons = [];

        foreach (array_chunk($data, 2) as $items) {

            foreach ($items as $item) {
                $buttons[] = Button::create(data_get($item, 'title'))
                    ->value(data_get($item, 'id'));
            }

        }

        $buttons[] = Button::create(__('telegram.' . TelegramError::CANCEL, locale: Main::getLocale()))
            ->value(TelegramError::CANCEL);

        return $buttons;
    }

}
