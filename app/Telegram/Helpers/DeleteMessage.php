<?php
namespace App\Telegram\Helpers;

use BotMan\BotMan\BotMan;
use Log;
use Throwable;

class DeleteMessage {

    public static function deleteMessage(BotMan $bot): void
    {
        try {
            $messageId = request()->input('message.message_id');

            $bot->sendRequest('deleteMessage', [
                'chat_id'       => request()->input('message.from.id'),
                'message_id'    => $messageId - 1
            ]);
        } catch (Throwable $e) {
            Log::error('asdas '.$e->getMessage(), [
                request()->all(),
            ]);
        }
    }

}
