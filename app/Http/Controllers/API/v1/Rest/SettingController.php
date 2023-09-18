<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReferralResource;
use App\Models\Order;
use App\Models\Referral;
use App\Models\Settings;
use App\Models\Translation;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class SettingController extends Controller
{
    use ApiResponse;

    public function settingsInfo(): JsonResponse
    {
        $settings = Settings::adminSettings();

        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language), $settings);
    }

    public function referral(): JsonResponse
    {
        $active = Settings::adminSettings()->where('key', 'referral_active')->first();

        if (!data_get($active, 'value')) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        $referral = Referral::with([
            'translation' => fn($q) => $q->where('locale', $this->language),
            'translations',
            'galleries',
        ])->where([
            ['expired_at', '>=', now()],
        ])->first();

        if (empty($referral)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            ReferralResource::make($referral)
        );
    }

    public function translationsPaginate(): JsonResponse
    {
        $translations = Cache::remember('language-'. $this->language, 86400, function () {
            return Translation::where('locale', $this->language)
                ->where('status', 1)
                ->pluck('value', 'key');
        });

        return $this->successResponse('errors.' . ResponseError::NO_ERROR, $translations->all());
    }

    /**
     * @return JsonResponse
     */
    public function systemInformation(): JsonResponse
    {
        // get MySql version from DataBase
        $error  = 'No error';

        try {
			$mysql  	= DB::selectOne(DB::raw('SHOW VARIABLES LIKE "%innodb_version%"'));
			$node       = exec('node -v');
            $npm        = exec('npm -v');
            $composer   = exec('composer -V');
        } catch (Throwable $e) {
            $this->error($e);
            $node = '';
            $npm = '';
            $composer = '';
            $error = $e->getMessage() ?? 'can`t run php command exec';
			$mysql = (object)['Value' => 'Mysql Error', 'Variable_name' => 'Mysql Error'];
        }

        return $this->successResponse('success', [
            'PHP Version'       => phpversion(),
            'Laravel Version'   => app()->version(),
            'OS Version'        => php_uname(),
            'MySql Version'     => $mysql->Value,
            'NodeJs Version'    => $node,
            'NPM Version'       => $npm,
            'Project Version'   => env('PROJECT_V'),
            'Composer Version'  => $composer,
            'error'             => $error,
            'engine'            => $mysql->Variable_name
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function stat(): JsonResponse
    {
        $users = DB::table('users')
            ->select(['id'])
            ->count('id');

        $orders = DB::table('orders')
            ->select(['id', 'status'])
            ->where('status', Order::STATUS_DELIVERED)
            ->count('id');

        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language), [
            'users'  => $users,
            'orders' => $orders
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function projectVersion(): JsonResponse
    {
        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language), [
            'version' => env('PROJECT_V')
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function timeZone(): JsonResponse
    {
        return $this->successResponse(__('errors.' . ResponseError::SUCCESS, locale: $this->language), [
            'timeZone'  => config('app.timezone'),
            'time'      => date('Y-m-d H:i:s')
        ]);
    }

}
