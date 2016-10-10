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
use sskaje\mqtt\Debug;
use sskaje\mqtt\Exception;
use sskaje\mqtt\Message;
use sskaje\mqtt\Message\Header;
use sskaje\mqtt\Utility;


/**
 * Base class for Headers
 *
 * Fixed Header
 *
 * First two or more bytes in Control Packets
 * 4-bit MQTT Control Packet type, 4-bit Flags, 1+ bytes remaining length
 */
class Base
{
    /**
     * Flags
     *
     * @var int
     */
    protected $reserved_flags = 0x0;

    /**
     * Remaining Length
     *
     * @var int
     */
    protected $remaining_length = 0;

    /**
     * Encoded Remaining Length
     *
     * @var string
     */
    protected $remaining_length_bytes = '';

    /**
     * Is Packet Identifier A MUST
     *
     * @var bool
     */
    protected $require_msgid = false;

    /**
     * Packet Identifier
     *
     * @var int
     */
    public $msgid = 0;

    /**
     *
     * @var \sskaje\mqtt\Message\Base
     */
    protected $message;

    public function __construct(\sskaje\mqtt\Message\Base $message)
    {
        $this->message = $message;
    }

    /**
     * Decode Packet Header and returns payload position.
     *
     * @param string & $packet_data
     * @param int    $remaining_length
     * @param int    & $payload_pos
     * @throws \sskaje\mqtt\Exception
     */
    final public function decode(& $packet_data, $remaining_length, & $payload_pos)
    {
        $cmd = Utility::ParseCommand(ord($packet_data[0]));
        $message_type = $cmd['message_type'];

        if ($this->message->getMessageType() != $message_type) {
            throw new Exception('Unexpected Control Packet Type');
        }

        $flags        = $cmd['flags'];
        $this->setFlags($flags);

        $pos = 1;

        $rl_len = strlen(($this->remaining_length_bytes = Utility::EncodeLength($remaining_length)));

        if (strpos($packet_data, $this->remaining_length_bytes, 1) !== $pos) {
            throw new Exception('Remaining Length mismatch.');
        }
        $pos += $rl_len;

        $this->remaining_length = $remaining_length;

        $this->decodeVariableHeader($packet_data, $pos);

        $payload_pos = $pos;
    }

    /**
     * Set Flags
     *
     * @param int $flags
     * @return bool
     * @throws Exception
     */
    protected function setFlags($flags)
    {
        if ($flags != $this->reserved_flags) {
            throw new Exception('Flags mismatch.');
        }

        return true;
    }

    /**
     * Decode Variable Header
     *
     * @param string & $packet_data
     * @param int    & $pos
     * @return bool
     */
    protected function decodeVariableHeader(& $packet_data, & $pos)
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
        return $this->message->getMessageType();
    }

    /**
     * Set Remaining Length
     *
     * @param int $length
     */
    public function setRemainingLength($length)
    {
        $this->remaining_length = $length;
        $this->remaining_length_bytes = Utility::EncodeLength($this->remaining_length);
    }

    /**
     * Set Payload Length
     *
     * @param int $length
     * @throws Exception
     */
    public function setPayloadLength($length)
    {
        $this->setRemainingLength($length + strlen($this->buildVariableHeader()));
    }

    /**
     * Get Remaining Length Field
     *
     * @return int
     */
    public function getRemainingLength()
    {
        return $this->remaining_length;
    }

    /**
     * Get Full Header Length
     *
     * @return int
     */
    public function getFullLength()
    {
        $cmd_length = 1;

        $rl_length = strlen($this->remaining_length_bytes);

        return $cmd_length + $rl_length + $this->remaining_length;
    }

    /**
     * Set Packet Identifier
     *
     * @param int $msgid
     * @throws Exception
     */
    public function setMsgID($msgid)
    {
        Utility::CheckPacketIdentifier($msgid);

        $this->msgid = $msgid;
    }

    /**
     * Get Packet Identifier
     *
     * @return int
     */
    public function getMsgID()
    {
        return $this->msgid;
    }

    /**
     * Default Variable Header
     *
     * @return string
     * @throws Exception
     */
    protected function buildVariableHeader()
    {
        $buffer = '';
        # Variable Header
        # Packet Identifier
        if ($this->require_msgid) {
            $buffer .= $this->packPacketIdentifer();
        }

        return $buffer;
    }

    final protected function packPacketIdentifer()
    {
        if (!$this->msgid) {
            throw new Exception('Invalid Packet Identifier');
        }

        Debug::Log(Debug::DEBUG, 'msgid='.$this->msgid);
        return pack('n', $this->msgid);
    }

    final protected function decodePacketIdentifier(& $packet_data, & $pos)
    {
        $msgid = Utility::ExtractUShort($packet_data, $pos);
        $this->setMsgID($msgid);

        return true;
    }

    /**
     * Build fixed Header packet
     *
     * @return string
     * @throws Exception
     */
    public function build()
    {
        # Fixed Header
        # Control Packet Type
        $cmd = $this->getMessageType() << 4;

        $cmd |= ($this->reserved_flags & 0x0F);

        $header = chr($cmd) . $this->remaining_length_bytes;

        $header .= $this->buildVariableHeader();

        return $header;
    }
}

# EOF
