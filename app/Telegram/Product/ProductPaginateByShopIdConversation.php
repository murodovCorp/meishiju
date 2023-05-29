<?php

namespace App\Telegram\Product;

use App\Helpers\TelegramError;
use App\Models\Currency;
use App\Models\Product;
use App\Models\User;
use App\Telegram\Helpers\Main;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use Illuminate\Contracts\Pagination\Paginator;
use BotMan\BotMan\Messages\Attachments\Image;

class ProductPaginateByShopIdConversation extends Conversation
{
    public function __construct(
        private $shopId = null,
        private $page = 1,
        private User|null $user = null,
    ) {}

    public function handle(BotMan $bot): bool
    {

        $this->user = User::where('tg_user_id', $bot->getUser()->getId())->first();

        request()->offsetSet('currency_id', $this->user?->currency_id);

        $locale = Main::getLocale();

        $products = Product::with([
            'translation' => fn($query) => $query->where('locale', $locale),
            'stock' => fn($q) => $q->where('addon', 0)->orderBy('price')
        ])
            ->whereHas('stock', fn($q) => $q->where('addon', 0))
            ->where([
                'status'  => Product::PUBLISHED,
                'active'  => 1,
                'addon'   => 0,
                'shop_id' => $this->shopId,
            ])
            ->simplePaginate(5, page: $this->page);

        $result = $this->collectData($products);

        foreach ($result as $item) {

            if (!data_get($item, 'message')) {
                $bot->reply('Paginate', [
                    'parse_mode'   => 'html',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            $item
                        ]
                    ])
                ]);
                continue;
            }

            $attachment = new Image(data_get($item, 'img_url'));
            $message    = OutgoingMessage::create(data_get($item, 'message'))->withAttachment($attachment);

            $bot->reply($message, [
                'parse_mode'   => 'html',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        data_get($item, 'params')
                    ]
                ])
            ]);

        }

        return true;
    }

    /**
     * @param Paginator $products
     * @return array
     */
    public function collectData(Paginator $products): array
    {
        if ($products->isEmpty()) {
            return [
                'message' => TelegramError::EMPTY_PRODUCTS,
            ];
        }

        $currencyList = Currency::currenciesList()->where('default', 1)->first();
        $defCurrency  = data_get($currencyList, 'symbol');
        $currency     = $this->user?->currency?->symbol ?? $defCurrency;

        $locale = Main::getLocale();

        $buttons = [];

        foreach ($products as $product) {

            /** @var Product|null $product */
            $title = $product?->translation?->title;

            if (empty($title)) {
                continue;
            }

            $title .= "\n$currency{$product?->stock?->rate_total_price}";

            $buttons[] = [
                'message' => $title,
                'img_url' => $product->img,
                'params'  => [
                    [
                        'text'            => $title,
                        'callback_data'   => "/stocks_$product->id",
                    ]
                ]
            ];

        }

        if ($products->hasMorePages() && $products->currentPage() > 1) {
            $buttons[] = [
                [
                    'text'            => __('telegram.' . TelegramError::PREV, locale: $locale),
                    'callback_data'   => '/product_offset_' . ($products->currentPage() > 1 ? $products->currentPage() - 1 : 1),
                ],
                [
                    'text'            => __('telegram.' . TelegramError::NEXT, locale: $locale),
                    'callback_data'   => '/product_offset_' . ($products->currentPage() + 1),
                ]
            ];
        } else if ($products->hasMorePages()) {
            $buttons[] = [
                [
                    'text'            => __('telegram.' . TelegramError::NEXT, locale: $locale),
                    'callback_data'   => '/product_offset_' . ($products->currentPage() + 1),
                ]
            ];
        } else {
            $buttons[] = [
                [
                    'text'            => __('telegram.' . TelegramError::PREV, locale: $locale),
                    'callback_data'   => '/product_offset_' . ($products->currentPage() > 1 ? $products->currentPage() - 1 : 1),
                ],
            ];
        }

        return $buttons;
    }

    public function run()
    {
        $this->handle($this->getBot());
    }

}
