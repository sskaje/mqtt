<?php
require(__DIR__ . '/../spMQTT.class.php');

$mqtt = new spMQTT('tcp://test.mosquitto.org:1883/');

spMQTTDebug::Enable();

//$mqtt->setAuth('sskaje', '123123');
$connected = $mqtt->connect();
if (!$connected) {
    die("Not connected\n");
}

$mqtt->ping();

$msg = str_repeat('1234567890', 1);

# mosquitto_sub -t 'sskaje/#'  -q 1 -h test.mosquitto.org
$mqtt->publish('sskaje/test', $msg, 0, 1, 0, 1);

sleep(10);

$msg = str_repeat('1234567890', 15);

# mosquitto_sub -t 'sskaje/#'  -q 1 -h test.mosquitto.org
$mqtt->publish('sskaje/test', $msg, 0, 1, 0, 1);

sleep(10);

$msg = str_repeat('1234567890', 1640);

# mosquitto_sub -t 'sskaje/#'  -q 1 -h test.mosquitto.org
$mqtt->publish('sskaje/test', $msg, 0, 1, 0, 1);

sleep(10);

$msg = str_repeat('1234567890', 209716);

# mosquitto_sub -t 'sskaje/#'  -q 1 -h test.mosquitto.org
$mqtt->publish('sskaje/test', $msg, 0, 1, 0, 1);


