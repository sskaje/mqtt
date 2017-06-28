<?php
use sskaje\mqtt\Message\PUBACK;
use sskaje\mqtt\Message\PUBCOMP;
use sskaje\mqtt\MessageHandler;
use sskaje\mqtt\MQTT;

class MyPublishHandler extends MessageHandler
{
    public $waitQueue = array();

    public function puback(MQTT $mqtt, PUBACK $puback_object)
    {
        $msgid = $puback_object->getMsgID();
        echo "======== puback: Remove from queue msgid={$msgid}\n";
        unset($this->waitQueue[$msgid]);
    }

    public function pubcomp(MQTT $mqtt, PUBCOMP $pubcomp_object)
    {
        $msgid = $pubcomp_object->getMsgID();
        echo "======== pubcomp: Remove from queue msgid={$msgid}\n";
        unset($this->waitQueue[$msgid]);
    }
}

