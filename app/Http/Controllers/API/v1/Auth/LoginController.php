<?php

namespace App\Http\Controllers\API\v1\Auth;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgetPasswordRequest;
use App\Http\Requests\Auth\PhoneVerifyRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ProvideLoginRequest;
use App\Http\Requests\Auth\ReSendVerifyRequest;
use App\Http\Resources\UserResource;
use App\Models\Notification;
use App\Models\User;
use App\Services\AuthService\AuthByMobilePhone;
use App\Services\EmailSettingService\EmailSendService;
use App\Services\UserServices\UserWalletService;
use App\Traits\ApiResponse;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LoginController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request): JsonResponse
    {
        if ($request->input('phone')) {
            return $this->loginByPhone($request);
        }

        if (!auth()->attempt($request->only(['email', 'password']))) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_102,
                'message' => __('errors.' . ResponseError::ERROR_102, locale: $this->language)
            ]);
        }

        $token = auth()->user()->createToken('api_token')->plainTextToken;

        return $this->successResponse('User successfully login', [
            'access_token'  => $token,
            'token_type'    => 'Bearer',
            'user'          => UserResource::make(auth('sanctum')->user()->loadMissing(['shop', 'model'])),
        ]);
    }

    protected function loginByPhone($request): JsonResponse
    {
        if (!auth()->attempt($request->only('phone', 'password'))) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_102,
                'message' => __('errors.' . ResponseError::ERROR_102, locale: $this->language)
            ]);
        }

        $token = auth()->user()->createToken('api_token')->plainTextToken;

        return $this->successResponse('User successfully login', [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => UserResource::make(auth('sanctum')->user()->loadMissing(['shop', 'model'])),
        ]);

    }

    /**
     * Obtain the user information from Provider.
     *
     * @param $provider
     * @param ProvideLoginRequest $request
     * @return JsonResponse
     */
    public function handleProviderCallback($provider, ProvideLoginRequest $request): JsonResponse
    {
        $validated = $this->validateProvider($request->input('id'), $provider);

        if (!empty($validated)) {
            return $validated;
        }

        try {
            $result = DB::transaction(function () use ($request, $provider) {

                @[$firstname, $lastname] = explode(' ', $request->input('name'));

                $user = User::withTrashed()->updateOrCreate(['email' => $request->input('email')], [
                    'email'             => $request->input('email'),
                    'email_verified_at' => now(),
                    'referral'          => $request->input('referral'),
                    'active'            => true,
                    'firstname'         => !empty($firstname) ? $firstname : $request->input('email'),
                    'lastname'          => $lastname,
                    'deleted_at'        => null,
                ]);

                $user->socialProviders()->updateOrCreate([
                    'provider'      => $provider,
                    'provider_id'   => $request->input('id'),
                ]);

                if (!$user->hasAnyRole(Role::query()->pluck('name')->toArray())) {
                    $user->syncRoles('user');
                }

                $ids = Notification::pluck('id')->toArray();

                if ($ids) {
                    $user->notifications()->sync($ids);
                } else {
                    $user->notifications()->forceDelete();
                }

                $user->emailSubscription()->updateOrCreate([
                    'user_id' => $user->id
                ], [
                    'active' => true
                ]);

				if (empty($user->wallet?->uuid)) {
					$user = (new UserWalletService)->create($user);
				}

                return [
                    'token' => $user->createToken('api_token')->plainTextToken,
                    'user'  => UserResource::make($user),
                ];
            });

            return $this->successResponse('User successfully login', [
                'access_token'  => data_get($result, 'token'),
                'token_type'    => 'Bearer',
                'user'          => data_get($result, 'user'),
            ]);
        } catch (Throwable $e) {
            $this->error($e);
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::USER_IS_BANNED, locale: $this->language)
            ]);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            /** @var User $user */
            /** @var PersonalAccessToken $current */
            $user           = auth('sanctum')->user();
            $firebaseToken  = collect($user->firebase_token)
                ->reject(fn($item) => (string)$item == (string)request('firebase_token') || empty($item))
                ->toArray();

            $user->update([
                'firebase_token' => $firebaseToken
            ]);

            $current = $user->currentAccessToken();

            $current->delete();
        } catch (Throwable $e) {
            $this->error($e);
        }

        return $this->successResponse('User successfully logout');
    }

    /**
     * @param $idToken
     * @param $provider
     * @return JsonResponse|void
     */
    protected function validateProvider($idToken, $provider)
    {
//        $serverKey = Settings::adminSettings()->where('key', 'api_key')->first()?->value;
//        $clientId  = Settings::adminSettings()->where('key', 'client_id')->first()?->value;
//
//        $response  = Http::get("https://oauth2.googleapis.com/tokeninfo?id_token=$idToken");

//        dd($response->json(), $clientId, $serverKey);

//        $response = Http::withHeaders([
//            'Content-Type' => 'application/x-www-form-urlencoded',
//        ])
//            ->post('http://your-laravel-app.com/oauth/token');

        if (!in_array($provider, ['facebook', 'github', 'google', 'apple'])) { //$response->ok()
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_107,
                'http'    => Response::HTTP_UNAUTHORIZED,
                'message' =>  __('errors.' . ResponseError::INCORRECT_LOGIN_PROVIDER, locale: $this->language)
            ]);
        }

    }

    public function forgetPassword(ForgetPasswordRequest $request): JsonResponse
    {
        return (new AuthByMobilePhone)->authentication($request->validated());
    }

    public function forgetPasswordEmail(ReSendVerifyRequest $request): JsonResponse
    {
        $user = User::withTrashed()->where('email', $request->input('email'))->first();

        if(!$user) {
            return $this->onErrorResponse([
                'code'      => ResponseError::ERROR_404,
                'message'   => __('errors.' . ResponseError::ERROR_404, locale: $this->language),
            ]);
        }

        $token = mb_substr(time(), -6, 6);

        Cache::put($token, $token, 900);

        (new EmailSendService)->sendEmailPasswordReset($user, $token);

        return $this->successResponse('Verify code send');
    }

    public function forgetPasswordVerifyEmail(int $hash, Request $request): JsonResponse
    {
        $token = Cache::get($hash);

        if (!$token) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_215,
                'message' => __('errors.' . ResponseError::ERROR_215, locale: $this->language)
            ]);
        }

        $user = User::withTrashed()->where('email', $request->input('email'))->first();

        if (!$user) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::USER_NOT_FOUND, locale: $this->language)
            ]);
        }

        if (!$user->hasAnyRole(Role::query()->pluck('name')->toArray())) {
            $user->syncRoles('user');
        }

        $token = $user->createToken('api_token')->plainTextToken;

        $user->update([
            'active'     => true,
            'deleted_at' => null
        ]);

        session()->forget([$request->input('email') . '-' . $hash]);

        return $this->successResponse('User successfully login', [
            'token' => $token,
            'user'  => UserResource::make($user),
        ]);
    }

    /**
     * @param PhoneVerifyRequest $request
     * @return JsonResponse
     */
    public function forgetPasswordVerify(PhoneVerifyRequest $request): JsonResponse
    {
        return (new AuthByMobilePhone)->forgetPasswordVerify($request->validated());
    }


}
