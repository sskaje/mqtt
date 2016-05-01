<?php

date_default_timezone_set('Asia/Shanghai');

require(__DIR__ . '/test.inc.php');

use \sskaje\mqtt\MQTT;
use \sskaje\mqtt\Debug;

$mqtt = new MQTT($MQTT_SERVER, '123');

$mqtt->setVersion(MQTT::VERSION_3_1_1);
#$mqtt->setVersion(MQTT::VERSION_3_1);

Debug::Enable();

$mqtt->setKeepalive(60);
$connected = $mqtt->connect();
if (!$connected) {
    die("Not connected\n");
}

log_test('publish() QoS 0');

$mqtt->publish(
    'qos/0',
    'This is a QoS 0 Message',
    0,
    0,
    0,
    0
);

try {
    $mqtt->publish(
        'qos/0',
        'This is a QoS 0 Message',
        0,
        0,
        0,
        1
    );
} catch (Exception $e) {
    echo 'Exception: ' . $e->getMessage() . "\n";
}

log_test('publish() QoS 1');
try {
    $mqtt->publish(
        'qos/1',
        'This is a QoS 1 Message',
        0,
        1,
        0,
        0
    );
} catch (Exception $e) {
    echo 'Exception: ' . $e->getMessage() . "\n";
}

try {
    $mqtt->publish(
        'qos/1',
        'This is a QoS 1 Message',
        0,
        1,
        0,
        1
    );
} catch (Exception $e) {
    echo 'Exception: ' . $e->getMessage() . "\n";
}

log_test('publish() QoS 2');
try {
    $mqtt->publish(
        'qos/2',
        'This is a QoS 2 Message',
        0,
        2,
        0,
        3
    );
} catch (Exception $e) {
    echo 'Exception: ' . $e->getMessage() . "\n";
}


log_test('publish() \0');
try {
    $mqtt->publish(
        'qos/0',
        "This is a QoS 0 Message\0aaa",
        0,
        1,
        0,
        1
    );
} catch (Exception $e) {
    echo 'Exception: ' . $e->getMessage() . "\n";
}

$mqtt->disconnect();


function log_test($msg)
{
    echo "\n--- [TEST] {$msg}\n";
}