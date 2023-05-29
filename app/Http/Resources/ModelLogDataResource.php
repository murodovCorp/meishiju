<?php

namespace App\Http\Resources;

class ModelLogDataResource
{
    /**
     * Transform the resource into an array.
     *
     * @param $data
     * @return array
     */
    public static function toArray($data): array
    {
        $resource = [];

        foreach ($data as $column => $attribute) {

            $encode = @json_decode($attribute);

            $resource[$column] = !empty($encode) ? $encode : $attribute;
        }

        return $resource;
    }
}
