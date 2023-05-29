<?php

use App\Telegram\Profile\{ChangeCurrencyConversation,
    ChangeLanguageConversation,
    ChangeLocationConversation,
    ChangePasswordConversation,
    ChangeBirthdayConversation};
use BotMan\BotMan\BotMan;

/** @var $router */

$router->hears('/profile', '\App\Telegram\Profile@handle');

$router->hears('/change_birthday', function (BotMan $bot) {
    $bot->startConversation(new ChangeBirthdayConversation);
});

$router->hears('/change_location', function (BotMan $bot) {
    $bot->startConversation(new ChangeLocationConversation);
});

$router->receivesLocation('\App\Telegram\Profile\UpdateLocationConversation@handle');

$router->hears('/change_password', function (BotMan $bot) {
    $bot->startConversation(new ChangePasswordConversation);
});

$router->hears('/change_language', function (BotMan $bot) {
    $bot->startConversation(new ChangeLanguageConversation);
});

$router->hears('/change_currency', function (BotMan $bot) {
    $bot->startConversation(new ChangeCurrencyConversation);
});

$router->hears('/clear_cart', '\App\Telegram\Profile\Cart\ClearCart@handle');
$router->hears('/cart', '\App\Telegram\Profile\Cart\ShowCart@handle');
