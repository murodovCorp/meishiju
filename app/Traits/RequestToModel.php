<?php

namespace App\Traits;

use App\Models\Gallery;
use App\Models\RequestModel;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property Collection|Gallery[] $galleries
 * @property Gallery|null $gallery
 * @property int $galleries_count
*/
trait RequestToModel
{
    public function models(): MorphMany
    {
        return $this->morphMany(RequestModel::class, 'model');
    }

    public function model(): MorphOne
    {
        return $this->morphOne(RequestModel::class, 'model');
    }
}

