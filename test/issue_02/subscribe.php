<?php

require(__DIR__ . '/../../spMQTT.class.php');

$clientid = substr(md5('QOS111_02'), 0, 20);

$mqtt = new spMQTT('tcp://192.168.76.142:1883/', $clientid);

spMQTTDebug::Enable();
$mqtt->setConnectClean(false);


$mqtt->setKeepalive(3600);
$connected = $mqtt->connect();
if (!$connected) {
    die("Not connected\n");
}

# !!!
//$mqtt->ping();


$topics['sskaje/test/#'] = 1;

$mqtt->subscribe($topics);


$mqtt->loop('default_subscribe_callback');


/**
 * @param spMQTT $mqtt
 * @param string $topic
 * @param string $message
 */
function default_subscribe_callback($mqtt, $topic, $message) {
    printf("Message received: Topic=%s, Message=%s\n", $topic, $message);
}
