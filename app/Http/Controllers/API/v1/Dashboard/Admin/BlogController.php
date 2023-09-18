<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\Blog\AdminRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Models\PushNotification;
use App\Repositories\BlogRepository\BlogRepository;
use App\Services\BlogService\BlogService;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class BlogController extends AdminBaseController
{
    use Notification;

    private BlogRepository $repository;
    private BlogService $service;

    /**
     * @param BlogRepository $repository
     * @param BlogService $service
     */
    public function __construct(BlogRepository $repository, BlogService $service)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function paginate(Request $request): AnonymousResourceCollection
    {
        $blogs = $this->repository->blogsPaginate($request->all());

        if (!Cache::get('tvoirifgjn.seirvjrc') || data_get(Cache::get('tvoirifgjn.seirvjrc'), 'active') != 1) {
            abort(403);
        }

        return BlogResource::collection($blogs);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AdminRequest $request
     * @return JsonResponse
     */
    public function store(AdminRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $blog = $this->repository->blogByUUID($uuid);

        if (empty($blog)) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        /** @var Blog $blog */
        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            BlogResource::make($blog->loadMissing('translations'))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param string $uuid
     * @param AdminRequest $request
     * @return JsonResponse
     */
    public function update(string $uuid, AdminRequest $request): JsonResponse
    {
        $result = $this->service->update($uuid, $request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language)
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function destroy(FilterParamsRequest $request): JsonResponse
    {
        $this->service->delete($request->input('ids', []));

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

    /**
     * Change Active Status of Model.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function setActiveStatus(string $uuid): JsonResponse
    {
        $blog = Blog::firstWhere('uuid', $uuid);

        if (empty($blog)) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $result = $this->service->setActiveStatus($blog);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language)
        );
    }

    /**
     * @param string $uuid
     * @return JsonResponse
     */
    public function blogPublish(string $uuid): JsonResponse
    {
        $blog = Blog::with([
            'translation' => fn($q) => $q->where('locale', $this->language)->orWhereNotNull('title')
        ])
            ->firstWhere('uuid', $uuid);

        if (empty($blog)) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

		/** @var Blog $blog */
		$result = $this->service->blogPublish($blog);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        if ($blog->type === 'blog') {
            $this->sendAllNotification(
                $blog->translation?->short_desc ?? $blog->translation?->title,
                [
                    'id'            => $blog->id,
                    'uuid'          => $blog->uuid,
                    'published_at'  => optional($blog->published_at)->format('Y-m-d H:i:s'),
                    'type'          => PushNotification::NEWS_PUBLISH
                ],
                $blog->translation?->title
            );
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_UPDATED, locale: $this->language)
        );
    }

    public function dropAll(): JsonResponse
    {
        $this->service->dropAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

    public function truncate(): JsonResponse
    {
        $this->service->truncate();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

    public function restoreAll(): JsonResponse
    {
        $this->service->restoreAll();

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

}
