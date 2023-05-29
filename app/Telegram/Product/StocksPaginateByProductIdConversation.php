<?php

namespace App\Telegram\Product;

use App\Helpers\TelegramError;
use App\Models\Currency;
use App\Models\Stock;
use App\Models\User;
use App\Telegram\Helpers\Main;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Attachments\Image;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class StocksPaginateByProductIdConversation extends Conversation
{
    public function __construct(
        private $productId = null,
        private User|null $user = null,
    ) {}

    public function handle(BotMan $bot): bool
    {
        $this->user = User::where('tg_user_id', $bot->getUser()->getId())->first();

        request()->offsetSet('currency_id', $this->user?->currency_id);

        $locale = Main::getLocale();

        $stocks = Stock::with([
            'countable.translation' => fn($q) => $q
                ->where('locale', $locale)->orWhere('locale', $locale),
            'stockExtras',
//            'stockExtras.group.translation' => fn($q) => $q
//                ->where('locale', $locale)->orWhere('locale', $locale),
        ])
//            ->whereHas('stockExtras.group.translation', fn($q) =>
//                $q->where('locale', $locale)->orWhere('locale', $locale)
//            )
            ->where([
                'addon'         => 0,
                'countable_id'  => $this->productId,
            ])
            ->get();

        $result = $this->collectData($stocks);

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
     * @param Builder[]|Collection $stocks
     * @return array
     */
    public function collectData(Collection|array $stocks): array
    {
        if (count($stocks) === 0) {
            return [
                'message' => TelegramError::EMPTY_PRODUCTS,
            ];
        }

        $currencyList = Currency::currenciesList()->where('default', 1)->first();
        $defCurrency  = data_get($currencyList, 'symbol');
        $currency     = $this->user?->currency?->symbol ?? $defCurrency;

        $firstStock = $stocks->first();

        $title  = $firstStock?->countable?->translation?->title;
        $params = [];

        $buttons = [
            'message' => $title,
            'img_url' => $firstStock?->countable->img,
        ];

        foreach ($stocks as $key => $stock) {

            /** @var Stock $stock */

            $price = "\n$currency$stock->rate_total_price";

            $extraText = '';

            foreach ($stock->stockExtras as $extraKey => $stockExtra) {

                if ($extraKey !== 0) {
                    $extraText .= ' ';
                }

                $extraText .= $stockExtra->value;
            }

            $params[$key][] = [
                'text'            => "$price, $extraText",
                'callback_data'   => "/stock_$stock->id",
            ];

        }

        $buttons['params'] = $params;

        return $buttons;
    }

    public function run()
    {
        $this->handle($this->getBot());
    }

}
