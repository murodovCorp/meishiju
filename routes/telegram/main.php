<?php

use App\Helpers\TelegramError;
use App\Telegram\Helpers\Main;
use App\Telegram\Middleware\CheckAuth;
use BotMan\BotMan\BotMan;

/** @var BotMan $router */
$router = resolve('botman');

$router->middleware->sending(new CheckAuth);

$locale = Main::getLocale();

$router->hears('/start', '\App\Telegram\Start@handle');
$router->hears('authorize', '\App\Telegram\Authorize@handle');
$router->hears('/menu', '\App\Telegram\Menu@handle');
$router->hears(__('telegram.' . TelegramError::MENU, locale: $locale), '\App\Telegram\Menu@handle');

$router->receivesContact('\App\Telegram\Authorize@handle');

include_once base_path('routes/telegram/profile.php');
include_once base_path('routes/telegram/shop.php');
//$router->fallback('\App\Telegram\FallBack@handle');
