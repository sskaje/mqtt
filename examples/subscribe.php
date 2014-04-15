<?php

require(__DIR__ . '/../spMQTT.class.php');

$mqtt = new spMQTT('tcp://test.mosquitto.org:1883/');

spMQTTDebug::Enable();

//$mqtt->setAuth('sskaje', '123123');
$mqtt->setKeepalive(3600);
$connected = $mqtt->connect();
if (!$connected) {
    die("Not connected\n");
}

$mqtt->ping();


$topics['sskaje/#'] = 1;

$mqtt->subscribe($topics);

#$mqtt->unsubscribe(array_keys($topics));

$mqtt->loop('default_subscribe_callback');


/**
 * @param spMQTT $mqtt
 * @param string $topic
 * @param string $message
 */
function default_subscribe_callback($mqtt, $topic, $message) {
    printf("Message received: Topic=%s, Message=%s\n", $topic, $message);
}