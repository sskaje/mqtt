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

#
$mqtt->setKeepalive(30);

//$mqtt->setAuth('sskaje', '123123');
$connected = $mqtt->connect();
if (!$connected) {
    die("Not connected\n");
}

$msg = str_repeat('1234567890', 1);

# Set Retry Timeout for Publish and its following commands
$mqtt->setRetryTimeout(5);

Debug::Log(Debug::INFO, "QoS=1");

$c = 1;
while (true) {
    # Special thanks to @LiuYongShuai for this test case.

    # It is the responsibility of the Client to ensure that the interval between Control Packets
    # being sent does not exceed the Keep Alive value. In the absence of sending any other Control
    # Packets, the Client MUST send a PINGREQ Packet [MQTT-3.1.2-23].

    # If the Keep Alive value is non-zero and the Server does not receive a Control Packet from the Client
    # within one and a half times the Keep Alive time period, it MUST disconnect the Network Connection to
    # the Client as if the network had failed [MQTT-3.1.2-24].
    $mqtt->keepalive();

    # mosquitto_sub -t 'sskaje/#'  -q 1 -h test.mosquitto.org
    $mqtt->publish_sync('sskaje/test', $msg, 1, 0);
    echo "======== QoS=1, Count={$c}\n";
    #sleep(1);
    ++$c;
}