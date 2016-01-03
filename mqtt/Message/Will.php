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
use sskaje\mqtt\Exception;
use sskaje\mqtt\Utility;

/**
 * Connect Will
 *
 */
class Will
{
    /**
     * Will Retain
     *
     * @var int
     */
    protected $retain = 0;
    /**
     * Will QoS
     *
     * @var int
     */
    protected $qos = 0;
    /**
     * Will Topic
     *
     * @var string
     */
    protected $topic = '';
    /**
     * Will Message
     *
     * @var string
     */
    protected $message = '';

    /**
     * Will
     *
     * @param string $topic     Will Topic
     * @param string $message   Will Message
     * @param int    $qos       Will QoS
     * @param int    $retain    Will Retain
     * @throws Exception
     */
    public function __construct($topic, $message, $qos=1, $retain=0)
    {
        /*
         If the Will Flag is set to 0 the Will QoS and Will Retain fields in the Connect Flags
         MUST be set to zero and the Will Topic and Will Message fields MUST NOT be present in
         the payload [MQTT-3.1.2-11].
         */

        if (!$topic || !$message) {
            throw new Exception('Topic/Message MUST NOT be empty in Will Message');
        }

        Utility::CheckTopicName($topic);

        $this->topic   = $topic;
        $this->message = $message;

        Utility::CheckQoS($qos);
        $this->qos     = (int) $qos;
        $this->retain  = $retain ? 1 : 0;
    }

    /**
     * Get Will Topic
     *
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Get Will Message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get Will flags
     *
     * @return int
     */
    public function get()
    {
        $var = 0;
        # Will flag
        $var |= 0x04;
        # Will QoS
        $var |= $this->qos << 3;
        # Will RETAIN
        if ($this->retain) {
            $var |= 0x20;
        }

        return $var;
    }
}

# EOF