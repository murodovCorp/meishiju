<?php

namespace App\Http\Resources;

use App\Models\BannerTranslation;
use App\Models\BlogTranslation;
use App\Models\FaqTranslation;
use App\Models\ReferralTranslation;
use App\Models\ShopTranslation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TranslationResource extends JsonResource
{
    /**
     * Ключи не трогать т.к общий ресурс для всех таблиц table_translations
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /**
         * @var ShopTranslation|FaqTranslation|ReferralTranslation|BannerTranslation|BlogTranslation|JsonResource $this
         */
        return [
            'id'            => $this->id,
            'locale'        => $this->locale,
            'title'         => $this->when($this->title, (string) $this->title),
            'short_desc'    => $this->when($this->short_desc, (string) $this->short_desc),
            'description'   => $this->when($this->description, $this->description),
            'button_text'   => $this->when($this->button_text, (string) $this->button_text),
            'address'       => $this->when($this->address, (string) $this->address),
            'question'      => $this->when($this->question, (string) $this->question),
            'answer'        => $this->when($this->answer, (string) $this->answer),
            'faq'           => $this->when($this->faq, (string) $this->faq),
            'deleted_at'    => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),
        ];
    }
}
