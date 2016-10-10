<?php
require(__DIR__ . '/../test.inc.php');

ini_set('error_log', __DIR__ . '/../../logs/examples_publish.log');

use \sskaje\mqtt\MQTT;
use \sskaje\mqtt\Debug;

$mqtt = new MQTT($MQTT_SERVER);

if (version_compare(PHP_VERSION, '5.6.0') < 1) {
    die("PHP 5.6.0+ only");
}

# Set Socket Context
$contextOptions = array(
    'ssl'   =>  array(
        'crypto_type'   =>  STREAM_CRYPTO_METHOD_TLSv1_2_SERVER,
    ),
);
$context = stream_context_create($contextOptions);
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

$msg = str_repeat('1234567890', 1);

Debug::Log(Debug::INFO, "QoS=0");

$c = 0;
do {
    # mosquitto_sub -t 'sskaje/#'  -q 1 -h test.mosquitto.org
    $mqtt->publish_sync('sskaje/test', $msg, 0, 0);
    echo "======== QoS=0, Count={$c}\n";
    #sleep(1);
} while (++$c < 100);

