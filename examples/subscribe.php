<?php

require(__DIR__ . '/../spMQTT.class.php');

$mqtt = new spMQTT('tcp://test.mosquitto.org:1883/', '333');

spMQTTDebug::Enable();

//$mqtt->setAuth('sskaje', '123123');
$mqtt->setKeepalive(3600);
$connected = $mqtt->connect();

$mqtt->ping();


$topics['sskaje/#'] = 2;

$mqtt->subscribe($topics);

$mqtt->unsubscribe(array_keys($topics));

$mqtt->loop('default_subscribe_callback');


function default_subscribe_callback($topic, $message) {
    printf("Message received: Topic=%s, Message=%s\n", $topic, $message);
}