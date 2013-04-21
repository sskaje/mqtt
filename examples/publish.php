<?php
require(__DIR__ . '/../spMQTT.class.php');

$mqtt = new spMQTT('tcp://test.mosquitto.org:1883/');

//$mqtt->setAuth('sskaje', '123123');
$connected = $mqtt->connect();

$mqtt->ping();

# mosquitto_sub -t 'sskaje/#'  -q 1 -h test.mosquitto.org
$mqtt->publish('sskaje/hello', 'messasssssge', 0, 2, 0, 100);

