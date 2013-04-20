<?php
/**
 * MQTT Client
 *
 * @author sskaje
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
        # Random client id
        if (empty($clientid)) {
            $clientid = 'mqtt'.substr(md5(uniqid('mqtt', true)), 8, 16);
        }
        $this->clientid = $clientid;
    }

    /**
     * create socket
     * @return bool
     */
    protected function socket_connect() {
        spMQTTDebug::Log('socket_connect()');
        $context = stream_context_create();
        $this->socket = stream_socket_client(
            $this->address,
            $errno,
            $errstr,
            60,
            STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT,
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
        return fwrite($this->socket, $packet, $packet_size);
    }

    /**
     * Read data
     *
     * @param int $length
     * @return string
     */
    public function socket_read($length = 8192 ){
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
        $this->socket_connect();
        spMQTTDebug::Log('connect()');

        $connectobj = $this->getMessageObject(spMQTTMessageType::CONNECT);

        # default client id
        if (empty($this->clientid)) {
            $clientid = 'mqtt'.substr(md5(uniqid('mqtt', true)), 8, 16);
        } else {
            $clientid = $this->clientid;
        }
        $connectobj->setClientID($clientid);
        $connectobj->setKeepalive($this->keepalive);
        $connectobj->setAuth($this->username, $this->password);
        $connectobj->setClean($this->connect_clean);
        if ($this->connect_will instanceof spMQTTWill) {
            $connectobj->setWill($this->connect_will);
        }

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
        $pingreqobj = $this->getMessageObject(spMQTTMessageType::PINGREQ);
        spMQTTDebug::Log('ping()');
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
     * @param int & $i     length of returned value + $i's original value
     * @return string      returned string
     */
    static public function PackStringWithLength($str, &$i){
        $len = strlen($str);
        $i += $len + 2;

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
     * @param string & $msg
     * @param int & $i
     * @return int
     */
    static public function RemainingLengthDecode(&$msg, &$i){
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
        if ($qos > 2 || $qos < 1) {
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
}

/**
 * Exception class
 */
class SPMQTT_Exception extends Exception {}

/**
 * Debug class
 */
class spMQTTDebug extends spMQTTBase {
    static protected $enabled = true;
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

        spMQTTMessageType::PINGREQ      => 'spMQTTMessage_PINGREQ',
        spMQTTMessageType::PINGRESP     => 'spMQTTMessage_PINGRESP',
        spMQTTMessageType::DISCONNECT   => 'spMQTTMessage_DISCONNECT',
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
    
    const SEND_FIXED_ONLY     = 0x01;
    const SEND_WITH_VARIABLE  = 0x02;
    const SEND_WITH_PAYLOAD   = 0x03;

    const RECV_FIXED_ONLY     = 0x11;
    const RECV_WITH_VARIABLE  = 0x12;
    const RECV_WITH_PAYLOAD   = 0x13;

    protected $protocol_type = self::SEND_FIXED_ONLY;
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
        if ($this->protocol_type == self::SEND_FIXED_ONLY) {
            return $this->buildFixedHeaderOnlyMessage($length);
        } else if ($this->protocol_type == self::SEND_WITH_VARIABLE) {
            if (!method_exists($this, 'buildMessageWithVariableHeader')) {
                throw new SPMQTT_Exception('"buildMessageWithVariableHeader(&$length)" not defined in '. __CLASS__, 200001);
            }
            return $this->buildMessageWithVariableHeader($length);
        } else if ($this->protocol_type == self::SEND_WITH_PAYLOAD) {
            if (!method_exists($this, 'buildMessageWithPayload')) {
                throw new SPMQTT_Exception('"buildMessageWithPayload(&$length)" not defined in '. __CLASS__, 200002);
            }
            return $this->buildMessageWithPayload($length);
        } else {
            throw new SPMQTT_Exception('Invalid protocol type', 200003);
        }
    }
    /**
     * Build a fixed-header-only message
     *
     * @param int & $length
     * @return string
     */
    protected function buildFixedHeaderOnlyMessage(&$length) {
        # Fixed header only
        $length = $this->header->getLength();
        return $this->header->build();
    }
    /*
        protected function buildMessageWithVariableHeader(&$length) {}
        protected function buildMessageWithPayload(&$length) {}
        protected function process_read($message) {}
    */
    /**
     * Read packet to generate an new message object
     *
     * @param int $message_type  Message type
     * @param spMQTTMessage & $class
     * @return mixed
     */
    public function read($message_type, & $class=null) {
        spMQTTDebug::Log('Message Read: message_type='.$message_type);
        # create message type
        $class = $this->mqtt->getMessageObject($message_type);

        spMQTTDebug::Log('Message Read: bytes to read='.$class->read_bytes);
        if ($class->read_bytes) {
            $message = $this->mqtt->socket_read($class->read_bytes);
        } else {
            $message = $this->mqtt->socket_read(8192);
        }
        spMQTTDebug::Log('Message read: message=' . spMQTTUtil::PrintHex($message, true));
        spMQTTDebug::Log('Message Read: bytes to read='.$class->read_bytes);

        if (!method_exists($class, 'process_read')) {
            throw new SPMQTT_Exception('"process_read($message)" not defined in '. get_class($class), 200201);
        }

        if ($class->protocol_type == self::RECV_FIXED_ONLY) {
            return $class->process_read($message);
        } else if ($class->protocol_type == self::RECV_WITH_VARIABLE) {
            return $class->process_read($message);
        } else if ($class->protocol_type == self::RECV_WITH_PAYLOAD) {
            return $class->process_read($message);
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

        return chr($cmd) . spMQTTUtil::RemainingLengthEncode($this->remaining_length);
    }
    public function getLength() {
        return 2;
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
    protected $protocol_type = self::SEND_WITH_PAYLOAD;
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

    protected function buildMessageWithPayload(&$length) {;
        # 3.1. CONNECT - Client requests a connection to a server
        $i = 0;
        $buffer = "";

        $buffer .= chr(0x00); $i++; # 0x00
        $buffer .= chr(0x06); $i++; # 0x06
        $buffer .= chr(0x4d); $i++; # 'M'
        $buffer .= chr(0x51); $i++; # 'Q'
        $buffer .= chr(0x49); $i++; # 'I'
        $buffer .= chr(0x73); $i++; # 's'
        $buffer .= chr(0x64); $i++; # 'd'
        $buffer .= chr(0x70); $i++; # 'p'
        $buffer .= chr(0x03); $i++; # protocol version

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

        $buffer .= chr($var);$i++;
        # End of Connect Flags

        # Keep alive: unsigned short 16bits big endian
        $buffer .= pack('n', $this->keepalive);
        $i += 2;

        # Append client id
        $buffer .= spMQTTUtil::PackStringWithLength($this->clientid, $i);

        # Adding will to payload
        if($this->will != NULL){
            $buffer .= spMQTTUtil::PackStringWithLength($this->will->getTopic(),   $i);
            $buffer .= spMQTTUtil::PackStringWithLength($this->will->getMessage(), $i);
        }
        # Append User name
        if($this->username) {
            $buffer .= spMQTTUtil::PackStringWithLength($this->username, $i);
        }
        # Append Password
        if($this->password) {
            $buffer .= spMQTTUtil::PackStringWithLength($this->password, $i);
        }

        $length = $i + $this->header->getLength();
        $this->header->setRemainingLength($i);
        return $this->header->build() . $buffer;
    }
}

/**
 * Message CONNACK
 */
class spMQTTMessage_CONNACK extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::CONNACK;
    protected $protocol_type = self::RECV_WITH_VARIABLE;
    protected $read_bytes = 4;

    protected function process_read($message) {
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
    protected $protocol_type = self::SEND_WITH_PAYLOAD;

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

    protected function buildMessageWithPayload(&$length) {;
        $i = 0;
        $buffer = "";
        # Topic
        $buffer .= spMQTTUtil::PackStringWithLength($this->topic,$i);
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
            $i += 2;
            spMQTTDebug::Log('Message PUBLISH: msgid='.$id);
        }

        # Payload
        $buffer .= $this->message;
        $i += strlen($this->message);
        spMQTTDebug::Log('Message PUBLISH: message='.$this->message);

        $length = $i + $this->header->getLength();
        $this->header->setRemainingLength($i);
        return $this->header->build() . $buffer;
    }
}


/**
 * Message PUBACK
 */
class spMQTTMessage_PUBACK extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PUBACK;
    protected $protocol_type = self::RECV_WITH_VARIABLE;
    protected $read_bytes = 4;

    protected function process_read($message) {
        # for PUBACK
        if (!isset($message[$this->read_bytes - 1])) {
            # error
            spMQTTDebug::Log('Message PUBACK: error on reading');
            return false;
        }

        # fixed message id
        $puback = unpack('cheader/clen/nmsgid', $message);

        # fixed header
        if ($puback['header'] >>4 == spMQTTMessageType::PUBACK && $puback['len'] == 0x02){
            spMQTTDebug::Log('Message PUBACK: continue');
        } else {
            spMQTTDebug::Log('Message PUBACK: protocol mismatch');
            return false;
        }

        spMQTTDebug::Log('Message PUBACK: msgid=' . $puback['msgid']);

        return $puback['msgid'];
    }
}

/**
 * Message PUBREC
 */
class spMQTTMessage_PUBREC extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PUBREC;
    protected $protocol_type = self::RECV_WITH_VARIABLE;
    protected $read_bytes = 4;

    protected function process_read($message) {
        # for PUBREC
        if (!isset($message[$this->read_bytes - 1])) {
            # error
            spMQTTDebug::Log('Message PUBREC: error on reading');
            return false;
        }

        # fixed message id
        $puback = unpack('cheader/clen/nmsgid', $message);

        # fixed header
        if ($puback['header'] >>4 == spMQTTMessageType::PUBREC && $puback['len'] == 0x02){
            spMQTTDebug::Log('Message PUBREC: continue');
        } else {
            spMQTTDebug::Log('Message PUBREC: protocol mismatch');
            return false;
        }

        spMQTTDebug::Log('Message PUBREC: msgid=' . $puback['msgid']);

        return $puback['msgid'];
    }
}

/**
 * Message PUBREL
 */
class spMQTTMessage_PUBREL extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PUBREL;
    protected $protocol_type = self::SEND_WITH_VARIABLE;

    protected $msgid = 0;
    public function setMsgID($msgid) {
        $this->msgid = $msgid;
    }

    protected function buildMessageWithVariableHeader(&$length) {;
        $i = 0;
        $buffer = "";

        $buffer .= pack('n', $this->msgid);
        $i += 2;
        spMQTTDebug::Log('Message PUBREL: msgid='.$this->msgid);

        $length = $i + $this->header->getLength();
        $this->header->setRemainingLength($i);
        return $this->header->build() . $buffer;
    }
}

/**
 * Message PUBCOMP
 */
class spMQTTMessage_PUBCOMP extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PUBCOMP;
    protected $protocol_type = self::RECV_WITH_VARIABLE;
    protected $read_bytes = 4;

    protected function process_read($message) {
        # for PUBCOMP
        if (!isset($message[$this->read_bytes - 1])) {
            # error
            spMQTTDebug::Log('Message PUBCOMP: error on reading');
            return false;
        }

        # fixed message id
        $puback = unpack('cheader/clen/nmsgid', $message);

        # fixed header
        if ($puback['header'] >>4 == spMQTTMessageType::PUBCOMP && $puback['len'] == 0x02){
            spMQTTDebug::Log('Message PUBCOMP: continue');
        } else {
            spMQTTDebug::Log('Message PUBCOMP: protocol mismatch');
            return false;
        }

        spMQTTDebug::Log('Message PUBCOMP: msgid=' . $puback['msgid']);

        return $puback['msgid'];
    }
}



/**
 * Message DISCONNECT
 */
class spMQTTMessage_DISCONNECT extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::DISCONNECT;
    protected $protocol_type = self::SEND_FIXED_ONLY;
}

/**
 * Message PINGREQ
 */
class spMQTTMessage_PINGREQ extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PINGREQ;
    protected $protocol_type = self::SEND_FIXED_ONLY;
}

/**
 * Message PINGRESP
 */
class spMQTTMessage_PINGRESP extends spMQTTMessage {
    protected $message_type = spMQTTMessageType::PINGRESP;
    protected $protocol_type = self::RECV_FIXED_ONLY;
    protected $read_bytes = 2;

    protected function process_read($message) {
        # for PINGRESP
        if (!isset($message[$this->read_bytes - 1])) {
            # error
            spMQTTDebug::Log('Message PINGRESP: error on reading');
            return false;
        }
        # check message type
        $message_type = ord($message[0]) >> 4;

        if ($message_type != $this->message_type) {
            # wrong response protocol mismatch
            spMQTTDebug::Log('Message PINGRESP: type mismatch');
            # log
            return false;
        } else {
            # write response
            spMQTTDebug::Log('Message PINGRESP: success');
            # log
            return true;
        }
    }
}


# EOF