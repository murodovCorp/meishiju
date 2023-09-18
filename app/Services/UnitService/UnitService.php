<?php

namespace App\Services\UnitService;

use App\Helpers\ResponseError;
use App\Models\Language;
use App\Models\Unit;
use App\Services\CoreService;
use DB;
use Throwable;

class UnitService extends CoreService
{
    protected function getModelClass(): string
    {
        return Unit::class;
    }

    public function create(array $data): array
    {
        try {
            $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

            /** @var Unit $unit */
            $unit = DB::transaction(function () use ($data) {

                /** @var Unit $unit */
                $unit = $this->model()->create([
                    'active'    => data_get($data, 'active', 0),
                    'position'  => data_get($data, 'position', 'after'),
                ]);

                $unit->translations()->forceDelete();

                $title = data_get($data, 'title');

                foreach (is_array($title) ? $title : [] as $index => $value) {

                    $unit->translation()->create([
                        'locale' => $index,
                        'title'  => $value,
                    ]);

                }

                return $unit;
            });

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data' => $unit->fresh([
                    'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                    'translations',
                ])
            ];
        } catch (Throwable $e) {
            $this->error($e);
        }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_501,
            'message' => __('errors.' . ResponseError::ERROR_501, locale: $this->language)
        ];
    }

    public function update(Unit $unit, array $data): array
    {
        try {
            $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

            $unit->update([
                'active'    => data_get($data, 'active', 0),
                'position'  => data_get($data, 'position', 'after'),
            ]);

            $unit->translations()->forceDelete();

            $title = data_get($data, 'title');

            foreach (is_array($title) ? $title : [] as $index => $value) {

                $unit->translation()->create([
                    'locale' => $index,
                    'title' => $value,
                ]);

            }

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data' => $unit->fresh([
                    'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                    'translations',
                ])
            ];
        } catch (Throwable $e) {
            $this->error($e);
        }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_502,
            'message' => __('errors.' . ResponseError::ERROR_502, locale: $this->language)
        ];
    }

    public function setActive(int $id): array
    {
        try {
            $locale = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

            $unit = Unit::find($id);

            $unit->update(['active' => !$unit->active]);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data' => $unit->fresh([
                    'translation' => fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale),
                    'translations',
                ])
            ];
        } catch (Throwable $e) {
            $this->error($e);
        }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_502,
            'message' => __('errors.' . ResponseError::ERROR_502, locale: $this->language)
        ];
    }
}
