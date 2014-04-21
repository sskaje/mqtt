<?php
require(__DIR__ . '/../../spMQTT.class.php');

$clientid = substr(md5('QOS111_01'), 0, 20);

$mqtt = new spMQTT('tcp://192.168.76.142:1883/', $clientid);

spMQTTDebug::Enable();

//$mqtt->setAuth('sskaje', '123123');
$connected = $mqtt->connect();
if (!$connected) {
    die("Not connected\n");
}

$mqtt->ping();

$msg = str_repeat('1234567890', 1);

# mosquitto_sub -t 'sskaje/#'  -q 1 -h test.mosquitto.org
$mqtt->publish('sskaje/test/qos0', $msg, 0, 0, 0, 1);

# mosquitto_sub -t 'sskaje/#'  -q 1 -h test.mosquitto.org
$mqtt->publish('sskaje/test/qos1', $msg, 0, 1, 0, 2);

# mosquitto_sub -t 'sskaje/#'  -q 1 -h test.mosquitto.org
$mqtt->publish('sskaje/test/qos2', $msg, 0, 2, 0, 3);


