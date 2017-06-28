<?php
require(__DIR__ . '/../test.inc.php');

use sskaje\mqtt\Message\PUBLISH;
use sskaje\mqtt\Message\SUBACK;
use \sskaje\mqtt\MQTT;
use \sskaje\mqtt\Debug;
use \sskaje\mqtt\MessageHandler;

$mqtt = new MQTT($MQTT_SERVER);

$context = stream_context_create();
$mqtt->setSocketContext($context);

Debug::Disable();

$mqtt->setVersion(MQTT::VERSION_3_1_1);

# $mqtt->setAuth('sskaje', '123123');
$mqtt->setKeepalive(3600);

# Set Connect Will
$mqtt->setWill(
    'sskaje/bot/broadcast',
    "I'll be back",
    1,
    0
);


$connected = $mqtt->connect();
if (!$connected) {
    die("Not connected\n");
}

$topics['sskaje/bot/#'] = 2;

$mqtt->subscribe($topics);


class BotControlCallback extends MessageHandler
{
    protected $online_agents = array();

    public function publish(MQTT $mqtt, PUBLISH $publish_object)
    {
        $topic = $publish_object->getTopic();
        $message = $publish_object->getMessage();

        if ($topic === 'sskaje/bot/login') {
            $this->online_agents[$message] = 1;

            $mqtt->publish_async('sskaje/bot/' . $message, 'Welcome, ' . $message, 1);
            $mqtt->publish_async('sskaje/bot/broadcast', $message . ' is online.', 1);

            echo "{$message} is online\n";

        } else if ($topic === 'sskaje/bot/logout') {
            unset($this->online_agents[$message]);

            $mqtt->publish_async('sskaje/bot/broadcast', $message . ' is offline.', 1);

            echo "{$message} is offline\n";

        } else if ($topic === 'sskaje/bot/status') {

            $mqtt->publish_async('sskaje/bot/broadcast', 'There are ' . count($this->online_agents) . ' agent(s) online', 1);

            echo "someone is querying status\n";
        }
    }

    public function suback(MQTT $mqtt, SUBACK $suback_object)
    {
        $mqtt->publish_async('sskaje/bot/broadcast', "I'm alive", 1);
    }
}

$callback = new BotControlCallback();

$mqtt->setHandler($callback);

$mqtt->loop();

