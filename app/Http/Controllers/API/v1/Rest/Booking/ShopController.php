<?php

namespace App\Http\Controllers\API\v1\Rest\Booking;

use App\Helpers\ResponseError;
use App\Helpers\Utility;
use App\Http\Controllers\API\v1\Rest\RestBaseController;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Shop\CheckWorkingDayRequest;
use App\Http\Resources\Booking\BookingShopResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\ShopGalleryResource;
use App\Http\Resources\ShopPaymentResource;
use App\Jobs\UserActivityJob;
use App\Models\Booking\BookingShop;
use App\Models\Order;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\ShopGallery;
use App\Repositories\Booking\ShopRepository\ShopRepository;
use App\Repositories\ReviewRepository\ReviewRepository;
use App\Repositories\ShopPaymentRepository\ShopPaymentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Str;
use Throwable;

class ShopController extends RestBaseController
{
    /**
     * @param ShopRepository $shopRepository
     */
    public function __construct(
        private ShopRepository $shopRepository
    )
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $visibility = (int)Settings::adminSettings()->where('key', 'by_subscription')->first()?->value;

        $merge = [
            'status'    => 'approved',
            'currency'  => $this->currency,
        ];

        if ($visibility) {
            $merge += ['visibility' => true];
        }

        $shops = $this->shopRepository->shopsPaginate(
            $request->merge($merge)->all()
        );

        return BookingShopResource::collection($shops);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function selectPaginate(Request $request): AnonymousResourceCollection
    {
        $shops = $this->shopRepository->selectPaginate(
            $request->merge([
                'status'        => 'approved',
                'currency'      => $this->currency
            ])->all()
        );

        return BookingShopResource::collection($shops);
    }

    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $shop = $this->shopRepository->shopDetails($uuid);

        if (!$shop) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        /** @var Shop $shop */
        UserActivityJob::dispatchAfterResponse(
            $shop->id,
            get_class($shop),
            'click',
            1,
            auth('sanctum')->user()
        );

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            BookingShopResource::make($shop)
        );
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function takes(): JsonResponse
    {
        $shop = $this->shopRepository->takes();

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            $shop
        );
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function productsAvgPrices(): JsonResponse
    {
        $shop = $this->shopRepository->productsAvgPrices();

        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language), $shop);
    }

    /**
     * Search shop Model from database.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function shopsSearch(Request $request): AnonymousResourceCollection
    {
        $shops = $this->shopRepository->shopsSearch($request->merge([
            'status'        => 'approved',
            'currency'      => $this->currency
        ])->all());

        return BookingShopResource::collection($shops);
    }

    /**
     * Search shop Model from database via IDs.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function shopsByIDs(Request $request): AnonymousResourceCollection
    {
        $shops = $this->shopRepository->shopsByIDs($request->merge(['status' => 'approved'])->all());

        return BookingShopResource::collection($shops);
    }

    /**
     * Search shop Model from database via IDs.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recommended(Request $request): JsonResponse
    {
        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            $this->shopRepository->recommended($request->all())
        );
    }

    /**
     * Search shop Model from database via IDs.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function products(int $id, Request $request): JsonResponse
    {
        $shop = BookingShop::find($id);

        if (empty($shop)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language),
            $this->shopRepository->products($request->merge(['shop_id' => $shop->id])->all())
        );
    }

    /**
     * Search shop Model from database via IDs.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function categories(int $id, Request $request): JsonResponse|AnonymousResourceCollection
    {
        $shop = BookingShop::find($id);

        if (empty($shop)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $categories = $this->shopRepository->categories($request->merge(['shop_id' => $shop->id])->all());

        return CategoryResource::collection($categories);
    }

    /**
     * Search shop Model from database via IDs.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function productsPaginate(int $id, Request $request): JsonResponse|AnonymousResourceCollection
    {
        $shop = BookingShop::find($id);

        if (empty($shop)) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $products = $this->shopRepository->productsPaginate($request->merge(['shop_id' => $shop->id])->all());

        return ProductResource::collection($products);
    }

    /**
     * Search shop Model from database via IDs.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function productsRecommendedPaginate(int $id, Request $request): JsonResponse|AnonymousResourceCollection
    {
        $shop = BookingShop::find($id);

        if (empty($shop)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $products = $this->shopRepository->productsRecommendedPaginate(
            $request->merge(['shop_id' => $shop->id])->all()
        );

        return ProductResource::collection($products);
    }

    /**
     * Search shop Model from database via IDs.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function shopPayments(int $id, Request $request): JsonResponse|AnonymousResourceCollection
    {
        $shop = BookingShop::find($id);

        if (empty($shop)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $payments = (new ShopPaymentRepository)->list($request->merge(['shop_id' => $shop->id])->all());

        return ShopPaymentResource::collection($payments);
    }

    /**
     * @param int $id
     * @return ShopGalleryResource|JsonResponse
     */
    public function galleries(int $id): ShopGalleryResource|JsonResponse
    {
        $shopGallery = ShopGallery::with(['galleries'])
            ->where('shop_id', $id)
            ->where('active', 1)
            ->first();

        if (empty($shopGallery)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return ShopGalleryResource::make($shopGallery);
    }

    /**
     * @param int $id
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function reviews(int $id, FilterParamsRequest $request): AnonymousResourceCollection
    {
        $filter = $request->merge([
            'type'      => 'order',
            'assign'    => 'shop',
            'assign_id' => $id,
        ])->all();

        $result = (new ReviewRepository)->paginate($filter, [
            'user' => fn($q) => $q
                ->select([
                    'id',
                    'uuid',
                    'firstname',
                    'lastname',
                    'password',
                    'img',
                    'active',
                ])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews'),
            'reviewable:id,address',
        ]);

        return ReviewResource::collection($result);
    }

    /**
     * @param int $id
     * @return float[]
     */
    public function reviewsGroupByRating(int $id): array
    {
        $reviews = DB::table('reviews')
            ->where('reviewable_type', Order::class)
            ->where('assignable_type', BookingShop::class)
            ->where('assignable_id', $id)
            ->whereNull('deleted_at')
            ->select([
                DB::raw('count(id) as count, avg(rating) as rating, rating')
            ])
            ->groupBy(['rating'])
            ->get();

        return [
            'group' => Utility::groupRating($reviews),
            'count' => $reviews->sum('count'),
            'avg'   => $reviews->avg('rating'),
        ];
    }

    /**
     * Search shop Model from database via IDs.
     *
     * @param int $id
     * @param CheckWorkingDayRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function shopWorkingCheck(int $id, CheckWorkingDayRequest $request): JsonResponse|AnonymousResourceCollection
    {
        $date = date('Y-m-d', strtotime($request->input('date')));
        $day  = Str::lower(date('l', strtotime($request->input('date'))));
        $time = date('H:i', strtotime($request->input('date')));

        $exists = false;

        $shop = BookingShop::whereHas('bookingWorkingDays', fn($q) => $q
            ->where('disabled', 0)
            ->where('day', $day)
//            ->where('from', '>=', str_replace(':', '-', $time))
//            ->where('to', '<=', str_replace(':', '-', $time))
        )
            ->whereDoesntHave('bookingClosedDates', fn($q) => $q->where('date', $date))
            ->find($id);

        if (!empty($shop)) {

            foreach ($shop->bookingWorkingDays as $workingDay) {

                try {
                    if (
                        $time >= date('H:i', strtotime(str_replace('-', ':', $workingDay->from))) &&
                        $time <= date('H:i', strtotime(str_replace('-', ':', $workingDay->to)))
                    ) {
                        $exists = true;
                    }
                } catch (Throwable) {

                }

            }

        }

        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language), $exists);
    }
}
