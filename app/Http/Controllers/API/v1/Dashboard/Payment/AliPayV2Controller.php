<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StripeRequest;
use App\Http\Resources\OrderResource;
use App\Services\PaymentService\AliPayServiceV2;
use App\Traits\ApiResponse;
use App\Traits\Notification;
use App\Traits\OnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AliPayV2Controller extends Controller
{
    use OnResponse, ApiResponse, Notification;

    public function __construct(private AliPayServiceV2 $service)
    {
        parent::__construct();
    }

    /**
     * process transaction.
     *
     * @param StripeRequest $request
     * @return JsonResponse
     */
    public function prepay(StripeRequest $request): JsonResponse
    {
        try {
            $result = $this->service->preparePay($request->all());

            if (!data_get($result, 'status')) {
                return $this->onErrorResponse($result);
            }

            /** @var ResponseInterface $data */
            $data = $result['data'];

            $responseBody = $data->getBody()->getContents();

//            parse_str($responseBody, $json);
//
//            $bizContent = json_decode(urldecode(data_get($json, 'biz_content')), true);
//
//            $json['biz_content'] = $bizContent;

            return $this->successResponse('success', $responseBody);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse([
                'message' => $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getCode(),
            ]);

        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function notify(Request $request): JsonResponse
    {
        $result = $this->service->notify($request->all());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::NO_ERROR, locale: $this->language),
            OrderResource::make(data_get($result, 'data'))
        );
    }

    public function paid(Request $request) {
//        $order = Order::find($request->input('order_id'));
//
//        if (empty($order)) {
//            Log::error('empty order:', $request->all());
//            return;
//        }
//
//        $order->transaction?->update([
//            'status' => Transaction::STATUS_PAID,
//        ]);
//
//        $tokens = (new BaseService)->tokens($order);
//
//        $this->sendNotification(
//            data_get($tokens, 'tokens'),
//            "New order was created",
//            $order->id,
//            $order->setAttribute('type', PushNotification::NEW_ORDER)?->only(['id', 'status', 'type']),
//            data_get($tokens, 'ids', [])
//        );
    }

}
