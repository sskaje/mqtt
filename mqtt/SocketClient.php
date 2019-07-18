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

/**
 * Socket Client
 *
 * @package sskaje\mqtt
 */
class SocketClient
{
    /**
     * Socket Connection Resource
     *
     * @var resource
     */
    protected $socket;
    /**
     * Server Address
     *
     * @var string
     */
    protected $address;

    /**
     * Stream Context
     *
     * @var resource
     */
    protected $context;

    public function __construct($address)
    {
        $this->address = $address;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Get Server Address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set Stream Context
     *
     * @param resource $context    A valid context resource created with stream_context_create()
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * create socket
     * @return bool
     */
    public function connect()
    {
        Debug::Log(Debug::DEBUG, 'socket_connect()');
        if (!$this->context) {
            $context = stream_context_create();
        } else {
            $context = $this->context;
        }
        Debug::Log(Debug::DEBUG, 'socket_connect(): connect to='.$this->address);

        $this->socket = @stream_socket_client(
            $this->address,
            $errno,
            $errstr,
            60,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!$this->socket) {
            Debug::Log(Debug::DEBUG, "stream_socket_client() {$errno}, {$errstr}");
            return false;
        }
        stream_set_timeout($this->socket,  5);

        #TODO:  MUST BE IN BLOCKING MODE
        #$this->set_blocking();

        return true;
    }

    /**
     * Set Blocking Mode
     */
    public function set_blocking()
    {
        if (!$this->socket || !is_resource($this->socket)) return false;
        Debug::Log(Debug::DEBUG, 'SOCKET: blocking mode: ON');
        stream_set_blocking($this->socket, true);
        return true;
    }

    /**
     * Set Non-Blocking Mode
     */
    public function set_non_blocking()
    {
        if (!$this->socket || !is_resource($this->socket)) return false;
        Debug::Log(Debug::DEBUG, 'SOCKET: blocking mode: OFF');
        stream_set_blocking($this->socket, false);
        return true;
    }

    /**
     * Send data
     *
     * @param string $packet
     * @param int    $packet_size
     * @return int
     */
    public function write($packet, $packet_size)
    {
        if (!$this->socket || !is_resource($this->socket)) return false;
        Debug::Log(Debug::DEBUG, "socket_write(length={$packet_size})", $packet);
        return fwrite($this->socket, $packet, $packet_size);
    }

    /**
     * Read data
     *
     * @param int $length
     * @return string
     */
    public function read($length = 8192)
    {
        if (!$this->socket || !is_resource($this->socket)) return false;

        Debug::Log(Debug::DEBUG, "socket_read({$length})");

        $string = "";
        $togo = $length;

        while (!feof($this->socket) && $togo>0) {
            $togo = $length - strlen($string);
            if($togo) $string .= fread($this->socket, $togo);
        }

        return $string;
    }

    /**
     * Close socket
     *
     * @return bool
     */
    public function close()
    {
        if (is_resource($this->socket)) {
            Debug::Log(Debug::DEBUG, 'socket_close()');
            return fclose($this->socket);
        }

        return true;
    }

    /**
     * Is EOF
     *
     * @return bool
     */
    public function eof()
    {
        return feof($this->socket);
    }

    /**
     * stream_select
     *
     * @param int $timeout
     * @return int
     */
    public function select($timeout)
    {
        $read = array($this->socket);
        $write = $except = NULL;

        return stream_select($read, $write, $except, $timeout);
    }
}

# EOF
