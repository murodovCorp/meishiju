<?php

namespace App\Telegram\Product;

use App\Helpers\TelegramError;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Currency;
use App\Models\Stock;
use App\Models\User;
use App\Models\UserCart;
use App\Services\CartService\CartService;
use App\Telegram\Helpers\Main;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Attachments\Image;
use Illuminate\Support\Str;

class StockConversation extends Conversation
{
    public function __construct(
        private $stockId = null,
        private User|null $user = null,
    ) {}

    public function handle(BotMan $bot): bool
    {
        $this->user = User::where('tg_user_id', $bot->getUser()->getId())->first();

        request()->offsetSet('currency_id', $this->user?->currency_id);

        $locale = Main::getLocale();

        /** @var Stock|null $stock */
        $stock = Stock::with([
            'countable.translation' => fn($q) => $q
                ->where('locale', $locale)->orWhere('locale', $locale),
            'stockExtras',
//            'stockExtras.group.translation' => fn($q) => $q
//                ->where('locale', $locale)->orWhere('locale', $locale),
            'addons.addon.stock',
            'addons.addon.translation' => fn($q) => $q->where('locale', $locale),
        ])
            ->find($this->stockId);

        $cart = Cart::where('owner_id', $this->user->id)->first();

        if (!empty($cart) && $cart->shop_id !== $stock?->countable?->shop_id) {
            $bot->reply(__('telegram.' . TelegramError::OTHER_SHOP, locale: $locale), [
                'parse_mode'   => 'html',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => __('telegram.' . TelegramError::CLEAR_CART, locale: $locale),
                                'callback_data' => '/clear_cart'
                            ]
                        ]
                    ]
                ])
            ]);
            return true;
        }

        $result = $this->collectData($stock);

        $attachment = new Image(data_get($result, 'img_url'));
        $message    = OutgoingMessage::create(data_get($result, 'message'))->withAttachment($attachment);

        $bot->reply($message, [
            'parse_mode'   => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => data_get($result, 'params')
            ])
        ]);

        return true;
    }

    /**
     * @param Stock|null $stock
     * @return array
     */
    public function collectData(Stock|null $stock): array
    {
        if (empty($stock)) {
            return [
                'message' => TelegramError::EMPTY_PRODUCTS,
            ];
        }

        $currencyList   = Currency::currenciesList()->where('default', 1)->first();
        $defCurrency    = data_get($currencyList, 'symbol');
        $currency       = $this->user?->currency ?? $currencyList;
        $symbol         = $this->user?->currency?->symbol ?? $defCurrency;

        $price          = "\n$symbol$stock->rate_total_price";
        $extraText      = '';

        foreach ($stock->stockExtras as $extraKey => $stockExtra) {

            if ($extraKey !== 0) {
                $extraText .= ' ';
            }

            $extraText .= $stockExtra->value;
        }

        $title  = "$price, {$stock->countable?->translation?->title}, $extraText";

        $cart = Cart::firstOrCreate([
            'owner_id'      => $this->user->id,
            'shop_id'       => $stock->countable?->shop_id,
        ], [
            'currency_id'   => data_get($currency, 'id'),
            'rate'          => data_get($currency, 'rate'),
        ]);

        $userCart = UserCart::firstOrCreate([
            'cart_id'   => $cart->id,
            'user_id'   => $this->user->id,
        ], [
            'name'      => $this->user->firstname,
            'uuid'      => Str::uuid()
        ]);

        $cartDetail = CartDetail::where([
            'stock_id'      => $stock->id,
            'user_cart_id'  => $userCart->id,
            'parent_id'     => null,
            'bonus'         => 0,
        ])->first();

        if(!empty($cartDetail)) {
            (new CartService)->bonus($cartDetail);
        }

        $quantity = $cartDetail?->quantity ?? 0;
        $quantity = $quantity ? $this->actualQuantity($stock, $quantity) : 0;
        $quantity = max($quantity, 0);

        $params = [
            [
                [
                    'text'          => "-",
                    'callback_data' => "/minus_to_cart_$stock->id",
                ],
                [
                    'text'          => $quantity,
                    'callback_data' => '/count',
                ],
                [
                    'text'          => "+",
                    'callback_data' => "/plus_to_cart_$stock->id",
                ],
            ]
        ];

        $buttons = [
            'message' => $title,
            'img_url' => $stock->countable->img,
        ];

        foreach ($stock->addons as $key => $addon) {

            if (empty($addon->addon?->translation?->title)) {
                continue;
            }

            $addonStock = $addon?->addon?->stock;

            if (empty($addonStock)) {
                continue;
            }

            $price = "\n$symbol$stock->rate_total_price";

            $addonText = '';

            foreach ($addonStock->stockExtras as $stockExtra) {
                $addonText .= $stockExtra->value;
            }

            $addonCartDetail = CartDetail::where([
                'stock_id'      => $addonStock->id,
                'user_cart_id'  => $userCart->id,
                'parent_id'     => $cartDetail?->id
            ])->first();

            $addonQuantity = $addonCartDetail?->quantity ?
                $this->actualQuantity($stock, $addonCartDetail->quantity) ?? 0 : 0;

            $addonQuantity = max($addonQuantity, 0);

            if(!empty($addonCartDetail)) {
                (new CartService)->bonus($addonCartDetail);
            }

            $params[$key + 1] = [
                [
                    'text'          => "-",
                    'callback_data' => "/addon_minus_to_cart_$stock->id" . "_addon_$addonStock->id",
                ],
                [
                    'text'          => "$price $addonText, qty:$addonQuantity",
                    'callback_data' => '/addon_count',
                ],
                [
                    'text'          => "+",
                    'callback_data' => "/addon_plus_to_cart_$stock->id" . "_addon_$addonStock->id",
                ],
            ];

        }

        $buttons['params'] = $params;

        return $buttons;
    }

    private function actualQuantity(Stock $stock, $quantity): mixed
    {
        $countable = $stock->countable;

        if ($quantity < ($countable?->min_qty ?? 0)) {

            $quantity = $countable->min_qty;

        } else if($quantity > ($countable?->max_qty ?? 0)) {

            $quantity = $countable->max_qty;

        }

        return $quantity > $stock->quantity ? max($stock->quantity, 0) : $quantity;
    }

    public function run()
    {
        $this->handle($this->getBot());
    }

}
