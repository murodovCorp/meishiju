<?php

namespace App\Http\Controllers\Web;

use App\Traits\Loggable;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\TelegramDriver;
use Log;
use Throwable;

class TelegramBotController
{
    use Loggable;

    public function webhook(): void
    {
        try {
            DriverManager::loadDriver(TelegramDriver::class);

            $router = BotManFactory::create([
                'telegram' => [
                    'token' => config('app.telegram_bot_token'),
                    'hideInlineKeyboard' => false
                ],
            ]);

            include_once base_path('routes/telegram/main.php');

            $router->listen();
//            Log::error('tg_req', request()->all());
        } catch (Throwable $e) {
            $this->error($e);
        }
    }
}
