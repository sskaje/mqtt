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


namespace sskaje\mqtt;
use sskaje\mqtt\Message\Header;

/**
 * Message type definitions
 */
class Message
{
    /**
     * Message Type: CONNECT
     */
    const CONNECT       = 0x01;
    /**
     * Message Type: CONNACK
     */
    const CONNACK       = 0x02;
    /**
     * Message Type: PUBLISH
     */
    const PUBLISH       = 0x03;
    /**
     * Message Type: PUBACK
     */
    const PUBACK        = 0x04;
    /**
     * Message Type: PUBREC
     */
    const PUBREC        = 0x05;
    /**
     * Message Type: PUBREL
     */
    const PUBREL        = 0x06;
    /**
     * Message Type: PUBCOMP
     */
    const PUBCOMP       = 0x07;
    /**
     * Message Type: SUBSCRIBE
     */
    const SUBSCRIBE     = 0x08;
    /**
     * Message Type: SUBACK
     */
    const SUBACK        = 0x09;
    /**
     * Message Type: UNSUBSCRIBE
     */
    const UNSUBSCRIBE   = 0x0A;
    /**
     * Message Type: UNSUBACK
     */
    const UNSUBACK      = 0x0B;
    /**
     * Message Type: PINGREQ
     */
    const PINGREQ       = 0x0C;
    /**
     * Message Type: PINGRESP
     */
    const PINGRESP      = 0x0D;
    /**
     * Message Type: DISCONNECT
     */
    const DISCONNECT    = 0x0E;

    static public $name = array(
        Message::CONNECT     => 'CONNECT',
        Message::CONNACK     => 'CONNACK',
        Message::PUBLISH     => 'PUBLISH',
        Message::PUBACK      => 'PUBACK',
        Message::PUBREC      => 'PUBREC',
        Message::PUBREL      => 'PUBREL',
        Message::PUBCOMP     => 'PUBCOMP',
        Message::SUBSCRIBE   => 'SUBSCRIBE',
        Message::SUBACK      => 'SUBACK',
        Message::UNSUBSCRIBE => 'UNSUBSCRIBE',
        Message::UNSUBACK    => 'UNSUBACK',
        Message::PINGREQ     => 'PINGREQ',
        Message::PINGRESP    => 'PINGRESP',
        Message::DISCONNECT  => 'DISCONNECT',
    );

    /**
     * Create Message Object
     *
     * @param int         $message_type
     * @param MQTT        $mqtt
     * @return mixed
     * @throws Exception
     */
    static public function Create($message_type, MQTT $mqtt)
    {
        if (!isset(Message::$name[$message_type])) {
            throw new Exception('Message type not defined');
        }

        $class = __NAMESPACE__ . '\\Message\\' . self::$name[$message_type];

        return new $class($mqtt);
    }

    /**
     * Maximum remaining length
     */
    const MAX_DATA_LENGTH = 268435455;
}

# EOF