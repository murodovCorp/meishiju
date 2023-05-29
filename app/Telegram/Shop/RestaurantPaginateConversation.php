<?php

namespace App\Telegram\Shop;

use App\Helpers\TelegramError;
use App\Models\Shop;
use App\Telegram\Helpers\Main;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Attachments\Image;
use Illuminate\Contracts\Pagination\Paginator;

class RestaurantPaginateConversation extends Conversation
{
    public function __construct(private $page = 1) {}

    public function handle(BotMan $bot): bool
    {
        $locale = Main::getLocale();

        $shops = Shop::with([
            'translation' => fn($query) => $query->where('locale', $locale),
            'products'
        ])
            ->whereHas('translation', fn($query) => $query->where('locale', $locale))
            ->where([
                'open'   => 1,
                'status' => 'approved',
                'type'   => 2,
            ])
            ->simplePaginate(5, page: $this->page);

        $result = $this->collectData($shops);

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
     * @param Paginator $shops
     * @return array
     */
    public function collectData(Paginator $shops): array
    {
        if ($shops->isEmpty()) {
            return [
                'message' => TelegramError::EMPTY_SHOPS,
            ];
        }

        $locale = Main::getLocale();

        $buttons = [];

        foreach ($shops as $shop) {

            /** @var Shop|null $shop */
            $title = $shop?->translation?->title;

            if (empty($title)) {
                continue;
            }

            $buttons[] = [
                'message' => $title,
                'img_url' => $shop->logo_img,
                'params'  => [
                    [
                        'text'          => $title,
                        'callback_data' => "/product_offset_$shop->id" . '_0',
                    ]
                ]
            ];

        }

        if ($shops->hasMorePages() && $shops->currentPage() > 1) {
            $buttons[] = [
                [
                    'text'            => __('telegram.' . TelegramError::PREV, locale: $locale),
                    'callback_data'   => '/restaurant_offset_' . ($shops->currentPage() > 1 ? $shops->currentPage() - 1 : 1),
                ],
                [
                    'text'            => __('telegram.' . TelegramError::NEXT, locale: $locale),
                    'callback_data'   => '/restaurant_offset_' . ($shops->currentPage() + 1),
                ]
            ];
        } else if ($shops->hasMorePages()) {
            $buttons[] = [
                [
                    'text'            => __('telegram.' . TelegramError::NEXT, locale: $locale),
                    'callback_data'   => '/restaurant_offset_' . ($shops->currentPage() + 1),
                ]
            ];
        } else {
            $buttons[] = [
                [
                    'text'            => __('telegram.' . TelegramError::PREV, locale: $locale),
                    'callback_data'   => '/restaurant_offset_' . ($shops->currentPage() > 1 ? $shops->currentPage() - 1 : 1),
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
