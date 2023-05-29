<?php

namespace App\Exports;

use App\Models\Gallery;
use Illuminate\Support\Collection;

class BaseExport
{
    /**
     * @param Collection $galleries
     * @return string
     */
    protected function imageUrl(Collection $galleries): string
    {
        return $galleries->transform(function (Gallery $gallery) {
            return [
                'path' => $gallery->path
            ];
        })->implode('path', ',');
    }
}
