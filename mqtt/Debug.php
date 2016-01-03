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
 * Debug class
 */
class Debug
{

    const NONE   = 0;
    const ERR    = 1;
    const WARN   = 2;
    const INFO   = 3;
    const NOTICE = 4;
    const DEBUG  = 5;
    const ALL    = 15;

    /**
     * Debug flag
     *
     * Disabled by default.
     *
     * @var bool
     */
    static protected $enabled = false;

    /**
     * Enable Debug
     */
    static public function Enable()
    {
        self::$enabled = true;
    }

    /**
     * Disable Debug
     */
    static public function Disable()
    {
        self::$enabled = false;
    }

    /**
     * Current Log Priority
     *
     * @var int
     */
    static protected $priority = self::WARN;

    /**
     * Log Priority
     *
     * @param int $priority
     */
    static public function SetLogPriority($priority)
    {
        self::$priority = (int) $priority;
    }

    /**
     * Log Message
     *
     * Message will be logged using error_log(), configure it with ini_set('error_log', ??)
     * If debug is enabled, Message will also be sent to stdout.
     *
     * @param int     $priority
     * @param string  $message
     * @param string  $bin_dump         If $bin_dump is not empty, hex/ascii char will be dumped
     */
    static public function Log($priority, $message, $bin_dump='')
    {
        static $DEBUG_NAME = array(
            self::DEBUG  => 'DEBUG',
            self::NOTICE => 'NOTICE',
            self::INFO   => 'INFO',
            self::WARN   => 'WARN',
            self::ERR    => 'ERROR',
        );

        $log_msg = sprintf(
            "%-6s %s",
            $DEBUG_NAME[$priority],
            trim($message)
        );

        if ($bin_dump) {
            $bin_dump = Utility::PrintHex($bin_dump, true, 16, true);
            $log_msg .= "\n" . $bin_dump;
        }

        if (self::$enabled) {
            list($usec, $sec) = explode(" ", microtime());
            $datetime = date('Y-m-d H:i:s', $sec);

            printf("[%s.%06d] ", $datetime, $usec * 1000000);
            echo $log_msg, "\n";
        }

        if ($priority <= self::$priority) {
            error_log($log_msg);
        }
    }
}

# EOF