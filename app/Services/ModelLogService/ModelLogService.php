<?php

namespace App\Services\ModelLogService;

use App\Models\ModelLog;
use App\Models\User;
use App\Services\CoreService;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class ModelLogService extends CoreService
{
    protected function getModelClass(): string
    {
        return ModelLog::class;
    }

    public function logging(Model $model, array $data, $type = 'logged'): void
    {
        try {
            if (count($data) > 0) {
                //for telegram register
                $createdBy = auth('sanctum')->id() ??
                    (get_class($model) === User::class) ? data_get($model, 'id') : auth('sanctum')->id();

                if ($createdBy) {
                    ModelLog::create([
                        'model_type' => get_class($model),
                        'model_id'   => data_get($model, 'id'),
                        'data'       => $type === 'created' ? $data : $this->prepareData($model),
                        'type'       => strtolower(data_get(explode('\\', get_class($model)), '2', 'model')) . '_' . $type,
                        'created_at' => now(),
                        'created_by' => $createdBy,
                    ]);
                }
            }
        } catch (Throwable $e) {
            $this->error($e);
        }
    }

    /**
     * Get only changed column values
     * @param $model
     * @return array
     */
    public function prepareData($model): array
    {
        $data = [];

        $originals = $model->getRawOriginal();

        unset($originals['id']);
        unset($originals['created_at']);
        unset($originals['updated_at']);

        foreach ($originals as $column => $original) {

            try {
                $attribute = $model->$column;

                // value by casts
                switch (true) {
                    case is_object($attribute) && get_class($attribute) === 'Illuminate\Support\Carbon':
                        $attribute = date('Y-m-d H:i:s', strtotime($attribute));
                        $original = date('Y-m-d H:i:s', strtotime($original));
                        break;
                    case is_int($original):
                        $attribute = (int)$attribute;
                        break;
                    case is_object($attribute):
                    case is_array($original):
                        $attribute = collect($attribute)->toArray();
                        break;
                    case is_bool($original):
                        $attribute = (bool)$attribute;
                        break;
                }

                if ($original !== $attribute) {
                    $data[$column] = $original;
                }
            } catch (Throwable $e) {
                $this->error($e);
            }

        }

        return $data;

    }

}
