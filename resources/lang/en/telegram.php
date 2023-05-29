<?php

use App\Helpers\TelegramError;

$e = new TelegramError;

return [
    $e::HELLO                               => 'Hello! 🌟',
    $e::REGISTER                            => '✅ Thanks for register :username. Your login :login and password :password for website. Click the menu and go to your profile to change your password (<b>We highly recommend doing this</b>).',
    $e::ERROR                               => 'Sorry we checking this bug 😢',
    $e::PROFILE                             => 'Profile 👤',
    $e::MENU                                => 'Menu 📖',
    $e::PROFILE_WAS_SUCCESSFULLY_UPDATED    => 'Profile updated 👤',
    $e::AUTH                                => 'Sign in 👤',
    $e::NEED_AUTH                           => 'Please sign or log in 👤',
    $e::RESTAURANTS                         => 'Restaurants 🍽',
    $e::SHOPS                               => 'Magazines 🛒',
    $e::OTHER_SHOP                          => 'You cannot order from different restaurants or shops ❗️',
    $e::CLEAR_CART                          => 'Clear cart 🗑',
    $e::EMPTY_CART                          => 'Empty cart 🛍',
    $e::CHANGE_BIRTHDAY                     => 'Change Birthday 📅',
    $e::CHANGE_PASSWORD                     => 'Change Password ♒️',
    $e::CHANGE_LANGUAGE                     => 'Change Language 🏳️',
    $e::CHANGE_LOCATION                     => 'Change Address 📍',
    $e::ADD_LOCATION                        => 'Add Address 📍',
    $e::CHANGE_CURRENCY                     => 'Change Currency 💲',
    $e::ADD_BIRTHDAY                        => 'Input Birthday in format: 2022-12-31',
    $e::ADD_PASSWORD                        => 'Input Password ♒️',
    $e::ADD_LANGUAGE                        => 'Input Language 🏳',
    $e::ADD_CURRENCY                        => 'Input Currency 💲',
    $e::CANCEL                              => 'Cancel ❌',
    $e::CANCELED                            => 'Canceled',
    $e::PREV                                => '<',
    $e::NEXT                                => '>',
    $e::BACK                                => 'Back 🔙',
    $e::EMPTY_SHOPS                         => 'Shops not found 📄',
    $e::EMPTY_RESTAURANTS                   => 'Restaurants not found 📄',
    $e::EMPTY_PRODUCTS                      => 'Products not found 📄',
];
