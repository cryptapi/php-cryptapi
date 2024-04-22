<?php
/**
 * Testing BlockBee Library...
 */

require __DIR__ . '/vendor/autoload.php';

$coin = "bep20_usdt";
$callback_url = "https://example.com";
$parameters = [
    'payment_id' => 12345,
];

$cryptapi_params = [
    'pending' => 1,
];

try {
    $ca = new CryptAPI\CryptAPI($coin, '0xA6B78B56ee062185E405a1DDDD18cE8fcBC4395d', $callback_url, $parameters, $cryptapi_params);

    # var_dump($ca->get_address()) . PHP_EOL;

    # var_dump($ca->check_logs()) . PHP_EOL;

    # var_dump($ca->get_qrcode()) . PHP_EOL;

    # var_dump($ca->get_qrcode(2, 500)) . PHP_EOL;

    # var_dump(\CryptAPI\CryptAPI::get_info('btc', true)) . PHP_EOL;

    # var_dump(\CryptAPI\CryptAPI::get_info($coin, false)) . PHP_EOL;

    # var_dump(\CryptAPI\CryptAPI::get_supported_coins()) . PHP_EOL;

    # var_dump(\CryptAPI\CryptAPI::get_estimate($coin, 1, '')) . PHP_EOL;

    # var_dump(\CryptAPI\CryptAPI::get_convert($coin, 3, 'usd')) . PHP_EOL;
} catch (Exception $e) {
    var_dump($e);
}
