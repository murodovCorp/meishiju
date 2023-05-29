<?php

use App\Telegram\Product\AddAddonStockConversation;
use App\Telegram\Product\AddStockConversation;
use App\Telegram\Product\ProductPaginateByShopIdConversation;
use App\Telegram\Product\StockConversation;
use App\Telegram\Product\StocksPaginateByProductIdConversation;
use App\Telegram\Shop\RestaurantPaginateConversation;
use App\Telegram\Shop\ShopPaginateConversation;
use BotMan\BotMan\BotMan;

/** @var $router */
$router->hears('/shop_offset_{offset}', function (BotMan $bot, $offset) {
    $bot->startConversation(new ShopPaginateConversation($offset));
});

/** @var $router */
$router->hears('/restaurant_offset_{offset}', function (BotMan $bot, $offset) {
    $bot->startConversation(new RestaurantPaginateConversation($offset));
});

$router->hears('/product_offset_{shop_id}_{offset}', function (BotMan $bot, $shopId, $offset) {
    $bot->startConversation(new ProductPaginateByShopIdConversation($shopId, $offset));
});

$router->hears('/stocks_{product_id}', function (BotMan $bot, $productId) {
    $bot->startConversation(new StocksPaginateByProductIdConversation($productId));
});

$router->hears('/stock_{stock_id}', function (BotMan $bot, $stockId) {
    $bot->startConversation(new StockConversation($stockId));
});

$router->hears('/minus_to_cart_{stock_id}', function (BotMan $bot, $stockId) {
    $bot->startConversation(new AddStockConversation($stockId, type: 'minus'));
});

$router->hears('/plus_to_cart_{stock_id}', function (BotMan $bot, $stockId) {
    $bot->startConversation(new AddStockConversation($stockId, type: 'plus'));
});

$router->hears('/addon_minus_to_cart_{stock_id}_addon_{addon_stock_id}',
    function (BotMan $bot, $stockId, $addonStockId) {
        $bot->startConversation(new AddAddonStockConversation($stockId, $addonStockId, 'minus'));
});

$router->hears('/addon_plus_to_cart_{stock_id}_addon_{addon_stock_id}',
    function (BotMan $bot, $stockId, $addonStockId) {
        $bot->startConversation(new AddAddonStockConversation($stockId, $addonStockId, 'plus'));
});
