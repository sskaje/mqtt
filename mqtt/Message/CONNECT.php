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
use sskaje\mqtt\Utility;
use sskaje\mqtt\Message;

/**
 * Message CONNECT
 * Client -> Server
 *
 * 3.1 CONNECT – Client requests a connection to a Server
 *
 * @property header\CONNECT $header
 */
class CONNECT extends Base
{
    protected $message_type = Message::CONNECT;
    protected $protocol_type = self::WITH_PAYLOAD;

    /**
     * Connect Will
     *
     * @var Will
     */
    public $will;

    /**
     * Username
     *
     * @var string
     */
    public $username = '';

    /**
     * Password
     *
     * @var string
     */
    public $password = '';

    /**
     * Client Identifier
     *
     * @var string
     */
    protected $clientid = '';

    /**
     * Clean Session
     *
     * @param int $clean
     */
    public function setClean($clean)
    {
        $this->header->setClean($clean);
    }

    /**
     * Connect Will
     *
     * @param Will $will
     */
    public function setWill(Will $will)
    {
        $this->will = $will;
    }

    /**
     * Keep Alive
     *
     * @param int $keepalive
     */
    public function setKeepalive($keepalive)
    {
        $this->header->setKeepalive($keepalive);
    }

    /**
     * Username and Password
     *
     * @param string $username
     * @param string $password
     */
    public function setAuth($username, $password='')
    {
        $this->username = $username;
        $this->password = $password;
    }

    protected function payload()
    {
        $payload = '';

        $payload .= Utility::PackStringWithLength($this->mqtt->clientid);

        # Adding Connect Will
        if ($this->will && $this->will->get()) {
            /*
             If the Will Flag is set to 0 the Will QoS and Will Retain fields in the Connect Flags
             MUST be set to zero and the Will Topic and Will Message fields MUST NOT be present in
             the payload [MQTT-3.1.2-11].
             */
            $payload .= Utility::PackStringWithLength($this->will->getTopic());
            $payload .= Utility::PackStringWithLength($this->will->getMessage());
        }

        # Append Username
        if ($this->username != NULL) {
            $payload .= Utility::PackStringWithLength($this->username);
        }
        # Append Password
        if ($this->password != NULL) {
            $payload .= Utility::PackStringWithLength($this->password);
        }

        return $payload;
    }
}

# EOF