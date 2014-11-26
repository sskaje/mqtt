<?php
/**
 * MQTT Client
 *
 * @author sskaje
 */

/*
Copyright (c) 2013 sskaje

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
 */

/**
 * Base class for spMQTT Project
 */
class spMQTTBase {}
/**
 * Class spMQTT
 */
class spMQTT extends spMQTTBase{

    protected $clientid;
    protected $address;
    protected $socket;
    protected $keepalive = 60;
    protected $username = null;
    protected $password = null;
    protected $connect_clean = true;
    protected $connect_will = null;

    /**
     * Create spMQTTMessage object
     *
     * @param int $message_type
     * @return spMQTTMessage
     * @throws SPMQTT_Exception
     */
    public function getMessageObject($message_type) {
        if (!isset(spMQTTMessageType::$class[$message_type])) {
            throw new SPMQTT_Exception('Message type not defined', 100001);
        } else {
            return new spMQTTMessageType::$class[$message_type]($this);
        }
    }

    public function __construct($address, $clientid=null) {
        $this->address = $address;
        # check client id
        spMQTTUtil::CheckClientID($clientid);

        $this->clientid = $clientid;
        
        date_default_timezone_set('UTC');
    }

    /**
     * create socket
     * @return bool
     */
    protected function socket_connect() {
        spMQTTDebug::Log('socket_connect()');
        $context = stream_context_create();
        spMQTTDebug::Log('socket_connect(): connect to='.$this->address);

        $this->socket = stream_socket_client(
            $this->address,
            $errno,
            $errstr,
            60,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!$this->socket) {
            spMQTTDebug::Log("stream_socket_client() {$errno}, {$errstr}", true);
            return false;
        }
        stream_set_timeout($this->socket,  5);
        # MUST BE IN BLOCKING MODE
        stream_set_blocking($this->socket, true);

        return true;
    }

    /**
     * Send data
     *
     * @param string $packet
     * @param int $packet_size
     * @return int
     */
    public function socket_write($packet, $packet_size) {
        if (!$this->socket || !is_resource($this->socket)) return false;
        return fwrite($this->socket, $packet, $packet_size);
    }

    /**
     * Read data
     *
     * @param int $length
     * @return string
     */
    public function socket_read($length = 8192 ){
        if (!$this->socket || !is_resource($this->socket)) return false;

        //	print_r(socket_get_status($this->socket));
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
    protected function socket_close() {
        if (is_resource($this->socket)) {
            spMQTTDebug::Log('socket_close()');
            return fclose($this->socket);
        }
    }

    /**
     * Reconnect connection
     *
     * @param bool $close_current close current existed connection
     * @return bool
     */
    public function reconnect($close_current=true) {
        spMQTTDebug::Log('reconnect()');
        if ($close_current) {
            spMQTTDebug::Log('reconnect(): close current');
            $this->disconnect();
            $this->socket_close();
        }

        return $this->connect();
    }

    /**
     * Set username/password
     *
     * @param string $username
     * @param string $password
     */
    public function setAuth($username=null, $password=null) {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Set Keep Alive timer
     *
     * @param int $keepalive
     */
    public function setKeepalive($keepalive) {
        $this->keepalive = (int) $keepalive;
    }

    /**
     * Set Clean Session
     *
     * @param bool $clean
     */
    public function setConnectClean($clean) {
        $this->connect_clean = $clean ? true : false;
    }

    /**
     * Set Will message
     *
     * @param spMQTTWill $will
     */
    public function setWill(spMQTTWill $will) {
        $this->connect_will = $will;
    }

    /**
     * Connect to broker
     *
     * @return bool
     */
    public function connect() {
        # create socket resource
        if (!$this->socket_connect()) {
            return false;
        }
        spMQTTDebug::Log('connect()');

        $connectobj = $this->getMessageObject(spMQTTMessageType::CONNECT);

        if (!$this->connect_clean && empty($this->clientid)) {
            throw new SPMQTT_Exception('Client id must be provided if Clean Session flag is set false.', 100701);
        }

        # default client id
        if (empty($this->clientid)) {
            $clientid = 'mqtt'.substr(md5(uniqid('mqtt', true)), 8, 16);
        } else {
            $clientid = $this->clientid;
        }
        $connectobj->setClientID($clientid);
        spMQTTDebug::Log('connect(): clientid=' . $clientid);
        $connectobj->setKeepalive($this->keepalive);
        spMQTTDebug::Log('connect(): keepalive=' . $this->keepalive);
        $connectobj->setAuth($this->username, $this->password);
        spMQTTDebug::Log('connect(): username=' . $this->username . ' password=' . $this->password);
        $connectobj->setClean($this->connect_clean);
        if ($this->connect_will instanceof spMQTTWill) {
            $connectobj->setWill($this->connect_will);
        }

        $length = 0;
        $msg = $connectobj->build($length);

        $bytes_written = $connectobj->write();
        spMQTTDebug::Log('connect(): bytes written=' . $bytes_written);


        $connackobj = null;
        $connected = $connectobj->read(spMQTTMessageType::CONNACK, $connackobj);
        spMQTTDebug::Log('connect(): connected=' . ($connected ? 1 : 0));

        # save current time for ping ?

        return $connected;
    }

    /**
     * Publish message to topic
     *
     * @param string $topic
     * @param string $message
     * @param int $dup
     * @param int $qos
     * @param int $retain
     * @param int|null $msgid
     * @return array|bool
     */
    public function publish($topic, $message, $dup=0, $qos=0, $retain=0, $msgid=null) {
        spMQTTDebug::Log('publish()');
        $publishobj = $this->getMessageObject(spMQTTMessageType::PUBLISH);
        $publishobj->setTopic($topic);
        $publishobj->setMessage($message);
        $publishobj->setDup($dup);
        $publishobj->setQos($qos);
        $publishobj->setRetain($retain);
        $publishobj->setMsgID($msgid);

        $publish_bytes_written = $publishobj->write();
        spMQTTDebug::Log('publish(): bytes written=' . $publish_bytes_written);

        if ($qos == 0) {
            return array(
                'qos'   =>  $qos,
                'ret'   =>  $publish_bytes_written != false,
                'publish' =>  $publish_bytes_written,
            );
        } else if ($qos == 1) {
            # QoS = 1, PUBLISH + PUBACK
            $pubackobj = null;
            $puback_msgid = $publishobj->read(spMQTTMessageType::PUBACK, $pubackobj);

            return array(
                'qos'   =>  $qos,
                'ret'   =>  $publish_bytes_written != false,
                'publish' =>  $publish_bytes_written,
                'puback'  =>  $puback_msgid,
            );
        } else if ($qos == 2) {
            # QoS = 2, PUBLISH + PUBREC + PUBREL + PUBCOMP

            $pubrecobj = null;
            $pubrec_msgid = $publishobj->read(spMQTTMessageType::PUBREC, $pubrecobj);

            $pubrelobj = $this->getMessageObject(spMQTTMessageType::PUBREL);
            $pubrelobj->setMsgID($pubrec_msgid);
            $pubrel_bytes_written = $pubrelobj->write();

            $pubcompobj = null;
            $pubcomp_msgid = $pubrelobj->read(spMQTTMessageType::PUBCOMP, $pubcompobj);

            return array(
                'qos'   =>  $qos,
                'ret'   =>  $publish_bytes_written != false,
                'publish' =>  $publish_bytes_written,
                'pubrec'  =>  $pubrec_msgid,
                'pubrel'  =>  $pubrel_bytes_written,
                'pubcomp' =>  $pubcomp_msgid,
            );
        } else {
            return false;
        }
    }

    /**
     * SUBSCRIBE
     *
     * @param array $topics array(array(string topic, int qos, callback callback))
     * @param int $default_qos
     * @param null $default_callback
     */
    public function subscribe(array $topics) {
        foreach ($topics as $topic_name=>$topic_qos) {
            $this->topics_to_subscribe[$topic_name] = $topic_qos;
        }
        return true;
    }

    /**
     * Topics
     *
     * @var array
     */
    protected $topics = array();

    protected $topics_to_subscribe = array();
    protected $topics_to_unsubscribe = array();

    /**
     * SUBSCRIBE
     *
     * @param int $default_qos
     * @param null $default_callback
     */
    protected function do_subscribe() {
        # set msg id
        $msgid = mt_rand(1, 65535);
        # send SUBSCRIBE
        $subscribeobj = $this->getMessageObject(spMQTTMessageType::SUBSCRIBE);
        $subscribeobj->setMsgID($msgid);

        if (count($this->topics_to_subscribe) > 100) {
            throw new SPMQTT_Exception('Don\'t try to subscribe more than 100 topics', 100401);
        }

        $all_topic_qos = array();
        foreach ($this->topics_to_subscribe as $topic_name=>$topic_qos) {
            spMQTTUtil::CheckQos($topic_qos);

            $this->topics[$topic_name] = $topic_qos;

            $subscribeobj->addTopic(
                $topic_name,
                $topic_qos
            );
            $all_topic_qos[] = $topic_qos;
            unset($this->topics_to_subscribe[$topic_name]);
        }

        spMQTTDebug::Log('do_subscribe(): msgid=' . $msgid);
        $subscribe_bytes_written = $subscribeobj->write();
        spMQTTDebug::Log('do_subscribe(): bytes written=' . $subscribe_bytes_written);

//        # TODO: SUBACK+PUBLISH
//        # read SUBACK
//        $subackobj = null;
//        $suback_result = $subscribeobj->read(spMQTTMessageType::SUBACK, $subackobj);
//
//        # check msg id & qos payload
//        if ($msgid != $suback_result['msgid']) {
//            throw new SPMQTT_Exception('SUBSCRIBE/SUBACK message identifier mismatch: ' . $msgid . ':' . $suback_result['msgid'], 100402);
//        }
//        if ($all_topic_qos != $suback_result['qos']) {
//            throw new SPMQTT_Exception('SUBACK returned qos list doesn\'t match SUBSCRIBE', 100403);
//        }

        return array($msgid, $all_topic_qos);
    }


    /**
     * loop
     * @param callback $callback function(spMQTT $mqtt, $topic, $message)
     * @throws SPMQTT_Exception
     */
    public function loop($callback) {
        spMQTTDebug::Log('loop()');

        if (empty($this->topics) && empty($this->topics_to_subscribe)) {
            throw new SPMQTT_Exception('No topic subscribed/to be subscribed', 100601);
        }

        $last_subscribe_msgid = 0;
        $last_subscribe_qos = array();
        $last_unsubscribe_msgid = 0;
        while (1) {
            # Subscribe topics
            if (!empty($this->topics_to_subscribe)) {
                list($last_subscribe_msgid, $last_subscribe_qos) = $this->do_subscribe();
            }
            # Unsubscribe topics
            if (!empty($this->topics_to_unsubscribe)) {
                $last_unsubscribe_msgid = $this->do_unsubscribe();
            }

            $sockets = array($this->socket);
            $w = $e = NULL;

            if (stream_select($sockets, $w, $e, $this->keepalive / 2)) {
                if (feof($this->socket) || !$this->checkAndPing()) {
                    spMQTTDebug::Log('loop(): EOF detected');
                    $this->reconnect();
                    $this->subscribe($this->topics);
                }

                # The maximum value of remaining length is 268 435 455, FF FF FF 7F.
                # In most cases, 4 bytes is enough for fixed header and remaining length.
                # For PUBREL and UNSUBACK, 4 bytes is the maximum length.
                # For SUBACK, QoS list should be checked.
                # So, read the first 4 bytes and try to figure out the remaining length,
                # then read else.

                # read 4 bytes
                $read_bytes = 4;
                $read_message = $this->socket_read($read_bytes);
                if (empty($read_message)) {
                    continue;
                }

                $cmd = spMQTTUtil::UnpackCommand(ord($read_message[0]));

                $message_type = $cmd['message_type'];
                $dup = $cmd['dup'];
                $qos = $cmd['qos'];
                $retain = $cmd['retain'];

                spMQTTDebug::Log("loop(): message_type={$message_type}, dup={$dup}, QoS={$qos}, RETAIN={$retain}");

                $flag_remaining_length_finished = 0;
                for ($i=1; isset($read_message[$i]); $i++) {
                    if (ord($read_message[$i]) < 0x80) {
                        $flag_remaining_length_finished = 1;
                        break;
                    }
                }
                if (empty($flag_remaining_length_finished)) {
                    # read 3 more bytes
                    $read_message .= $this->socket_read(3);
                }

                $pos = 1;
                $len = $pos;
                $remaining_length = spMQTTUtil::RemainingLengthDecode($read_message, $pos);
                if ($flag_remaining_length_finished) {
                    $to_read = $remaining_length - (3 + $len - $pos);
                } else {
                    $to_read = $remaining_length - 2;
                }
                spMQTTDebug::Log('loop(): remaining length=' . $remaining_length . ' to read='.$to_read);

                $read_message .= $this->socket_read($to_read);

                spMQTTDebug::Log('loop(): read message=' . spMQTTUtil::PrintHex($read_message, true));

                switch ($message_type) {
                # Process PUBLISH
                case spMQTTMessageType::PUBLISH:
                    spMQTTDebug::Log('loop(): PUBLISH');
                    # topic length
                    $topic_length = spMQTTUtil::ToUnsignedShort(substr($read_message, $pos, 2));
                    $pos += 2;
                    # topic content
                    $topic = substr($read_message, $pos, $topic_length);
                    $pos += $topic_length;

                    # PUBLISH QoS 0 doesn't have msgid
                    if ($qos > 0) {
                        $msgid = spMQTTUtil::ToUnsignedShort(substr($read_message, $pos, 2));
                        $pos += 2;
                    }

                    # message content
                    $message = substr($read_message, $pos);

                    if ($qos == 0) {
                        spMQTTDebug::Log('loop(): PUBLISH QoS=0 PASS');
                        # Do nothing
                    } else if ($qos == 1) {
                        # PUBACK
                        $pubackobj = $this->getMessageObject(spMQTTMessageType::PUBACK);
                        $pubackobj->setDup($dup);
                        $pubackobj->setMsgID($msgid);
                        $puback_bytes_written = $pubackobj->write();
                        spMQTTDebug::Log('loop(): PUBLISH QoS=1 PUBACK written=' . $puback_bytes_written);

                    } else if ($qos == 2) {
                        # PUBREC
                        $pubrecobj = $this->getMessageObject(spMQTTMessageType::PUBREC);
                        $pubrecobj->setDup($dup);
                        $pubrecobj->setMsgID($msgid);
                        $pubrec_bytes_written = $pubrecobj->write();
                        spMQTTDebug::Log('loop(): PUBLISH QoS=2 PUBREC written=' . $pubrec_bytes_written);

                    } else {
                        # wrong packet
                        spMQTTDebug::Log('loop(): PUBLISH Invalid QoS');
                    }
                    # callback
                    call_user_func($callback, $this, $topic, $message);
                    break;

                # Process PUBREL
                case spMQTTMessageType::PUBREL:
                    spMQTTDebug::Log('loop(): PUBREL');
                    $msgid = spMQTTUtil::ToUnsignedShort(substr($read_message, $pos, 2));
                    $pos += 2;

                    # PUBCOMP
                    $pubcompobj = $this->getMessageObject(spMQTTMessageType::PUBCOMP);
                    $pubcompobj->setDup($dup);
                    $pubcompobj->setMsgID($msgid);
                    $pubcomp_bytes_written = $pubcompobj->write();
                    spMQTTDebug::Log('loop(): PUBREL QoS=2 PUBCOMP written=' . $pubcomp_bytes_written);
                    break;

                # Process SUBACK
                case spMQTTMessageType::SUBACK:
                    spMQTTDebug::Log('loop(): SUBACK');
                    $msgid = spMQTTUtil::ToUnsignedShort(substr($read_message, $pos, 2));
                    $pos += 2;

                    $qos_list = array();
                    for ($i=$pos; isset($read_message[$i]); $i++) {
                        # pick bit 0,1
                        $qos_list[] = ord($read_message[$i]) & 0x03;
                    }

                    # check msg id & qos payload
                    if ($msgid != $last_subscribe_msgid) {
                        spMQTTDebug::Log('loop(): SUBACK message identifier mismatch: ' . $msgid . ':' . $last_subscribe_msgid);
                    } else {
                        spMQTTDebug::Log('loop(): SUBACK msgid=' . $msgid);
                    }
                    if ($last_subscribe_qos != $qos_list) {
                        spMQTTDebug::Log('loop(): SUBACK returned qos list doesn\'t match SUBSCRIBE');
                    }

                    break;

                # Process UNSUBACK
                case spMQTTMessageType::UNSUBACK:
                    spMQTTDebug::Log('loop(): UNSUBACK');
                    $msgid = spMQTTUtil::ToUnsignedShort(substr($read_message, $pos, 2));
                    $pos += 2;

                    # TODO:???
                    if ($msgid != $last_unsubscribe_msgid) {
                        spMQTTDebug::Log('loop(): UNSUBACK message identifier mismatch ' . $msgid . ':' . $last_unsubscribe_msgid);
                    } else {
                        spMQTTDebug::Log('loop(): UNSUBACK msgid=' . $msgid);
                    }

                    break;

                }
            }
        }
    }

    protected function checkAndPing() {
        spMQTTDebug::Log('checkAndPing()');
        static $time = null;
        $current_time = time();
        if (empty($time)) {
            $time = $current_time;
        }

        if ($current_time - $time >= $this->keepalive / 2) {
            spMQTTDebug::Log("checkAndPing(): current_time={$current_time}, time={$time}, keepalive={$this->keepalive}");
            $time = $current_time;
            $ping_result = $this->ping();
            return $ping_result;
        }
        return true;
    }

    /**
     * Unsubscribe topics
     *
     * @param array $topics
     * @return bool
     * @throws SPMQTT_Exception
     */
    public function unsubscribe(array $topics) {
        foreach ($topics as $topic) {
            $this->topics_to_unsubscribe[] = $topic;
        }
        return true;
    }
    /**
     * Unsubscribe topics
     *
     * @param array $topics
     * @return bool
     * @throws SPMQTT_Exception
     */
    protected function do_unsubscribe() {
        # set msg id
        $msgid = mt_rand(1, 65535);
        # send SUBSCRIBE
        $unsubscribeobj = $this->getMessageObject(spMQTTMessageType::UNSUBSCRIBE);
        $unsubscribeobj->setMsgID($msgid);

        foreach ($this->topics_to_unsubscribe as $tn=>$topic_name) {
            if (!isset($this->topics[$topic_name])) {
                # log
                continue;
            }

            $unsubscribeobj->addTopic($topic_name);
            unset($this->topics[$topic_name]);
            unset($this->topics_to_unsubscribe[$tn]);
        }

        $unsubscribe_bytes_written = $unsubscribeobj->write();
        spMQTTDebug::Log('unsubscribe(): bytes written=' . $unsubscribe_bytes_written);

        # read UNSUBACK
        $unsubackobj = null;
        $unsuback_msgid = $unsubscribeobj->read(spMQTTMessageType::UNSUBACK, $unsubackobj);

        # check msg id & qos payload
        if ($msgid != $unsuback_msgid) {
            throw new SPMQTT_Exception('UNSUBSCRIBE/UNSUBACK message identifier mismatch: ' . $msgid . ':' . $unsuback_msgid, 100502);
        }

        return true;
    }

    /**
     * Disconnect connection
     *
     * @return bool
     */
    public function disconnect() {
        spMQTTDebug::Log('disconnect()');
        $disconnectobj = $this->getMessageObject(spMQTTMessageType::DISCONNECT);
        return $disconnectobj->write();
    }

    /**
     * Send PINGREQ and check PINGRESP
     *
     * @return bool
     */
    public function ping() {
        spMQTTDebug::Log('ping()');
        $pingreqobj = $this->getMessageObject(spMQTTMessageType::PINGREQ);
        $pingreqobj->write();
        $pingrespobj = null;
        $pingresp = $pingreqobj->read(spMQTTMessageType::PINGRESP, $pingrespobj);
        spMQTTDebug::Log('ping(): response ' . ($pingresp ? 1 : 0));
        return $pingresp;
    }
    
}

/**
 * Utilities for spMQTT
 */
class spMQTTUtil extends spMQTTBase {
    /**
     * Print string in Hex
     *
     * @param string $chars
     * @param bool $return
     */
    static public function PrintHex($chars, $return=false)
    {
        $output = '';
        for ($i=0; isset($chars[$i]); $i++) {
            $output .= sprintf('%02x ', ord($chars[$i]));
        }
        if ($output) {
            $output .= "\n";
        }
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }
    /**
     * Print string in Binary
     *
     * @param string $chars
     * @param bool $return
     */
    static public function PrintBin($chars, $return=false)
    {
        $output = '';
        for ($i=0; isset($chars[$i]); $i++) {
            $output .= sprintf('%08b ', ord($chars[$i]));
        }
        if ($output) {
            $output .= "\n";
        }
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }
    /**
     * return string with a 16-bit big endian length ahead.
     *
     * @param string $str  input string
     * @return string      returned string
     */
    static public function PackStringWithLength($str){
        $len = strlen($str);
        return pack('n', $len) . $str;
    }
    /**
     * Encode Remaining Length
     *
     * @param int $int
     * @return string
     */
    static public function RemainingLengthEncode($length)
    {
        $string = "";
        do{
            $digit = $length % 0x80;
            $length = $length >> 7;
            // if there are more digits to encode, set the top bit of this digit
            if ( $length > 0 ) $digit = ($digit | 0x80);
            $string .= chr($digit);
        } while ( $length > 0 );

        return $string;
    }
    /**
     * Decode Remaining Length
     *
     * @param string $msg
     * @param int & $i
     * @return int
     */
    static public function RemainingLengthDecode($msg, &$i){
        $multiplier = 1;
        $value = 0 ;
        do{
            $digit = ord($msg[$i]);
            $value += ($digit & 0x7F) * $multiplier;
            $multiplier *= 0x80;
            $i++;
        } while (($digit & 0x80) != 0);

        return $value;
    }

    /**
     * Check QoS
     *
     * @param int $qos
     * @throws SPMQTT_Exception
     */
    static public function CheckQos($qos)
    {
        if ($qos > 2 || $qos < 0) {
            throw new SPMQTT_Exception('QoS must be an integer in (0,1,2).', 300001);
        }
    }
    /**
     * Check Client ID
     *
     * @param string $clientid
     * @throws SPMQTT_Exception
     */
    static public function CheckClientID($clientid)
    {
        if (strlen($clientid) > 23) {
            throw new SPMQTT_Exception('Client identifier exceeds 23 bytes.', 300101);
        }
    }

    /**
     * Convert WORD to unsigned short
     *
     * @param string $word
     * @return int
     */
    static public function ToUnsignedShort($word) {
        return (ord($word[0]) << 8) | (ord($word[1]));
    }

    /**
     * Unpack command
     * @param int $cmd
     * @return array
     */
    static public function UnpackCommand($cmd) {
        # check message type
        $message_type = $cmd >> 4;
        $dup = ($cmd & 0x08) >> 3;
        $qos = ($cmd & 0x06) >> 1;
        $retain = ($cmd & 0x01);

        return array(
            'message_type'  =>  $message_type,
            'dup'           =>  $dup,
            'qos'           =>  $qos,
            'retain'        =>  $retain,
        );
    }
}

/**
 * Exception class
 */
class SPMQTT_Exception extends Exception {}

/**
 * Debug class
 */
class spMQTTDebug extends spMQTTBase {
    static protected $enabled = false;
    static public function Enable() {
        self::$enabled = true;
    }
    static public function Disable() {
        self::$enabled = false;
    }
    static public function Log($message, $error_log=false) {
        list($usec, $sec) = explode(" ", microtime());
        $datetime = date('Y-m-d H:i:s', $sec);
        $log_msg = sprintf("[%s.%06d] %s \n", $datetime, $usec * 1000000, trim($message));
        if (self::$enabled) {
            echo $log_msg;
        }

        if ($error_log) {
            error_log($log_msg);
        }
    }
}

/**
 * Message type definitions
 */
class spMQTTMessageType extends spMQTTBase {
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

    static public $class = array(
        spMQTTMessageType::CONNECT      => 'spMQTTMessage_CONNECT',
        spMQTTMessageType::CONNACK      => 'spMQTTMessage_CONNACK',
        spMQTTMessageType::PUBLISH      => 'spMQTTMessage_PUBLISH',
        spMQTTMessageType::PUBACK       => 'spMQTTMessage_PUBACK',
        spMQTTMessageType::PUBREC       => 'spMQTTMessage_PUBREC',
        spMQTTMessageType::PUBREL       => 'spMQTTMessage_PUBREL',
        spMQTTMessageType::PUBCOMP      => 'spMQTTMessage_PUBCOMP',
        spMQTTMessageType::SUBSCRIBE    => 'spMQTTMessage_SUBSCRIBE',
        spMQTTMessageType::SUBACK       => 'spMQTTMessage_SUBACK',
        spMQTTMessageType::UNSUBSCRIBE  => 'spMQTTMessage_UNSUBSCRIBE',
        spMQTTMessageType::UNSUBACK     => 'spMQTTMessage_UNSUBACK',
        spMQTTMessageType::PINGREQ      => 'spMQTTMessage_PINGREQ',
        spMQTTMessageType::PINGRESP     => 'spMQTTMessage_PINGRESP',
        spMQTTMessageType::DISCONNECT   => 'spMQTTMessage_DISCONNECT',
    );
    static public $name = array(
        spMQTTMessageType::CONNECT      => 'CONNECT',
        spMQTTMessageType::CONNACK      => 'CONNACK',
        spMQTTMessageType::PUBLISH      => 'PUBLISH',
        spMQTTMessageType::PUBACK       => 'PUBACK',
        spMQTTMessageType::PUBREC       => 'PUBREC',
        spMQTTMessageType::PUBREL       => 'PUBREL',
        spMQTTMessageType::PUBCOMP      => 'PUBCOMP',
        spMQTTMessageType::SUBSCRIBE    => 'SUBSCRIBE',
        spMQTTMessageType::SUBACK       => 'SUBACK',
        spMQTTMessageType::UNSUBSCRIBE  => 'UNSUBSCRIBE',
        spMQTTMessageType::UNSUBACK     => 'UNSUBACK',
        spMQTTMessageType::PINGREQ      => 'PINGREQ',
        spMQTTMessageType::PINGRESP     => 'PINGRESP',
        spMQTTMessageType::DISCONNECT   => 'DISCONNECT',
    );
}

/**
 * Base class for MQTT Messages
 */
abstract class spMQTTMessage extends spMQTTBase {
    /**
     * @var spMQTT
     */
    protected $mqtt;

    const FIXED_ONLY     = 0x01;
    const WITH_VARIABLE  = 0x02;
    const WITH_PAYLOAD   = 0x03;
    const MSGID_ONLY     = 0x04;

    protected $protocol_type = self::FIXED_ONLY;

    protected $read_bytes = 0;
    /**
     * @var spMQTTMessageHeader
     */
    protected $header = null;
    /**
     * Message Type
     *
     * @var int
     */
    protected $message_type = 0;

    public function __construct(spMQTT $mqtt) {
        $this->mqtt = $mqtt;
        $this->header = new spMQTTMessageHeader($this->message_type);
    }
    /**
     * Build packet data
     * @param int & $length
     * @return string
     * @throws SPMQTT_Exception
     */
    final public function build(&$length) {
        if ($this->protocol_type == self::FIXED_ONLY) {
            $message = $this->processBuild();
        } else if ($this->protocol_type == self::WITH_VARIABLE) {
            $message = $this->processBuild();
        } else if ($this->protocol_type == self::WITH_PAYLOAD) {
            $message = $this->processBuild();
        } else {
            throw new SPMQTT_Exception('Invalid protocol type', 200003);
        }

        $length = strlen($message);
        $this->header->setRemainingLength($length);
        spMQTTDebug::Log('Message Build: remaining length='.$length);
        $length += $this->header->getLength();
        return $this->header->build() . $message;
    }

    protected function processBuild() {return '';}
    protected function processRead($message) {return false;}

    /**
     * Read packet to generate an new message object
     *
     * @param int $message_type  Message type
     * @param spMQTTMessage & $class
     * @return mixed
     */
    final public function read($message_type=null, & $class=null) {
        if (!empty($message_type)) {
            spMQTTDebug::Log('Message Read: message_type='.$message_type);
            # create message type
            $class = $this->mqtt->getMessageObject($message_type);
        } else {
            spMQTTDebug::Log('Message Read: message_type='.$this->message_type);
            $class = $this;
        }

        spMQTTDebug::Log('Message Read: bytes to read='.$class->read_bytes);
        if ($class->read_bytes) {
            $message = $this->mqtt->socket_read($class->read_bytes);
        } else {
            $message = $this->mqtt->socket_read(8192);
        }
        spMQTTDebug::Log('Message read: message=' . spMQTTUtil::PrintHex($message, true));
        spMQTTDebug::Log('Message Read: bytes to read='.$class->read_bytes);

        if (!method_exists($class, 'processRead')) {
            throw new SPMQTT_Exception('"processRead($message)" not defined in '. get_class($class), 200201);
        }

        if ($class->protocol_type == self::FIXED_ONLY) {
            return $class->processRead($message);
        } else if ($class->protocol_type == self::WITH_VARIABLE) {
            return $class->processRead($message);
        } else if ($class->protocol_type == self::WITH_PAYLOAD) {
            return $class->processRead($message);
        } else {
            throw new SPMQTT_Exception('Invalid protocol type', 200202);
        }
    }
    /**
     * Process packet with Fixed Header + Message Identifier only
     *
     * @param string $message
     * @return array|bool
     */
    final protected function processReadFixedHeaderWithMsgID($message) {
        $packet_length = 4;
        $name = spMQTTMessageType::$name[$this->message_type];

        if (!isset($message[$packet_length - 1])) {
            # error
            spMQTTDebug::Log("Message {$name}: error on reading");
            return false;
        }

        $packet = unpack('Ccmd/Clength/nmsgid', $message);

        $packet['cmd'] = spMQTTUtil::UnpackCommand($packet['cmd']);

        if ($packet['cmd']['message_type'] != $this->message_type) {
            spMQTTDebug::Log("Message {$name}: type mismatch");
            return false;
        } else {
            spMQTTDebug::Log("Message {$name}: success");
            return $packet;
        }
    }

    /**
     * Send packet
     *
     * @return int
     */
    public function write() {
        spMQTTDebug::Log('Message write: message_type='.$this->message_type);
        $length = 0;
        $message = $this->build($length);
        $bytes_written = $this->mqtt->socket_write($message, $length);
        spMQTTDebug::Log('Message write: message=' . spMQTTUtil::PrintHex($message, true));
        spMQTTDebug::Log('Message write: bytes written='.$bytes_written);
        return $bytes_written;
    }
}

/**
 * Fixed Header
 */
class spMQTTMessageHeader extends spMQTTBase {
    protected $message_type = 0;
    protected $remaining_length = 0;
    protected $remaining_length_bytes = '';
    protected $dup = 0;
    protected $qos = 0;
    protected $retain = 0;

    public function __construct($message_type) {
        $this->message_type = (int) $message_type;
    }
    public function setDup($dup) {
        $this->dup = $dup ? 1 : 0;
    }
    public function getDup() {
        return $this->dup;
    }
    public function setQos($qos) {
        spMQTTUtil::CheckQos($qos);
        $this->qos = (int) $qos;
    }
    public function getQos() {
        return $this->qos;
    }
    public function setRetain($retain) {
        $this->retain = $retain ? 1 : 0;
    }
    public function getRetain() {
        return $this->retain;
    }
    public function setRemainingLength($remaining_length) {
        $this->remaining_length = $remaining_length;
        $this->remaining_length_bytes = spMQTTUtil::RemainingLengthEncode($this->remaining_length);
    }


    /**
     * Build fixed header packet
     *
     * @return string
     */
    public function build() {
        $cmd = $this->message_type << 4;
        $cmd |= ($this->dup << 3);
        $cmd |= ($this->qos << 1);
        $cmd |= $this->retain;

        return chr($cmd) . $this->remaining_length_bytes;
    }

    public function getLength() {
        return 1 + strlen($this->remaining_length_bytes);
    }
}

/**
 * Connect will
 */
class spMQTTWill {
    protected $retain = 0;
    protected $qos = 0;
    protected $flag = 0;
    protected $topic = '';
    protected $message = '';
    public function __construct($flag=1, $qos=1, $retain=0, $topic='', $message='')
    {
        $this->flag    = $flag ? 1 : 0;
        spMQTTUtil::CheckQos($qos);
        $this->qos     = (int) $qos;
        $this->retain  = $retain ? 1 : 0;
        $this->topic   = $topic;
        $this->message = $message;
    }

    public function getTopic() {
        return $this->topic;
    }
    public function getMessage() {
        return $this->message;
    }

    /**
     *
     * @return int
     */
    public function get()
    {
        $var = 0;
        if ($this->flag) {
            # Will flag
            $var |= 0x04;
            # Will QoS
            $var |= $this->qos << 3;
            # Will RETAIN
            if ($this->retain) {
                $var |= 0x20;
            }
        }

        return $var;
    }
}

/**
 * Message CONNECT
 */
class spMQTTMessage_CONNECT extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::CONNECT;
    protected $protocol_type = self::WITH_PAYLOAD;
    /**
     * @var spMQTTWill
     */
    protected $will;
    protected $clean = 1;
    protected $username = null;
    protected $password = null;
    protected $keepalive = 60;
    protected $clientid = '';

    public function setClean($clean) {
        $this->clean = $clean ? 1 : 0;
    }
    public function setWill(spMQTTWill $will) {
        $this->will = $will;
    }
    public function setAuth($username, $password=null) {
        $this->username = $username;
        $this->password = $password;
    }
    public function setKeepalive($keepalive) {
        $this->keepalive = (int) $keepalive;
    }
    public function setClientID($clientid) {
        $this->clientid = $clientid;
    }

    protected function processBuild() {;
        $buffer = "";

        $buffer .= chr(0x00); # 0x00
        $buffer .= chr(0x06); # 0x06
        $buffer .= chr(0x4d); # 'M'
        $buffer .= chr(0x51); # 'Q'
        $buffer .= chr(0x49); # 'I'
        $buffer .= chr(0x73); # 's'
        $buffer .= chr(0x64); # 'd'
        $buffer .= chr(0x70); # 'p'
        $buffer .= chr(0x03); # protocol version

        # Connect Flags
        # Set to 0 by default
        $var = 0;
        # clean session
        if ($this->clean) {
            $var|= 0x02;
        }
        # Will flags
        if ($this->will) {
            $var |= $this->will->get();
        }

        # User name flag
        if ($this->username != NULL) {
            $var |= 0x80;
        }
        # Password flag
        if ($this->password != NULL) {
            $var |= 0x40;
        }

        $buffer .= chr($var);
        # End of Connect Flags

        # Keep alive: unsigned short 16bits big endian
        $buffer .= pack('n', $this->keepalive);

        # Append client id
        $buffer .= spMQTTUtil::PackStringWithLength($this->clientid);

        # Adding will to payload
        if($this->will != NULL){
            $buffer .= spMQTTUtil::PackStringWithLength($this->will->getTopic());
            $buffer .= spMQTTUtil::PackStringWithLength($this->will->getMessage());
        }
        # Append User name
        if($this->username) {
            $buffer .= spMQTTUtil::PackStringWithLength($this->username);
        }
        # Append Password
        if($this->password) {
            $buffer .= spMQTTUtil::PackStringWithLength($this->password);
        }
        return $buffer;
    }
}

/**
 * Message CONNACK
 */
class spMQTTMessage_CONNACK extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::CONNACK;
    protected $protocol_type = self::WITH_VARIABLE;
    protected $read_bytes = 4;

    protected function processRead($message) {
        if (!isset($message[3])) {
            return false;
        }
        if (ord($message[0])>>4 == $this->message_type && $message[3] == chr(0)){
            spMQTTDebug::Log("Connected to Broker");
            return true;
        } else {
            $connect_errors = array(
                0   =>  'Connection Accepted',
                1   =>  'Connection Refused: unacceptable protocol version',
                2   =>  'Connection Refused: identifier rejected',
                3   =>  'Connection Refused: server unavailable',
                4   =>  'Connection Refused: bad user name or password',
                5   =>  'Connection Refused: not authorized',
            );

            spMQTTDebug::Log(
                sprintf(
                    "Connection failed! (Error: 0x%02x 0x%02x|%s)",
                    ord($message[0]),
                    ord($message[3]),
                    isset($connect_errors[ord($message[3])]) ? $connect_errors[ord($message[3])] : 'Unknown error'
                ),
                true
            );
            return false;
        }
    }
}

/**
 * Message PUBLISH
 */
class spMQTTMessage_PUBLISH extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PUBLISH;
    protected $protocol_type = self::WITH_PAYLOAD;

    protected $topic;
    protected $message;
    protected $msgid = 0;
    public function setTopic($topic) {
        $this->topic = $topic;
    }
    public function setMessage($message) {
        $this->message = $message;
    }
    public function setMsgID($msgid) {
        $this->msgid = $msgid;
    }
    public function setDup($dup) {
        return $this->header->setDup($dup);
    }
    public function setQos($qos) {
        return $this->header->setQos($qos);
    }
    public function setRetain($retain) {
        return $this->header->setRetain($retain);
    }

    protected function processBuild() {;
        $buffer = "";
        # Topic
        $buffer .= spMQTTUtil::PackStringWithLength($this->topic);
        spMQTTDebug::Log('Message PUBLISH: topic='.$this->topic);

        spMQTTDebug::Log('Message PUBLISH: QoS='.$this->header->getQos());
        # Message ID if QoS > 0
        if ($this->header->getQos()) {
            if ($this->msgid !== null) {
                $id = (int) $this->msgid;
            } else {
                $id = ++$this->msgid;
            }
            $buffer .= pack('n', $id);
            spMQTTDebug::Log('Message PUBLISH: msgid='.$id);
        }

        # Payload
        $buffer .= $this->message;
        spMQTTDebug::Log('Message PUBLISH: message='.$this->message);

        return  $buffer;
    }
}


/**
 * Message PUBACK
 */
class spMQTTMessage_PUBACK extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PUBACK;
    protected $protocol_type = self::WITH_VARIABLE;
    protected $read_bytes = 4;

    protected function processRead($message) {
        $puback_packet = $this->processReadFixedHeaderWithMsgID($message);
        if (!$puback_packet) {
            return false;
        }
        return $puback_packet['msgid'];
    }

    protected $msgid;
    public function setMsgID($msgid) {
        $this->msgid = $msgid;
    }
    public function setDup($dup) {
        return $this->header->setDup($dup);
    }

    protected function processBuild() {;
        $buffer = "";
        $buffer .= pack('n', $this->msgid);
        spMQTTDebug::Log('Message PUBACK: msgid='.$this->msgid);
        $this->header->setQos(1);
        return $buffer;
    }
}

/**
 * Message PUBREC
 */
class spMQTTMessage_PUBREC extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PUBREC;
    protected $protocol_type = self::WITH_VARIABLE;
    protected $read_bytes = 4;

    protected function processRead($message) {
        $pubrec_packet = $this->processReadFixedHeaderWithMsgID($message);
        if (!$pubrec_packet) {
            return false;
        }
        return $pubrec_packet['msgid'];
    }

    protected $msgid;
    public function setMsgID($msgid) {
        $this->msgid = $msgid;
    }
    public function setDup($dup) {
        return $this->header->setDup($dup);
    }

    protected function processBuild() {;
        $buffer = "";
        $buffer .= pack('n', $this->msgid);
        spMQTTDebug::Log('Message PUBREC: msgid='.$this->msgid);
        $this->header->setQos(1);
        return $buffer;
    }
}

/**
 * Message PUBREL
 */
class spMQTTMessage_PUBREL extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PUBREL;
    protected $protocol_type = self::WITH_VARIABLE;

    protected function processRead($message) {
        $pubrel_packet = $this->processReadFixedHeaderWithMsgID($message);
        if (!$pubrel_packet) {
            return false;
        }
        return $pubrel_packet['msgid'];
    }

    protected $msgid = 0;
    public function setMsgID($msgid) {
        $this->msgid = $msgid;
    }
    public function setDup($dup) {
        return $this->header->setDup($dup);
    }

    protected function processBuild() {;
        $buffer = "";
        $buffer .= pack('n', $this->msgid);
        spMQTTDebug::Log('Message PUBREL: msgid='.$this->msgid);
        $this->header->setQos(1);
        return $buffer;
    }
}

/**
 * Message PUBCOMP
 */
class spMQTTMessage_PUBCOMP extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PUBCOMP;
    protected $protocol_type = self::WITH_VARIABLE;
    protected $read_bytes = 4;

    protected function processRead($message) {
        $pubcomp_packet = $this->processReadFixedHeaderWithMsgID($message);
        if (!$pubcomp_packet) {
            return false;
        }
        return $pubcomp_packet['msgid'];
    }

    protected $msgid = 0;
    public function setMsgID($msgid) {
        $this->msgid = $msgid;
    }
    public function setDup($dup) {
        return $this->header->setDup($dup);
    }
    protected function processBuild() {;
        $buffer = "";

        $buffer .= pack('n', $this->msgid);
        spMQTTDebug::Log('Message PUBCOMP: msgid='.$this->msgid);

        $this->header->setQos(1);
        return $buffer;
    }
}

/**
 * Message SUBSCRIBE
 */
class spMQTTMessage_SUBSCRIBE extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::SUBSCRIBE;
    protected $protocol_type = self::WITH_PAYLOAD;

    protected $topics = array();
    protected $msgid = 0;
    public function addTopic($topic, $qos) {
        $this->topics[$topic] = $qos;
    }
    public function setMsgID($msgid) {
        $this->msgid = $msgid;
    }

    protected function processBuild() {;
        $buffer = "";

        # Variable Header: message identifier
        $buffer .= pack('n', $this->msgid);
        spMQTTDebug::Log('Message SUBSCRIBE: msgid='.$this->msgid);

        # Payload
        foreach ($this->topics as $topic=>$qos) {
            $topic_length = strlen($topic);
            $buffer .= pack('n', $topic_length);
            $buffer .= $topic;
            $buffer .= chr($qos);
        }

        # SUBSCRIBE uses QoS 1
        $this->header->setQos(1);
        #
        $this->header->setDup(0);
        return $buffer;
    }
}
/**
 * Message SUBACK
 */
class spMQTTMessage_SUBACK extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::SUBACK;
    protected $protocol_type = self::WITH_VARIABLE;
    protected $read_bytes = 4;

    protected function processRead($message) {
        $suback_packet = $this->processReadFixedHeaderWithMsgID($message);
        if (!$suback_packet) {
            return false;
        }

        $bytes = $this->mqtt->socket_read($suback_packet['length'] - 2);

        $return_qos = array();
        for ($i=0; isset($bytes[$i]); $i++) {
            # pick bit 0,1
            $return_qos[] = ord($bytes[$i]) & 0x03;
        }

        return array(
            'msgid' =>  $suback_packet['msgid'],
            'qos'   =>  $return_qos,
        );
    }
}

/**
 * Message UNSUBSCRIBE
 */
class spMQTTMessage_UNSUBSCRIBE extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::UNSUBSCRIBE;
    protected $protocol_type = self::WITH_PAYLOAD;

    protected $topics = array();
    protected $msgid = 0;
    public function addTopic($topic) {
        $this->topics[] = $topic;
    }
    public function setMsgID($msgid) {
        $this->msgid = $msgid;
    }

    protected function processBuild() {;
        $buffer = "";

        # Variable Header: message identifier
        $buffer .= pack('n', $this->msgid);
        spMQTTDebug::Log('Message UNSUBSCRIBE: msgid='.$this->msgid);

        # Payload
        foreach ($this->topics as $topic) {
            $topic_length = strlen($topic);
            $buffer .= pack('n', $topic_length);
            $buffer .= $topic;
        }

        # SUBSCRIBE uses QoS 1
        $this->header->setQos(1);
        $this->header->setDup(0);

        return $buffer;
    }
}
/**
 * Message UNSUBACK
 */
class spMQTTMessage_UNSUBACK extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::UNSUBACK;
    protected $protocol_type = self::FIXED_ONLY;
    protected $read_bytes = 4;

    protected function processRead($message) {
        $unsuback_packet = $this->processReadFixedHeaderWithMsgID($message);
        if (!$unsuback_packet) {
            return false;
        }
        return $unsuback_packet['msgid'];
    }
}
/**
 * Message PINGREQ
 */
class spMQTTMessage_PINGREQ extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PINGREQ;
    protected $protocol_type = self::FIXED_ONLY;
}
/**
 * Message PINGRESP
 */
class spMQTTMessage_PINGRESP extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PINGRESP;
    protected $protocol_type = self::FIXED_ONLY;
    protected $read_bytes = 2;

    protected function processRead($message) {
        # for PINGRESP
        if (!isset($message[$this->read_bytes - 1])) {
            # error
            spMQTTDebug::Log('Message PINGRESP: error on reading');
            return false;
        }

        $packet = unpack('Ccmd/Clength', $message);

        $packet['cmd'] = spMQTTUtil::UnpackCommand($packet['cmd']);

        if ($packet['cmd']['message_type'] != $this->message_type) {
            spMQTTDebug::Log("Message PINGRESP: type mismatch");
            return false;
        } else {
            spMQTTDebug::Log("Message PINGRESP: success");
            return true;
        }
    }
}
/**
 * Message DISCONNECT
 */
class spMQTTMessage_DISCONNECT extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::DISCONNECT;
    protected $protocol_type = self::FIXED_ONLY;
}
# EOF