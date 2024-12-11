<?php

namespace CryptAPI;

use CryptAPI\Exceptions\ApiException;
use Exception;

class CryptAPI
{
    private static $base_url = 'https://api.cryptapi.io';
    private static $pro_url  = 'https://api.blockbee.io';
    private $valid_coins     = [];
    private $own_address     = null;
    private $payment_address = null;
    private $callback_url    = null;
    private $coin            = null;
    private $ca_params       = [];
    private $parameters      = [];
    private $api_key         = null;

    /**
     * @throws Exception
     */
    public function __construct($coin, $own_address, $callback_url, $parameters = [], $ca_params = [], $api_key = null)
    {
        if (empty($own_address)) {
            throw new Exception('Please provide your cryptocurrency wallet address.');
        }

        if (empty($coin)) {
            throw new Exception('Please provide a valid coin/ticker.');
        }

        if (empty($callback_url)) {
            throw new Exception('Please provide a valid callback url.');
        }

        $this->valid_coins = CryptAPI::get_supported_coins();

        $coin = str_replace('/', '_', (string) $coin);

        if (!in_array($coin, $this->valid_coins)) {
            $vc = print_r($this->valid_coins, true);
            throw new Exception("Unsupported Coin: {$coin}, Valid options are: {$vc}");
        }

        $this->own_address  = $own_address;
        $this->callback_url = $callback_url;
        $this->coin         = $coin;
        $this->ca_params    = $ca_params;
        $this->parameters   = $parameters;
        $this->api_key      = $api_key;
    }

    /**
     * @throws ApiException
     */
    public static function get_supported_coins()
    {
        $info = CryptAPI::get_info(null, true);

        if (empty($info)) {
            return null;
        }

        unset($info['fee_tiers']);

        $coins = [];

        foreach ($info as $chain => $data) {
            $is_base_coin = in_array('ticker', array_keys($data));
            if ($is_base_coin) {
                $coins[] = $chain;
                continue;
            }

            $base_ticker = "{$chain}_";
            foreach ($data as $token => $subdata) {
                $coins[] = $base_ticker . $token;
            }
        }

        return $coins;
    }

    /**
     * @throws ApiException
     */
    public function get_address()
    {
        $api_key = $this->api_key;

        $callback_url = $this->callback_url;
        if (!empty($this->parameters)) {
            $req_parameters = http_build_query($this->parameters);
            $callback_url   = "{$this->callback_url}?{$req_parameters}";
        }

        if (empty($api_key)) {
            $ca_params = array_merge([
                'callback' => $callback_url,
                'address'  => $this->own_address,
            ], $this->ca_params);
        } else {
            $ca_params = array_merge([
                'apikey'   => $api_key,
                'callback' => $callback_url,
                'address'  => $this->own_address,
            ], $this->ca_params);
        }

        $response = CryptAPI::_request($this->coin, 'create', $ca_params);

        $this->payment_address = $response->address_in;

        return $response->address_in;
    }

    /**
     * @throws ApiException
     */
    public function check_logs()
    {
        $callback_url = $this->callback_url;
        if (!empty($this->parameters)) {
            $req_parameters = http_build_query($this->parameters);
            $callback_url   = "{$this->callback_url}?{$req_parameters}";
        }

        $params = [
            'callback' => $callback_url,
        ];

        return CryptAPI::_request($this->coin, 'logs', $params);
    }

    /**
     * @throws ApiException
     */
    public function get_qrcode($value = false, $size = false)
    {
        $address = $this->payment_address;

        if (empty($address)) {
            $address = $this->get_address();
        }

        $params = [
            'address' => $address,
        ];

        if ($value) {
            $params['value'] = $value;
        }
        if ($size) {
            $params['size'] = $size;
        }

        return CryptAPI::_request($this->coin, 'qrcode', $params);
    }

    /**
     * @throws ApiException
     */
    public static function get_info($coin = null, $assoc = false)
    {
        $params = [];

        if (empty($coin)) {
            $params['prices'] = '0';
        }

        return CryptAPI::_request($coin, 'info', $params, $assoc);
    }

    /**
     * @throws ApiException
     */
    public static function get_estimate($coin, $addresses = 1, $priority = 'default')
    {
        return CryptAPI::_request($coin, 'estimate', [
            'addresses' => $addresses,
            'priority'  => $priority
        ]);
    }

    /**
     * @throws ApiException
     */
    public static function get_convert($coin, $value, $from)
    {
        return CryptAPI::_request($coin, 'convert', [
            'value' => $value,
            'from'  => $from
        ]);
    }

    public static function process_callback($_get)
    {
        $params = [
            'address_in'           => $_get['address_in'],
            'address_out'          => $_get['address_out'],
            'txid_in'              => $_get['txid_in'],
            'txid_out'             => isset($_get['txid_out']) ? $_get['txid_out'] : null,
            'confirmations'        => $_get['confirmations'],
            'value'                => $_get['value'],
            'value_coin'           => $_get['value_coin'],
            'value_forwarded'      => isset($_get['value_forwarded']) ? $_get['value_forwarded'] : null,
            'value_forwarded_coin' => isset($_get['value_forwarded_coin']) ? $_get['value_forwarded_coin'] : null,
            'coin'                 => $_get['coin'],
            'pending'              => isset($_get['pending']) ? $_get['pending'] : false,
        ];

        foreach ($_get as $k => $v) {
            if (isset($params[$k])) {
                continue;
            }
            $params[$k] = $_get[$k];
        }

        return $params;
    }

    /**
     * @throws ApiException
     */
    private static function _request($coin, $endpoint, $params = [], $assoc = false)
    {
        $base_url = Cryptapi::$base_url;
        $coin     = str_replace('_', '/', (string) $coin);

        if (!empty($params['apikey']) && $endpoint !== 'info') {
            $base_url = CryptAPI::$pro_url;
        }

        if (!empty($params)) {
            $data = http_build_query($params);
        }

        if (!empty($coin)) {
            $url = "{$base_url}/{$coin}/{$endpoint}/";
        } else {
            $url = "{$base_url}/{$endpoint}/";
        }

        if (!empty($data)) {
            $url .= "?{$data}";
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response_object = json_decode($response, $assoc);

        if ((is_object($response_object) && isset($response_object->status) && $response_object->status === 'error') || (is_array($response_object) && isset($response_object['status']) && $response_object['status'] === 'error')) {
            $statusCode = $http_code;
            $apiError = $response_object->error ?? null;

            throw ApiException::withStatus($statusCode, $apiError);
        }

        return $response_object;
    }
}
