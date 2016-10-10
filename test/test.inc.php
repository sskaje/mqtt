<?php
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require(__DIR__ . '/../vendor/autoload.php');
} else {
    require(__DIR__ . '/../autoload.example.php');
}

$MQTT_SERVER = 'tcp://test.mosquitto.org:1883/';
$MQTT_SERVER = 'tcp://192.168.11.62:1883/';
