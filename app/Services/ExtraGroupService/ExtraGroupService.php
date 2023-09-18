<?php

namespace App\Services\ExtraGroupService;

use App\Helpers\ResponseError;
use App\Models\ExtraGroup;
use App\Services\CoreService;
use App\Traits\SetTranslations;
use Throwable;

class ExtraGroupService extends CoreService
{
    use SetTranslations;

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return ExtraGroup::class;
    }


    public function create(array $data): array
    {
        try {
            /** @var ExtraGroup $extraGroup */
            $extraGroup = $this->model()->create($data);

            $this->setTranslations($extraGroup, $data, false);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $extraGroup,
            ];
        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_501,
                'message' => $e->getMessage()
            ];
        }
    }

    public function update(ExtraGroup $extraGroup, array $data): array
    {
        try {
            $extraGroup->update($data);
            $this->setTranslations($extraGroup, $data, false);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => $extraGroup,
            ];
        } catch (Throwable $e) {

            $this->error($e);

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_502,
                'message' => $e->getMessage()
            ];
        }
    }

    public function delete(?array $ids, ?int $shopId = null): int
    {
        $hasValues = 0;

		$extraGroups = $this->model()
			->with([
				'extraValues',
			])
			->whereIn('id', is_array($ids) ? $ids : [])
			->when($shopId, fn($q) => $q->where('shop_id', $shopId))
			->get();

        foreach ($extraGroups as $extraGroup) {

            /** @var ExtraGroup $extraGroup */

            if (count($extraGroup->extraValues) > 0) {
                $hasValues++;
                continue;
            }

            $extraGroup->delete();
        }

        return $hasValues;
    }

    public function setActive(int $id, ?int $shopId = null): array
    {
        $extraGroup = ExtraGroup::find($id);

        if (empty($extraGroup) || (!empty($shopId) && $extraGroup->shop_id !== $shopId)) {
            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ];
        }

        /** @var ExtraGroup $extraGroup */
        $extraGroup->update(['active' => !$extraGroup->active]);

        return [
            'status' => true,
            'code'   => ResponseError::NO_ERROR,
            'data'   => $extraGroup,
        ];
    }
}
