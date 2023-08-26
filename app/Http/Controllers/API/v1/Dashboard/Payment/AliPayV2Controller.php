<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StripeRequest;
use App\Services\PaymentService\AliPayServiceV2;
use App\Traits\ApiResponse;
use App\Traits\OnResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Yansongda\Pay\Exception\ContainerException;
use Yansongda\Pay\Exception\InvalidParamsException;
use Yansongda\Pay\Pay;

class AliPayV2Controller extends Controller
{
    use OnResponse, ApiResponse;

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

            parse_str($responseBody, $json);

            $bizContent = json_decode(urldecode(data_get($json, 'biz_content')), true);

            $json['biz_content'] = $bizContent;

            return $this->successResponse('success', $json);
        } catch (Throwable $e) {

            $this->error($e);

            return $this->onErrorResponse([
                'message' => $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getCode(),
            ]);
        }
    }

    public function notify(Request $request): JsonResponse
    {
        $config = config('pay.alipay.default');
        $alipay = Pay::alipay($config);

        try {
            $data = $alipay->callback();
            Log::info('bosya tr：notify',[$data]);

        } catch (ContainerException|InvalidParamsException $e) {

            $message = $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getCode();

            Log::error("bosya: $message", $request->all());

            return $this->onErrorResponse(['message' => $message]);
        }

        return $this->successResponse('success', $data);
    }

}
