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


/**
 * Fixed Header definition for CONNACK
 */
class CONNACK extends Base
{
    /**
     * Default Flags
     *
     * @var int
     */
    protected $reserved_flags = 0x00;

    /**
     * CONNACK does not have Packet Identifier
     *
     * @var bool
     */
    protected $require_msgid = false;

    /**
     * Session Present
     *
     * @var int
     */
    protected $session_present = 0;

    /**
     * Connect Return code
     *
     * @var int
     */
    protected $return_code = 0;

    /**
     * Default error definitions
     *
     * @var array
     */
    static public $connect_errors = array(
        0   =>  'Connection Accepted',
        1   =>  'Connection Refused: unacceptable protocol version',
        2   =>  'Connection Refused: identifier rejected',
        3   =>  'Connection Refused: server unavailable',
        4   =>  'Connection Refused: bad user name or password',
        5   =>  'Connection Refused: not authorized',
    );

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
        $this->session_present = ord($packet_data[2]) & 0x01;

        $this->return_code = ord($packet_data[3]);

        if ($this->return_code != 0) {
            $error = isset(self::$connect_errors[$this->return_code]) ? self::$connect_errors[$this->return_code] : 'Unknown error';
            Debug::Log(
                Debug::ERR,
                sprintf(
                    "Connection failed! (Error: 0x%02x 0x%02x|%s)",
                    ord($packet_data[2]),
                    $this->return_code,
                    $error
                )
            );

            /*
             If a server sends a CONNACK packet containing a non-zero return code it MUST
             then close the Network Connection [MQTT-3.2.2-5]
             */
            throw new Exception\ConnectError($error);
        }

        if ($this->session_present) {
            Debug::Log(Debug::DEBUG, "CONNACK: Session Present Flag: ON");
        } else {
            Debug::Log(Debug::DEBUG, "CONNACK: Session Present Flag: OFF");
        }
    }
}

# EOF