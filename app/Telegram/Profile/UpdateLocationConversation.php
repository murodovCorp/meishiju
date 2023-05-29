<?php

namespace App\Telegram\Profile;

use App\Helpers\TelegramError;
use App\Telegram\Helpers\Main;
use App\Telegram\Profile;
use App\Traits\Loggable;
use BotMan\BotMan\BotMan;
use DB;
use Exception;
use Throwable;

class UpdateLocationConversation
{
    use Loggable;

    /**
     * @throws Exception
     */
    public function handle(BotMan $bot): void
    {
        try {
            $locale   = Main::getLocale();

            \Log::error('$bot->getMessage()->getLocation()->getPayload()', $bot->getMessage()->getLocation()->getPayload());
            DB::table('users')->updateOrInsert([
                'tg_user_id' => $bot->getUser()->getId(),
            ], [
                'deleted_at' => null,
                'location'   => $bot->getMessage()->getLocation()->getPayload(),
            ]);

            $bot->reply(
                __('telegram.' . TelegramError::PROFILE_WAS_SUCCESSFULLY_UPDATED, locale: $locale),
                (new Profile)->getMenu()
            );
        } catch (Throwable $e) {
            $this->error($e);
        }
    }
}
