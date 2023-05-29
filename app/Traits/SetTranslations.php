<?php

namespace App\Traits;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Throwable;

trait SetTranslations
{
    /**
     * @param Model $model Все модели у которых есть таблица $model_translations
     * @param array $data
     * @param bool $hasDesc
     * @param bool $hasAddress
     * @return void
     */
    public function setTranslations(Model $model, array $data, bool $hasDesc = true, bool $hasAddress = false): void
    {
        try {
            /** @var Category $model */
            if (is_array(data_get($data, 'title'))) {
                $model->translations()->forceDelete();
            }

            foreach (is_array(data_get($data, 'title')) ? data_get($data, 'title') : [] as $index => $value) {

                $desc    = $hasDesc     ? ['description' => data_get($data, "description.$index")] : [];
                $address = $hasAddress  ? ['address' => data_get($data, "address.$index")] : [];

                $model->translations()->create([
                    'title'         => $value,
                    'locale'        => $index,
                ] + $desc + $address);
            }

        } catch (Throwable $e) {
            $this->error($e);
        }
    }
}
