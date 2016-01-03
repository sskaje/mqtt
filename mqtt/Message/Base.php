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
use sskaje\mqtt\Exception;
use sskaje\mqtt\Message;
use sskaje\mqtt\MQTT;
use sskaje\mqtt\Utility;

/**
 * Base class for MQTT Messages
 */
abstract class Base
{
    /**
     * Message with Fixed Header ONLY
     *
     * CONNECT, CONNACK, PINGREQ, PINGRESP, DISCONNECT
     */
    const FIXED_ONLY     = 0x01;

    /**
     * Message with Variable Header
     * Fixed Header + Variable Header
     *
     */
    const WITH_VARIABLE  = 0x02;
    /**
     * Message with Payload
     * Fixed Header + Variable Header + Payload
     *
     */
    const WITH_PAYLOAD   = 0x03;
    /**
     * Protocol Type
     * Constants FIXED_ONLY, WITH_VARIABLE, WITH_PAYLOAD
     *
     * @var int
     */
    protected $protocol_type = self::FIXED_ONLY;

    /**
     * @var MQTT
     */
    public $mqtt;

    /**
     * Bytes to read
     *
     * @var int
     */
    protected $read_bytes = 0;

    /**
     * @var header\Base
     */
    public $header = null;

    /**
     * Control Packet Type
     *
     * @var int
     */
    protected $message_type = 0;

    public function __construct(MQTT $mqtt)
    {
        $this->mqtt = $mqtt;

        $header_class = __NAMESPACE__ . '\\Header\\' . Message::$name[$this->message_type];

        $this->header = new $header_class($this);
    }

    final public function decode($packet_data, $remaining_length)
    {
        $payload_pos = 0;

        $this->header->decode($packet_data, $remaining_length, $payload_pos);

        return $this->decodePayload($packet_data, $payload_pos);
    }

    protected function decodePayload(& $packet_data, & $payload_pos)
    {
        return true;
    }

    /**
     * Get Control Packet Type
     *
     * @return int
     */
    public function getMessageType()
    {
        return $this->message_type;
    }

    /**
     * Get Protocol Type
     *
     * @return int
     */
    public function getProtocolType()
    {
        return $this->protocol_type;
    }

    /**
     * Set Packet Identifier
     *
     * @param int $msgid
     */
    public function setMsgID($msgid)
    {
        $this->header->setMsgID($msgid);
    }

    /**
     * Get Packet Identifier
     *
     * @return int
     */
    public function getMsgID()
    {
        return $this->header->getMsgID();
    }


    /**
     * Build packet data
     *
     * @param int & $length
     * @return string
     * @throws Exception
     */
    final public function build(&$length=0)
    {
        if ($this->protocol_type == self::FIXED_ONLY) {
            $payload = $this->payload();
        } else if ($this->protocol_type == self::WITH_VARIABLE) {
            $payload = $this->payload();
        } else if ($this->protocol_type == self::WITH_PAYLOAD) {
            $payload = $this->payload();
        } else {
            throw new Exception('Invalid protocol type');
        }

        $length = strlen($payload);
        $this->header->setPayloadLength($length);

        $length = $this->header->getFullLength();
        Debug::Log(Debug::DEBUG, 'Message Build: total length='.$length);

        return $this->header->build() . $payload;
    }

    protected $payload = '';

    /**
     * Prepare Payload
     * Empty payload by default
     *
     * @return string
     */
    protected function payload()
    {
        return $this->payload;
    }

    /**
     * Process packet with Fixed Header + Message Identifier only
     *
     * @param string $message
     * @return array|bool
     */
    final protected function processReadFixedHeaderWithMsgID($message)
    {
        $packet_length = 4;
        $name = Message::$name[$this->message_type];

        if (!isset($message[$packet_length - 1])) {
            # error
            Debug::Log(Debug::DEBUG, "Message {$name}: error on reading");
            return false;
        }

        $packet = unpack('Ccmd/Clength/nmsgid', $message);

        $packet['cmd'] = Utility::UnpackCommand($packet['cmd']);

        if ($packet['cmd']['message_type'] != $this->getMessageType()) {
            Debug::Log(Debug::DEBUG, "Message {$name}: type mismatch");
            return false;
        } else {
            Debug::Log(Debug::DEBUG, "Message {$name}: success");
            return $packet;
        }
    }


}

# EOF