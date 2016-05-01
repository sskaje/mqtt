<?php
require(__DIR__ . '/../test.inc.php');

use \sskaje\mqtt\Utility;

check_name("");
check_name("/");
check_name(" ");
check_name("//");
check_name(" /");

check_name("aaaa");
check_name("aaaa/");
check_name("/aaaa");

check_name("+");
check_name("+/");
check_name("/+");
check_name("++");
check_name("+/+");
check_name("++/");
check_name("/++");
check_name("aaaa/+");
check_name("+/aaaa");
check_name("aaaa+/aaaa");
check_name("aaaa/aaaa+");


check_name("#");
check_name("#/");
check_name("/#");
check_name("##");
check_name("#/#");
check_name("##/");
check_name("/##");
check_name("aaaa/#");
check_name("#/aaaa");
check_name("aaaa#/aaaa");
check_name("aaaa/aaaa#");


check_name("aaaa" . chr(0));
check_name("aaaa/" . chr(0));
check_name("aaa/a" . chr(0));
check_name("/aaaa" . chr(0));


function check_name($topic_name)
{
    try {
        echo "Checking \"{$topic_name}\" ... ";
        Utility::CheckTopicName($topic_name);
        echo "\e[32mPASS\e[0m";
    } catch (Exception $e) {
        echo "\e[31mFAILED\e[0m " . $e->getMessage();
    }

    echo "\n";
}


