<?php
namespace App\Telegram\Helpers;

use App\Helpers\TelegramError;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class Main
{
    public static function getLanguage() {

        $language = null;

        try {
            $tgUserId = collect(request()->input('*.from.id'))->reject(null)->first();

            /** @var User $user */
            $user = User::with(['language'])->where('tg_user_id', $tgUserId)->first();

            $code = $user?->language?->locale ?? collect(request()->input('*.from.language_code'))->reject(null)->first();

            $language = Language::languagesList()->where('locale', '=', $code)->first();

            if (empty($language)) {
                $language = Language::languagesList()->where('default', 1)->first();
            }

        } catch (Throwable $e) {
            Log::error('lang | ' . $e->getMessage(), [
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
        }

        return $language;
    }

    public static function getLocale()
    {
        return data_get(self::getLanguage(), 'locale', 'en');
    }

    public static function getMenu(): array
    {
        return [
            'inline_keyboard' => self::getInlineMenuButtons(),
        ];
    }

    public static function getInlineMenuButtons(): array
    {
        $locale = self::getLocale();

        return [
            [
                [
                    'text' => __('telegram.' . TelegramError::SHOPS, locale: $locale),
                    'callback_data' => '/shop_offset_0',
                ],
                [
                    'text' => __('telegram.' . TelegramError::RESTAURANTS, locale: $locale),
                    'callback_data' => '/restaurant_offset_0',
                ],
            ],
            [
                [
                    'text' => __('telegram.' . TelegramError::PROFILE, locale: $locale),
                    'callback_data' => '/profile',
                ],
                [
                    'text' => 'Web Site',
                    'url'  => 'https://foodyman.org',
                ],
            ],
        ];
    }

    public static function getCancelButton(): array
    {
        $locale = self::getLocale();

        return [
            'text'      => __('telegram.' . TelegramError::CANCEL, locale: $locale),
            'callback'  => TelegramError::CANCEL
        ];
    }
}
