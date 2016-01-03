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
use sskaje\mqtt\Message;
use sskaje\mqtt\Utility;


/**
 * Message UNSUBSCRIBE
 * Client -> Server
 *
 * 3.10 UNSUBSCRIBE – Unsubscribe from topics
 */
class UNSUBSCRIBE extends Base
{
    protected $message_type = Message::UNSUBSCRIBE;
    protected $protocol_type = self::WITH_PAYLOAD;

    protected $topics = array();

    public function addTopic($topic_filter)
    {
        Utility::CheckTopicFilter($topic_filter);
        $this->topics[] = $topic_filter;
    }

    protected function payload()
    {
        if (empty($this->topics)) {
            /*
             The Topic Filters in an UNSUBSCRIBE packet MUST be UTF-8 encoded strings as
             defined in Section 1.5.3, packed contiguously [MQTT-3.10.3-1].
             */
            throw new Exception('Missing topics!');
        }

        $buffer = "";

        # Payload
        foreach ($this->topics as $topic) {
            $buffer .= Utility::PackStringWithLength($topic);
        }

        return $buffer;
    }
}

# EOF