<?php

namespace App\Telegram\Profile;

use App\Helpers\TelegramError;
use App\Models\Currency;
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

class ChangeCurrencyConversation extends Conversation
{
    public function askCurrency(): bool
    {
        DeleteMessage::deleteMessage($this->getBot());

        $locale = Main::getLocale();

        $buttons = $this->collectButtons(Currency::currenciesList()->where('active', 1)->toArray());

        $question = Question::create(__('telegram.' . TelegramError::ADD_CURRENCY, locale: $locale))
            ->fallback(__('telegram.' . TelegramError::ADD_CURRENCY, locale: $locale))
            ->callbackId('/change_currency')
            ->addButtons($buttons);

        $this->ask($question, function (Answer $answer) use ($locale) {

            if (!$answer->isInteractiveMessageReply()) {
                $this->askCurrency();
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
                'key' => Rule::exists('currencies', 'id')->whereNull('deleted_at'),
            ]);

            if ($validator->fails()) {
                $this->say(__('telegram.' . TelegramError::ADD_CURRENCY, locale: $locale));
                return $this->askCurrency();
            }

            User::where('tg_user_id', $this->getBot()->getUser()->getId())->update([
                'currency_id' => $key,
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
        $this->askCurrency();
    }

    public function collectButtons(array $data): array
    {
        $buttons = [];

        foreach (array_chunk($data, 2) as $items) {

            foreach ($items as $item) {
                $buttons[] = Button::create(data_get($item, 'title') . ' ' . data_get($item, 'symbol'))
                    ->value(data_get($item, 'id'));
            }

        }

        $buttons[] = Button::create(__('telegram.' . TelegramError::CANCEL, locale: Main::getLocale()))
            ->value(TelegramError::CANCEL);

        return $buttons;
    }

}
