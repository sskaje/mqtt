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
 * Class CMDStore
 *
 * @package sskaje\mqtt
 */
class CMDStore
{

    protected $command_awaits = array();
    protected $command_awaits_counter = 0;

    /**
     * @param int $message_type
     * @param int $msgid
     * @return bool
     */
    public function isEmpty($message_type, $msgid=null)
    {
        if ($msgid) {
            return empty($this->command_awaits[$message_type][$msgid]);
        } else {
            return empty($this->command_awaits[$message_type]);
        }
    }

    /**
     * Add
     *
     * @param int   $message_type
     * @param int   $msgid
     * @param array $data
     */
    public function addWait($message_type, $msgid, array $data)
    {
        if (!isset($this->command_awaits[$message_type][$msgid])) {
            Debug::Log(Debug::DEBUG, "Waiting for " . Message::$name[$message_type] . " msgid={$msgid}");

            $this->command_awaits[$message_type][$msgid] = $data;
            ++ $this->command_awaits_counter;
        }
    }

    /**
     * Delete
     *
     * @param int $message_type
     * @param int $msgid
     */
    public function delWait($message_type, $msgid)
    {
        if (isset($this->command_awaits[$message_type][$msgid])) {
            Debug::Log(Debug::DEBUG, "Forget " . Message::$name[$message_type] . " msgid={$msgid}");

            unset($this->command_awaits[$message_type][$msgid]);
            -- $this->command_awaits_counter;
        }
    }

    /**
     * Get
     *
     * @param int $message_type
     * @param int $msgid
     * @return false|array
     */
    public function getWait($message_type, $msgid)
    {
        return $this->isEmpty($message_type, $msgid) ?
            false : $this->command_awaits[$message_type][$msgid];
    }

    /**
     * Get all by message_type
     *
     * @param int $message_type
     * @return array
     */
    public function getWaits($message_type)
    {
        return $this->isEmpty($this->command_awaits[$message_type]) ?
            false : $this->command_awaits[$message_type];
    }

    /**
     * Count
     *
     * @return int
     */
    public function countWaits()
    {
        return $this->command_awaits_counter;
    }
}

# EOF