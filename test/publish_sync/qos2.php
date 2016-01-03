<?php
require(__DIR__ . '/../test.inc.php');

ini_set('error_log', __DIR__ . '/../../logs/examples_publish.log');

use \sskaje\mqtt\MQTT;
use \sskaje\mqtt\Debug;

$mqtt = new MQTT($MQTT_SERVER);

# Set Socket Context
$context = stream_context_create();
$mqtt->setSocketContext($context);

# Set Connect Will
$mqtt->setWill(
    'sskaje/will',
    'Ciao~',
    0,
    0
);

Debug::Enable();
Debug::SetLogPriority(Debug::NOTICE);

//$mqtt->setAuth('sskaje', '123123');
$connected = $mqtt->connect();
if (!$connected) {
    die("Not connected\n");
}

# Set Retry Timeout for Publish and its following commands
$mqtt->setRetryTimeout(5);

$msg = str_repeat('1234567890', 1);

Debug::Log(Debug::INFO, "QoS=2");
$c = 0;
do {
    # mosquitto_sub -t 'sskaje/#'  -q 1 -h test.mosquitto.org
    $mqtt->publish_sync('sskaje/test', $msg, 2, 0, $msgid);
    echo "======== QoS=2, Count={$c}, msgid={$msgid} \n";
} while (++$c < 100);
