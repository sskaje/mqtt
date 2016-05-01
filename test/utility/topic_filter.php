<?php
require(__DIR__ . '/../test.inc.php');

use \sskaje\mqtt\Utility;

check_filter("");
check_filter("/");
check_filter(" ");
check_filter("//");
check_filter(" /");

check_filter("aaaa");
check_filter("aaaa/");
check_filter("/aaaa");

check_filter("+");
check_filter("+/");
check_filter("/+");
check_filter("++");
check_filter("+/+");
check_filter("++/");
check_filter("/++");
check_filter("aaaa/+");
check_filter("+/aaaa");
check_filter("aaaa+/aaaa");
check_filter("aaaa/aaaa+");


check_filter("#");
check_filter("#/");
check_filter("/#");
check_filter("##");
check_filter("#/#");
check_filter("##/");
check_filter("/##");
check_filter("aaaa/#");
check_filter("#/aaaa");
check_filter("aaaa#/aaaa");
check_filter("aaaa/aaaa#");


check_filter("aaaa" . chr(0));
check_filter("aaaa/" . chr(0));
check_filter("aaa/a" . chr(0));
check_filter("/aaaa" . chr(0));



function check_filter($topic_filter)
{
    try {
        echo "Checking \"{$topic_filter}\" ... ";
        Utility::CheckTopicFilter($topic_filter);
        echo "\e[32mPASS\e[0m";
    } catch (Exception $e) {
        echo "\e[31mFAILED\e[0m " . $e->getMessage();
    }

    echo "\n";
}


