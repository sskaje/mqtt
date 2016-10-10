<?php
require(__DIR__ . '/test.inc.php');

use \sskaje\mqtt\MQTT;
use \sskaje\mqtt\Debug;

$mqtt = new MQTT($MQTT_SERVER);

Debug::Enable();

//$mqtt->setAuth('sskaje', '123123');
$mqtt->setKeepalive(3600);
$connected = $mqtt->connect();
if (!$connected) {
    die("Not connected\n");
}

$topics['sskaje/test/1'] = 1;
$topics['sskaje/broadcast/#'] = 1;

$c = 0;
do {
    $msg = $c;

    # mosquitto_sub -t 'sskaje/#'  -q 1 -h test.mosquitto.org
    $mqtt->publish_sync('sskaje/test/1', $msg, 1, 0);

    $mqtt->publish_sync('sskaje/broadcast/' . $c, $msg, 1, 0);

    usleep(500000);
} while (++$c < 1000);