<?php
require(__DIR__ . '/../test.inc.php');

use \sskaje\mqtt\MQTT;
use \sskaje\mqtt\Debug;
use \sskaje\mqtt\MessageHandler;

$client_id = isset($argv[1]) ? $argv[1] : time();

$mqtt = new MQTT($MQTT_SERVER, $client_id);

$context = stream_context_create();
$mqtt->setSocketContext($context);

$mqtt->setVersion(MQTT::VERSION_3_1_1);

#Debug::Enable();

# $mqtt->setAuth('sskaje', '123123');
$mqtt->setKeepalive(3600);


# Set Connect Will
$mqtt->setWill(
    'sskaje/bot/logout',
    $client_id,
    1,
    0
);


$connected = $mqtt->connect();
if (!$connected) {
    die("Not connected\n");
}


$topics['sskaje/bot/broadcast'] = 2;
$topics['sskaje/bot/' . $client_id] = 2;


$mqtt->subscribe($topics);


class BotAgentCallback extends MessageHandler
{

    public function publish(MQTT $mqtt, \sskaje\mqtt\Message\PUBLISH $publish_object)
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

    public function suback(MQTT $mqtt, \sskaje\mqtt\Message\SUBACK $suback_object)
    {
        global $client_id;
        # sign up
        $mqtt->publish_async('sskaje/bot/login', $client_id, 1);

    }
}

$callback = new BotAgentCallback();

$mqtt->setHandler($callback);

$mqtt->loop();

