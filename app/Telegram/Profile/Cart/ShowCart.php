<?php

namespace App\Telegram\Profile\Cart;

use App\Helpers\TelegramError;
use App\Models\{Cart, CartDetail, Language, User};
use App\Telegram\Helpers\Main;
use App\Traits\Loggable;
use BotMan\BotMan\BotMan;
use Illuminate\Support\Collection;
use Throwable;

class ShowCart
{
    use Loggable;

    public function handle(BotMan $bot): void
    {
        try {
            $user   = User::where('tg_user_id', $bot->getUser()->getId())->first();
            $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

            $cart = Cart::with([
                'userCart.cartDetails' => fn($q) => $q->whereNull('parent_id'),

                'userCart.cartDetails.stock.countable.translation' => fn($q) => $q
                    ->where('locale', $user->language?->locale)
                    ->orWhere('locale', $locale)
                    ->orWhere('locale', '!=', null),

                'userCart.cartDetails.stock.stockExtras',
//                'userCart.cartDetails.stock.stockExtras.group.translation' => fn($q) => $q
//                    ->where('locale', $user->language?->locale)
//                    ->orWhere('locale', $locale)
//                    ->orWhere('locale', '!=', null),

                'userCart.cartDetails.children.stock.countable.translation' => fn($q) => $q
                    ->where('locale', $user->language?->locale)
                    ->orWhere('locale', $locale)
                    ->orWhere('locale', '!=', null),

                'userCart.cartDetails.children.stock.stockExtras',
//                'userCart.cartDetails.children.stock.stockExtras.group.translation' => fn($q) => $q
//                    ->where('locale', $user->language?->locale)
//                    ->orWhere('locale', $locale)
//                    ->orWhere('locale', '!=', null),
            ])
                ->whereHas('userCart')
                ->where('owner_id', $user->id)
                ->first();

            if (empty($cart)) {
                exit();
            }

            /** @var Cart $cart */
            $names = $this->names($cart);

            $bot->reply($names, [
                'parse_mode'    => 'html',
                'reply_markup'  => json_encode(Main::getMenu()),
            ]);

        } catch (Throwable $e) {
            $this->error($e);
            $bot->reply(__('telegram.' . TelegramError::EMPTY_CART), [
                'parse_mode'    => 'html',
                'reply_markup'  => json_encode(Main::getMenu()),
            ]);
        }
    }

    private function names(Cart $cart): string
    {
        $name = '';

        $this->nameRecursive($cart->userCart->cartDetails, $name);

        if (!empty($name)) {
            $name .= "<b>{$cart->currency?->symbol}$cart->rate_total_price\n";
            $name .= date('Y-m-d H:i:s', strtotime($cart->created_at)) . "</b>";
        }

        return $name;
    }

    /**
     * @param array|Collection $cartDetails
     * @param $name
     * @return void
     */
    private function nameRecursive(array|Collection $cartDetails, &$name): void
    {

        foreach ($cartDetails as $cartDetail) {

            /** @var CartDetail $cartDetail */
            $name .= "<b>{$cartDetail->stock?->countable?->translation?->title}</b>";

            foreach ($cartDetail->stock->stockExtras as $stockExtra) {
                $name .= ", $stockExtra->value";
            }

            if ($cartDetail?->children) {
                $this->nameRecursive($cartDetail?->children, $name);
            }

            $name .= "\n";
        }

    }
}
