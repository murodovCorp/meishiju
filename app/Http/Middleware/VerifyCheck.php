<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseError;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyCheck
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param null $redirectToRoute
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $redirectToRoute = null)
    {
//        if (!auth('sanctum')->user() ||
//            (auth('sanctum')->user() instanceof MustVerifyEmail &&
//                ! auth('sanctum')->user()->hasVerifiedEmail())) {
//            return $this->errorResponse(ResponseError::ERROR_105, trans('errors.' . ResponseError::ERROR_105, [], request()->lang ?? 'ru'), Response::HTTP_UNAUTHORIZED);
//        }
        return $next($request);
    }

    // for view
    //!$request->expectsJson()
    //? $this->errorResponse(ResponseError::ERROR_105, trans('errors.' . ResponseError::ERROR_105, [], request()->lang ?? 'ru'), Response::HTTP_UNAUTHORIZED)
    //: Redirect::guest(URL::route($redirectToRoute ?: 'verification.notice'));
}
