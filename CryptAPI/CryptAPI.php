<?php

namespace CryptAPI;
use Exception;

class CryptAPI {
    private static $base_url = "https://api.cryptapi.io";
    private $valid_erc20_tokens = ['becaz', 'bnb', 'busd', 'cro', 'link', 'mkr', 'nexo', 'pax', 'tusd', 'usdc', 'usdt', ];
    private $valid_trc20_tokens = ['usdt', 'btc', 'eth', ];
    private $valid_coins = ['btc', 'bch', 'eth', 'ltc', 'xmr', 'iota', 'trx', ];
    private $own_address = null;
    private $callback_url = null;
    private $coin = null;
    private $ca_params = [];
    private $parameters = [];

    public static $COIN_MULTIPLIERS = [
        'btc' => 10**8,
        'bch' => 10**8,
        'ltc' => 10**8,
        'eth' => 10**18,
        'iota' => 10**6,
        'xmr' => 10**12,
        'trx' => 10**6,
    ];

    public function __construct($coin, $own_address, $callback_url, $parameters=[], $ca_params=[]) {

        foreach ($this->valid_erc20_tokens as $token) {
            $this->valid_coins[] = 'erc20_' . $token;
        }

        foreach ($this->valid_trc20_tokens as $token) {
            $this->valid_coins[] = 'trc20_' . $token;
        }

        if (!in_array($coin, $this->valid_coins)) {
            $vc = print_r($this->valid_coins, true);
            throw new Exception("Unsupported Coin: {$coin}, Valid options are: {$vc}");
        }

        $this->own_address = $own_address;
        $this->callback_url = $callback_url;
        $this->coin = $coin;
        $this->ca_params = $ca_params;
        $this->parameters = $parameters;

    }

    public function get_address() {

        if (empty($this->own_address) || empty($this->coin) || empty($this->callback_url)) return null;

        $callback_url = $this->callback_url;
        if (!empty($this->parameters)) {
            $req_parameters = http_build_query($this->parameters);
            $callback_url = "{$this->callback_url}?{$req_parameters}";
        }

        $ca_params = array_merge([
            'callback' => $callback_url,
            'address' => $this->own_address,
        ], $this->ca_params);

        $response = CryptAPI::_request($this->coin, 'create', $ca_params);

        if ($response->status == 'success') {
            return $response->address_in;
        }

        return null;
    }

    public function check_logs() {

        if (empty($this->coin) || empty($this->callback_url)) return null;

        $params = [
            'callback' => $this->callback_url,
        ];

        $response = CryptAPI::_request($this->coin, 'logs', $params);

        if ($response->status == 'success') {
            return $response;
        }

        return null;
    }

    public static function get_info($coin) {
        $response = CryptAPI::_request($coin, 'info');

        if ($response->status == 'success') {
            return $response;
        }

        return null;
    }
        
    public static function get_qrcode($coin, $address, $value = false, $size = false) {
        $params = [
            'address' => $address,
        ];

        if ($value) $params['value'] = $value;
        if ($size) $params['size'] = $size;

        $response = CryptAPI::_request($coin, 'qrcode', $params);

        if ($response->status == 'success') {
            return $response;
        }

        return null;
    }

    public static function get_estimate($coin, $addresses = 1, $priority = 'default') {
        $response = CryptAPI::_request($coin, 'estimate', [
            'addresses' => $addresses,
            'priority' => $priority
        ]);

        if ($response->status == 'success') {
            return $response;
        }

        return null;
    }
    
    public static function get_convert($coin, $value, $from) {
        $response = CryptAPI::_request($coin, 'convert', [
            'value' => $value,
            'from' => $from
        ]);

        if ($response->status == 'success') {
            return $response;
        }

        return null;
    }

    public static function process_callback($_get) {
        $params = [
            'address_in' => $_get['address_in'],
            'address_out' => $_get['address_out'],
            'txid_in' => $_get['txid_in'],
            'txid_out' => isset($_get['txid_out']) ? $_get['txid_out'] : null,
            'confirmations' => $_get['confirmations'],
            'value' => $_get['value'],
            'value_coin' => $_get['value_coin'],
            'value_forwarded' => isset($_get['value_forwarded']) ? $_get['value_forwarded'] : null,
            'value_forwarded_coin' => isset($_get['value_forwarded_coin']) ? $_get['value_forwarded_coin'] : null,
            'coin' => $_get['coin'],
            'pending' => isset($_get['pending']) ? $_get['pending'] : false,
        ];

        foreach ($_get as $k => $v) {
            if (isset($params[$k])) continue;
            $params[$k] = $_get[$k];
        }

        return $params;
    }

    private static function _request($coin, $endpoint, $params=[]) {

        $base_url = Cryptapi::$base_url;
        $coin = str_replace('_', '/', $coin);

        if (!empty($params)) $data = http_build_query($params);

        $url = "{$base_url}/{$coin}/{$endpoint}/";

        if (!empty($data)) $url .= "?{$data}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }
}
