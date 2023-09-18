<?php


namespace App\Helpers;

use App\Traits\Loggable;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class Helper
{
    use Loggable;

    /**
     * generate number function
     * 生成唯一编号方法
     * @param string $prefix
     * @param integer $length
     * @return string
     */
    public static function generateNumber(string $prefix, int $length): string
    {
        $uid = uniqid($prefix, true);
        $uid = str_replace('.', '', $uid);
        $num = substr($prefix . $uid, 0, $length);

        for ($i = 0; $i < strlen($num); $i++) {

            if ($i >= strlen($prefix)) {

                $num[$i] = strtolower($num[$i]);

                if (ord($num[$i]) >= 97 && ord($num[$i]) <= 122) {
                    $num[$i] = (ord($num[$i]) - 97) % 10;
                }

            }

        }

        return $num;
    }

    public static function requestGet($url, $options = [])
    {

        $client = new Client($options);

        try {
            $response = $client->get($url);

            if (!in_array($response->getStatusCode(), [200, 201])) {

                Log::error("http error: code：" . $response->getStatusCode(), [
                    $response->getBody()
                ]);

                return false;
            }

            $response = $response->getBody()->getContents();

            return json_decode($response, true);
        } catch (Exception|GuzzleException $e) {

            (new self)->error($e);

            return false;
        }

    }

}
