<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller\Report\Sales;

use App\Http\Controllers\API\v1\Dashboard\Seller\SellerBaseController;
use App\Http\Requests\Report\Sales\HistoryMainRequest;
use App\Http\Requests\Report\Sales\HistoryRequest;
use App\Repositories\ReportRepository\HistoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class HistoryController extends SellerBaseController
{
    public function __construct(
        private HistoryRepository $repository,
    )
    {
        parent::__construct();
    }

    /**
     * @param HistoryRequest $request
     * @return LengthAwarePaginator
     */
    public function history(HistoryRequest $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request->merge(['shop_id' => $this->shop->id])->all());
    }

    /**
     * @return array
     */
    public function cards(): array
    {
        return $this->repository->cards(['shop_id' => $this->shop->id]);
    }

    /**
     * @param HistoryMainRequest $request
     * @return array
     */
    public function mainCards(HistoryMainRequest $request): array
    {
        return $this->repository->mainCards($request->merge(['shop_id' => $this->shop->id])->all());
    }

    /**
     * @param HistoryMainRequest $request
     * @return Collection
     */
    public function chart(HistoryMainRequest $request): Collection
    {
        return $this->repository->chart($request->merge(['shop_id' => $this->shop->id])->all());
    }

    /**
     * @param HistoryMainRequest $request
     * @return array[]
     */
    public function statistic(HistoryMainRequest $request): array
    {
        return $this->repository->statistic($request->merge(['shop_id' => $this->shop->id])->all());
    }
}
