<?php

namespace App\Services;

use App\Helpers\ResponseError;
use App\Models\Currency;
use App\Models\Language;
use App\Traits\ApiResponse;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Cache;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

abstract class CoreService
{
    use ApiResponse, Loggable;

    private mixed $model;
    protected string $language;
    protected int $currency;

    public function __construct()
    {
        $this->model    = app($this->getModelClass());
        $this->language = $this->setLanguage();
        $this->currency = $this->setCurrency();
    }

    abstract protected function getModelClass();

    protected function model()
    {
        return clone $this->model;
    }

    /**
     * Set default Currency
     */
    protected function setCurrency(): int
    {
        $default = Currency::where('default', 1)->first(['id', 'default'])?->id ?? null;

        $currency = request('currency_id', $default);

        if (is_bool($currency) || is_object($currency) || is_array($currency)) {
            $currency = $default;
        }

        return (int)$currency;
    }

    protected function setLanguage(): string
    {
        $default = Language::where('default', 1)->first(['locale', 'default'])?->locale ?? 'en';

        $lang = request('lang', $default);

        if (!is_string($lang)) {
             $lang = $default;
        }

        return (string)$lang;
    }

    /**
     * @param array|null $exclude
     * @return void
     */
    public function dropAll(?array $exclude = []): void
    {
        /** @var Model|Language $models */

        $models = $this->model();

        $models = $models->when(data_get($exclude, 'column') && data_get($exclude, 'value'),
            function (Builder $query) use($exclude) {
                $query->where(data_get($exclude, 'column'), '!=', data_get($exclude, 'value'));
            }
        )->get();

        foreach ($models as $model) {

            try {

                $model->delete();

            } catch (Throwable $e) {

                $this->error($e);

            }

        }

        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}
    }

    /**
     * @return void
     */
    public function restoreAll(): void
    {
        /** @var Model|Language $models */
        $models = $this->model();

        foreach ($models->withTrashed()->whereNotNull('deleted_at')->get() as $model) {

            try {

                $model->update([
                    'deleted_at' => null
                ]);

            } catch (Throwable $e) {

                $this->error($e);

            }

        }

        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}
    }

    /**
     * @param string $name
     * @return void
     */
    public function truncate(string $name = ''): void
    {
        DB::statement("SET foreign_key_checks = 0");
        DB::table($name ?: $this->model()->getTable())->truncate();
        DB::statement("SET foreign_key_checks = 1");

        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}
    }

    /**
     * @param array $ids
     * @return array|int[]|void
     */
    public function destroy(array $ids)
    {
        foreach ($this->model()->whereIn('id', $ids)->get() as $model) {
            try {
                $model->delete();
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}
    }

	/**
	 * @param array $ids
	 * @return array|int|int[]|void
	 */
    public function delete(array $ids)
    {
        $this->destroy($ids);
        $s = Cache::get('tvoirifgjn.seirvjrc');

        Cache::flush();

        try {
            Cache::set('tvoirifgjn.seirvjrc', $s);
        } catch (Throwable|InvalidArgumentException) {}
    }

    /**
     * @param array $ids
     * @param string $column
     * @param array<string>|null $when
     * @return array
     */
    public function remove(array $ids, string $column = 'id', ?array $when = ['column' => null, 'value' => null]): array
    {
        $errorIds = [];

        $models = $this->model()
            ->whereIn($column, $ids)
            ->when(data_get($when, 'column'), fn($q, $column) => $q->where($column, data_get($when, 'value')))
            ->get();

        foreach ($models as $model) {
            try {
                $model->delete();
            } catch (Throwable $e) {
                $this->error($e);
                $errorIds[] = $model->id;
            }
        }

        if (count($errorIds) === 0) {
            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        }

        return [
            'status'  => false,
            'code'    => ResponseError::ERROR_505,
            'message' => __(
                'errors.' . ResponseError::CANT_DELETE_IDS,
                [
                    'ids' => implode(', ', $errorIds)
                ],
                $this->language
            )
        ];
    }
}
