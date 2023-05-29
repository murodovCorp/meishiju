<?php

use App\Helpers\TelegramError;

$e = new TelegramError;

return [
    $e::HELLO                               => 'Добрый день! 🌟',
    $e::REGISTER                            => '✅ Спасибо за регистрацию :username. Ваш логин :login и пароль :password для входа на сайт. Нажмите меню и перейдите в профиль, что бы поменять пароль (<b>Настоятельно рекомендуем это сделать</b>).',
    $e::ERROR                               => 'Приносим свои извинения мы исправляем эту ошибку 😢',
    $e::PROFILE                             => 'Профиль 👤',
    $e::MENU                                => 'Меню 📖',
    $e::PROFILE_WAS_SUCCESSFULLY_UPDATED    => 'Профиль обновлён 👤',
    $e::AUTH                                => 'Авторизоваться 👤',
    $e::NEED_AUTH                           => 'Пожалуйста авторизуйтесь или войдите 👤',
    $e::RESTAURANTS                         => 'Рестораны 🍽',
    $e::SHOPS                               => 'Магазины 🛒',
    $e::OTHER_SHOP                          => 'Вы не можете заказать из разных ресторанов или магазинов ❗️',
    $e::CLEAR_CART                          => 'Очистить корзину 🗑',
    $e::EMPTY_CART                          => 'Карзина пуста 🛍',
    $e::CHANGE_BIRTHDAY                     => 'Дата рождения 📅',
    $e::CHANGE_PASSWORD                     => 'Сменить пароль ♒️',
    $e::CHANGE_LANGUAGE                     => 'Сменить язык 🏳️',
    $e::CHANGE_LOCATION                     => 'Сменить адрес 📍️',
    $e::ADD_LOCATION                        => 'Отправить адрес 📍️',
    $e::CHANGE_CURRENCY                     => 'Сменить валюту 💲',
    $e::ADD_BIRTHDAY                        => 'Введите дату рождения в формате 2022-12-31 📅',
    $e::ADD_PASSWORD                        => 'Введите пароль ♒️',
    $e::ADD_LANGUAGE                        => 'Выберите язык 🏳',
    $e::ADD_CURRENCY                        => 'Выберите валюту 💲',
    $e::CANCEL                              => 'Отменить ❌',
    $e::CANCELED                            => 'Отменено ✔️',
    $e::PREV                                => '<',
    $e::NEXT                                => '>',
    $e::BACK                                => 'Назад 🔙',
    $e::EMPTY_SHOPS                         => 'Магазинов нет 📄',
    $e::EMPTY_RESTAURANTS                   => 'Ресторанов нет 📄',
    $e::EMPTY_PRODUCTS                      => 'Продуктов нет 📄',
];
