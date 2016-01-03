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

namespace sskaje\mqtt\Message\Header;
use sskaje\mqtt\Exception;
use sskaje\mqtt\Message;
use sskaje\mqtt\MQTT;
use sskaje\mqtt\Utility;


/**
 * Fixed Header definition for CONNECT
 *
 * @property \sskaje\mqtt\Message\CONNECT $message
 */
class CONNECT extends Base
{
    /**
     * Default Flags
     *
     * @var int
     */
    protected $reserved_flags = 0x00;

    /**
     * CONNECT does not have Packet Identifier
     *
     * @var bool
     */
    protected $require_msgid = false;

    /**
     * Clean Session
     *
     * @var int
     */
    protected $clean = 1;

    /**
     * KeepAlive
     *
     * @var int
     */
    protected $keepalive = 60;

    /**
     * Clean Session
     *
     * Session is not stored currently.
     *
     * @todo Store Session  MQTT-3.1.2-4, MQTT-3.1.2-5
     * @param int $clean
     */
    public function setClean($clean)
    {
        $this->clean = $clean ? 1 : 0;
    }

    /**
     * Keep Alive
     *
     * @param int $keepalive
     */
    public function setKeepalive($keepalive)
    {
        $this->keepalive = (int) $keepalive;
    }

    /**
     * Build Variable Header
     *
     * @return string
     */
    protected function buildVariableHeader()
    {
        $buffer = "";

        # Protocol Name
        if ($this->message->mqtt->version() == MQTT::VERSION_3_1_1) {
            $buffer .= Utility::PackStringWithLength('MQTT');

        } else {
            $buffer .= Utility::PackStringWithLength('MQIsdp');
        }
        # End of Protocol Name

        # Protocol Level
        $buffer .= chr($this->message->mqtt->version());

        # Connect Flags
        # Set to 0 by default
        $var = 0;
        # clean session
        if ($this->clean) {
            $var |= 0x02;
        }
        # Will flags
        if ($this->message->will) {
            $var |= $this->message->will->get();
        }

        # User name flag
        if ($this->message->username != NULL) {
            $var |= 0x80;
        }
        # Password flag
        if ($this->message->password != NULL) {
            $var |= 0x40;
        }

        $buffer .= chr($var);
        # End of Connect Flags

        # Keep alive: unsigned short 16bits big endian
        $buffer .= pack('n', $this->keepalive);

        return $buffer;
    }

    /**
     * Decode Variable Header
     *
     * @param string & $packet_data
     * @param int    & $pos
     * @return bool
     * @throws Exception
     */
    protected function decodeVariableHeader(& $packet_data, & $pos)
    {
        throw new Exception('NO CONNECT will be sent to client');
    }
}

# EOF