<?php
require(__DIR__ . '/../test.inc.php');

use \sskaje\mqtt\MQTT;
use \sskaje\mqtt\Debug;
use \sskaje\mqtt\MessageHandler;

$mqtt = new MQTT($MQTT_SERVER);

$context = stream_context_create();
$mqtt->setSocketContext($context);

Debug::Enable();

//$mqtt->setAuth('sskaje', '123123');
$mqtt->setKeepalive(10);
$connected = $mqtt->connect();
if (!$connected) {
    die("Not connected\n");
}


$topics['sskaje/#'] = 2;

$mqtt->subscribe($topics);

#$mqtt->unsubscribe(array_keys($topics));


class MySubscribeCallback extends MessageHandler
{

    public function publish($mqtt, sskaje\mqtt\Message\PUBLISH $publish_object)
    {
        printf(
            "\e[32mI got a message\e[0m:(msgid=%d, QoS=%d, dup=%d, topic=%s) \e[32m%s\e[0m\n",
            $publish_object->getMsgID(),
            $publish_object->getQoS(),
            $publish_object->getDup(),
            $publish_object->getTopic(),
            $publish_object->getMessage()
        );
    }
}

$callback = new MySubscribeCallback();

$mqtt->setHandler($callback);

$mqtt->loop();

