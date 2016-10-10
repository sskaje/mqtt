<?php

/**
 * MQTT Client
 *
 * An open source MQTT client library in PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2013 - 2016, sskaje (https://sskaje.me/)
 *
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 *
 * @package    sskaje/mqtt
 * @author     sskaje (https://sskaje.me/)
 * @copyright  Copyright (c) 2013 - 2016, sskaje (https://sskaje.me/)
 * @license    http://opensource.org/licenses/MIT MIT License
 * @link       https://sskaje.me/mqtt/
 */

namespace sskaje\mqtt\Message;
use sskaje\mqtt\Debug;
use sskaje\mqtt\Utility;
use sskaje\mqtt\Message;

/**
 * Message PUBLISH
 * Client <-> Server
 *
 * 3.3 PUBLISH – Publish Message
 *
 * @property header\PUBLISH $header
 */
class PUBLISH extends Base
{
    protected $message_type = Message::PUBLISH;
    protected $protocol_type = self::WITH_PAYLOAD;

    protected $topic;
    protected $message;

    /**
     * Set Topic
     *
     * @param string $topic
     */
    public function setTopic($topic)
    {
        Utility::CheckTopicName($topic);

        $this->topic = $topic;
    }

    /**
     * Get Topic
     *
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Set Message
     *
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Get Message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set DUP
     *
     * @param int $dup
     */
    public function setDup($dup)
    {
        $this->header->setDup($dup);
    }

    /**
     * Get DUP
     *
     * @return int
     */
    public function getDup()
    {
        return $this->header->getDup();
    }

    /**
     * Set QoS
     *
     * @param int $qos
     */
    public function setQos($qos)
    {
        $this->header->setQos($qos);
    }

    /**
     * Get QoS
     *
     * @return int
     */
    public function getQoS()
    {
        return $this->header->getQoS();
    }

    /**
     * Set RETAIN
     *
     * @param int $retain
     */
    public function setRetain($retain)
    {
        $this->header->setRetain($retain);
    }

    /**
     * Get RETAIN
     *
     * @return int
     */
    public function getRetain()
    {
        return $this->header->getRetain();
    }

    /**
     * Build Payload
     *
     * @return string
     */
    protected function payload()
    {
        $buffer = "";

        # Payload
        $buffer .= $this->message;
        Debug::Log(Debug::DEBUG, 'Message PUBLISH: Message='.$this->message);

        return  $buffer;
    }

    /**
     * Decode Payload
     *
     * @param string & $packet_data
     * @param int    & $payload_pos
     * @return void
     */
    protected function decodePayload(& $packet_data, & $payload_pos)
    {
        $this->message = substr($packet_data, $payload_pos);
    }
}

# EOF