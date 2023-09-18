<?php

namespace App\Imports;

use App\Models\Product;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class BaseImport
{
    use Loggable;

    /**
     * @param Product|Model $model
     * @param string|null $imgUrls
     * @return bool
     */
    protected function downloadImages(mixed $model, ?string $imgUrls): bool
    {
        try {

            if (empty($imgUrls)) {
                return false;
            }

            $firstKey = 0;
            $urls     = explode(',', $imgUrls);

            foreach ($urls as $key => $url) {

                DB::table('before_galleries')->updateOrInsert([
                    'url'        => $url,
                    'model_id'   => $model->id,
                    'model_type' => get_class($model),
                ], [
                    'parent'     => $firstKey == $key
                ]);

                $firstKey = $key;
            }

            return true;
        } catch (Throwable $e) {
            $this->error($e);
        }

        return false;
    }
}
