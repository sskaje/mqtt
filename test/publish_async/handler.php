<?php
use sskaje\mqtt\MessageHandler;

class MyPublishHandler extends MessageHandler
{
    public $waitQueue = array();

    public function puback($mqtt, $puback_object)
    {
        $msgid = $puback_object->getMsgID();
        echo "======== puback: Remove from queue msgid={$msgid}\n";
        unset($this->waitQueue[$msgid]);
    }

    public function pubcomp($mqtt, $pubcomp_object)
    {
        $msgid = $pubcomp_object->getMsgID();
        echo "======== pubcomp: Remove from queue msgid={$msgid}\n";
        unset($this->waitQueue[$msgid]);
    }
}

